<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Command\Doctor;

use Cecil\Command\AbstractCommand;
use Cecil\Doctor\LinksDoctor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Links doctor command.
 *
 * This command builds the site in dry-run mode and checks the internal and/or
 * external links found in the rendered HTML pages and in the linked CSS files.
 */
class DoctorLinks extends AbstractCommand
{
    use FailOnTrait;
    use FindingsTableTrait;

    private bool $includeFeedback = false;
    private string $format = 'text';
    private string $baseurl = '';

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function configure()
    {
        $this
            ->setName('doctor:links')
            ->setDescription('Checks internal and external links of the rendered pages')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to an extra configuration file'),
                new InputOption('page', 'p', InputOption::VALUE_REQUIRED, 'Check links of a single page relative to the pages directory'),
                new InputOption('internal', null, InputOption::VALUE_NONE, 'Check internal links'),
                new InputOption('external', null, InputOption::VALUE_NONE, 'Check external links'),
                new InputOption('exclude', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Exclude links matching this regular expression'),
                new InputOption('timeout', null, InputOption::VALUE_REQUIRED, 'External links request timeout, in seconds'),
                new InputOption('concurrency', null, InputOption::VALUE_REQUIRED, 'Number of concurrent external links requests'),
                new InputOption('no-cache', null, InputOption::VALUE_NONE, 'Do not use cached external links results'),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: text (default) or json', 'text'),
                new InputOption('include-virtual', null, InputOption::VALUE_NONE, 'Include virtual pages (paginated, taxonomies) in audit'),
                new InputOption('feedback', null, InputOption::VALUE_NONE, 'Include findings with feedback level'),
                $this->getFailOnOption(),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command builds the site in dry-run mode, then checks the links
found in the rendered HTML pages (links, images, scripts, stylesheets, media, inline
styles) and in the linked CSS files (<comment>url()</comment> and <comment>@import</comment>).

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>

By default, only internal links are checked. To check external links too, run:

  <info>%command.full_name% --internal --external</>

To check external links only, run:

  <info>%command.full_name% --external</>

To check links of a single page, run:

  <info>%command.full_name% --page=blog/post.md</>

To exclude links matching a regular expression, run:

  <info>%command.full_name% --external --exclude="linkedin\.com" --exclude="^https://localhost"</>

To output results as JSON for CI integration:

  <info>%command.full_name% --format=json</>

By default, the command fails if findings with the <comment>bad</comment> level are found.
To change this behavior, use the <info>--fail-on</info> option:

  <info>%command.full_name% --fail-on=ok</>
  <info>%command.full_name% --fail-on=never</>

Configure default settings in your configuration:

  doctor:
    links:
      internal: true
      external: false
      timeout: 10
      concurrency: 10
      cache_ttl: 86400
      exclude: ['linkedin\.com']
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->format = (string) ($input->getOption('format') ?? 'text');
        $this->includeFeedback = (bool) $input->getOption('feedback');
        $failOn = $this->getFailOn($input);

        // without --internal nor --external options, the configuration is used
        $internal = $external = null;
        if ($input->getOption('internal') || $input->getOption('external')) {
            $internal = (bool) $input->getOption('internal');
            $external = (bool) $input->getOption('external');
        }

        // In JSON mode, redirect build logs to stderr to keep stdout clean for JSON output
        if ($this->format === 'json' && $output instanceof ConsoleOutputInterface) {
            $this->output = $output->getErrorOutput();
        }

        $builder = $this->getBuilder();
        $this->baseurl = (string) $builder->getConfig()->get('baseurl');
        $doctor = new LinksDoctor();

        if ($this->format !== 'json') {
            $this->io->title('Building site in dry-run mode for links audit');
        }
        $result = $doctor->audit($builder, [
            'page' => (string) ($input->getOption('page') ?? ''),
            'include_virtual' => (bool) $input->getOption('include-virtual'),
            'feedback' => $this->includeFeedback,
            'internal' => $internal,
            'external' => $external,
            'exclude' => (array) $input->getOption('exclude'),
            'timeout' => $input->getOption('timeout') !== null ? (int) $input->getOption('timeout') : null,
            'concurrency' => $input->getOption('concurrency') !== null ? (int) $input->getOption('concurrency') : null,
            'cache' => !$input->getOption('no-cache'),
        ]);

        if ($this->format === 'json') {
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $this->outputText($output, $result);
        }

        return $this->shouldFail($result['summary'], $failOn) ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Output results in text format (tables).
     *
     * @param array{
     *   summary: array{pages_audited: int, links_checked: int, external_urls: int, bad_count: int, ok_count: int, feedback_count: int},
     *   findings: array<int, array{page: string, level: string, type: string, link: string, details: string}>
     * } $result
     */
    private function outputText(OutputInterface $output, array $result): void
    {
        $summaryData = $result['summary'];
        $findings = $result['findings'];

        $summaryRows = [
            ['Pages audited', (string) $summaryData['pages_audited']],
            ['Links checked', (string) $summaryData['links_checked']],
            ['External URLs', (string) $summaryData['external_urls']],
            ['Bad', (string) $summaryData['bad_count']],
            ['OK', (string) $summaryData['ok_count']],
        ];
        if ($this->includeFeedback) {
            $summaryRows[] = ['Feedback', (string) $summaryData['feedback_count']];
        }

        $summary = new Table($output);
        $summary
            ->setHeaderTitle('Links audit summary')
            ->setHeaders(['Metric', 'Value'])
            ->setRows($summaryRows)
        ;
        $summary->setStyle('box')->render();

        if (!empty($findings)) {
            $this->outputFindings($output, $findings);
        }

        if ($summaryData['bad_count'] > 0) {
            $this->io->error(\sprintf('Links audit found %d broken link(s).', $summaryData['bad_count']));

            return;
        }

        if ($summaryData['ok_count'] > 0 || $summaryData['feedback_count'] > 0) {
            $this->io->warning(\sprintf('Links audit found %d improvement(s).', $summaryData['ok_count'] + $summaryData['feedback_count']));

            return;
        }

        $this->io->success('No broken links found.');
    }

    /**
     * Output findings grouped by page.
     *
     * @param array<int, array{page: string, level: string, type: string, link: string, details: string}> $findings
     */
    private function outputFindings(OutputInterface $output, array $findings): void
    {
        $this->renderFindings($output, $findings, [
            [
                'header' => 'Issue',
                'cell' => fn (array $finding): string => $this->formatFindingIssue($finding['level'], $finding['type']),
            ],
            [
                'header' => 'Link',
                'cell' => fn (array $finding, ?int $width): string => $this->formatLink($finding, $width),
                'ratio' => 0.45,
            ],
            [
                'header' => 'Details',
                'cell' => fn (array $finding, ?int $width): string => $this->wrapFindingValue($finding['details'], $width),
                'ratio' => 0.55,
            ],
        ]);
    }

    /**
     * Formats the link as a terminal hyperlink (OSC 8), so it stays clickable when wrapped.
     *
     * @param array<string, string> $finding
     * @param int|null              $wrapWidth Wraps the link on several lines (each one being a hyperlink)
     */
    private function formatLink(array $finding, ?int $wrapWidth = null): string
    {
        $lines = explode("\n", $this->wrapFindingValue($finding['link'], $wrapWidth));
        $href = $this->getLinkHref($finding['page'], $finding['link']);

        return implode("\n", array_map(
            static fn (string $line): string => $href !== null
                ? \sprintf('<href=%s>%s</>', $href, OutputFormatter::escape($line))
                : OutputFormatter::escape($line),
            $lines
        ));
    }

    /**
     * Returns the absolute URL of a link, or null if it can't be resolved.
     */
    private function getLinkHref(string $page, string $link): ?string
    {
        $href = null;
        if (preg_match('#^https?://#i', $link)) {
            $href = $link;
        } elseif (str_starts_with($link, '//')) {
            $href = 'https:' . $link;
        } elseif (preg_match('#^(https?://[^/?\#]+)#i', $this->baseurl, $matches)) {
            // the page label is relative to the base URL
            $pageUrl = rtrim($this->baseurl, '/') . $page;
            $href = match (true) {
                str_starts_with($link, '/') => $matches[1] . $link,
                str_starts_with($link, '#'), str_starts_with($link, '?') => explode('#', $pageUrl, 2)[0] . $link,
                default => substr($pageUrl, 0, (int) strrpos($pageUrl, '/') + 1) . $link,
            };
        }

        // characters that can't be used in a formatter style tag
        if ($href === null || preg_match('#[\s<>;]#', $href)) {
            return null;
        }

        return $href;
    }
}
