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

namespace Cecil\Command;

use Cecil\Doctor\SeoDoctor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * SEO doctor command.
 *
 * This command builds the site in dry-run mode and audits the rendered HTML
 * pages for a focused set of SEO checks inspired by editorial audit tools.
 */
class DoctorSeo extends AbstractCommand
{
    private const PAGE_LABEL_MAX_LENGTH = 60;

    private bool $includeVirtual = false;
    private bool $includeFeedback = false;
    private string $format = 'text';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('doctor:seo')
            ->setDescription('Audits rendered HTML pages for common SEO issues')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to an extra configuration file'),
                new InputOption('page', 'p', InputOption::VALUE_REQUIRED, 'Audit a single page relative to the pages directory'),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: text (default) or json', 'text'),
                new InputOption('include-virtual', null, InputOption::VALUE_NONE, 'Include virtual pages (paginated, taxonomies) in audit'),
                new InputOption('feedback', null, InputOption::VALUE_NONE, 'Include findings with feedback level'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command builds the site in dry-run mode, then audits
the rendered HTML output for a focused set of SEO checks.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>

To audit a single page, run:

  <info>%command.full_name% --page=blog/post.md</>

To output results as JSON for CI integration:

  <info>%command.full_name% --format=json</>

To include virtual pages (paginated, taxonomy pages):

  <info>%command.full_name% --include-virtual</>

To include findings with feedback level:

    <info>%command.full_name% --feedback</>

To inspect a site with an extra configuration file, run:

  <info>%command.full_name% --config=config.yml</>

Configure audit thresholds and checks in your configuration:

  doctor:
    seo:
      title: { min: 30, max: 60 }
      description: { min: 120, max: 160 }
      content: { min_words: 300 }
      checks:
        title: true
        description: true
        canonical: true
        h1: true
        og_tags: true
        img_alt: true
        content_length: true
        lang_attribute: true
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->format = (string) ($input->getOption('format') ?? 'text');
        $this->includeVirtual = (bool) $input->getOption('include-virtual');
        $this->includeFeedback = (bool) $input->getOption('feedback');

        // In JSON mode, redirect build logs to stderr to keep stdout clean for JSON output
        if ($this->format === 'json' && $output instanceof ConsoleOutputInterface) {
            $this->output = $output->getErrorOutput();
        }

        $builder = $this->getBuilder();
        $doctor = new SeoDoctor();

        if ($this->format !== 'json') {
            $this->io->title('Building site in dry-run mode for SEO audit');
        }
        $result = $doctor->audit($builder, [
            'page' => (string) ($input->getOption('page') ?? ''),
            'include_virtual' => $this->includeVirtual,
        ]);
        if (!$this->includeFeedback) {
            $result = $this->filterResultWithoutLevel($result, 'feedback');
        }

        // Output results based on format
        if ($this->format === 'json') {
            $this->outputJson($output, $result);
        } else {
            $this->outputText($output, $result);
        }

        return Command::SUCCESS;
    }

    /**
     * Output results in JSON format.
     */
    private function outputJson(OutputInterface $output, array $result): void
    {
        $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param array{
     *   summary: array{pages_audited: int, pages_without_findings: int, bad_count: int, ok_count: int, feedback_count: int},
     *   findings: array<int, array{page: string, level: string, check: string, details: string}>
     * } $result
     * @param string $level
     *
     * @return array{
     *   summary: array{pages_audited: int, pages_without_findings: int, bad_count: int, ok_count: int, feedback_count: int},
     *   findings: array<int, array{page: string, level: string, check: string, details: string}>
     * }
     */
    private function filterResultWithoutLevel(array $result, string $level): array
    {
        $findings = array_values(array_filter(
            $result['findings'],
            static fn (array $finding): bool => ($finding['level'] ?? '') !== $level
        ));

        return $this->buildFilteredResult($result, $findings);
    }

    /**
     * @param array{
     *   summary: array{pages_audited: int, pages_without_findings: int, bad_count: int, ok_count: int, feedback_count: int},
     *   findings: array<int, array{page: string, level: string, check: string, details: string}>
     * } $result
     * @param array<int, array{page: string, level: string, check: string, details: string}> $findings
     *
     * @return array{
     *   summary: array{pages_audited: int, pages_without_findings: int, bad_count: int, ok_count: int, feedback_count: int},
     *   findings: array<int, array{page: string, level: string, check: string, details: string}>
     * }
     */
    private function buildFilteredResult(array $result, array $findings): array
    {
        $counts = [
            'bad' => 0,
            'ok' => 0,
            'feedback' => 0,
        ];

        $pagesWithFindings = [];
        foreach ($findings as $finding) {
            $pagesWithFindings[$finding['page']] = true;
            $findingLevel = (string) ($finding['level'] ?? '');
            if (isset($counts[$findingLevel])) {
                $counts[$findingLevel]++;
            }
        }

        $pagesAudited = (int) ($result['summary']['pages_audited'] ?? 0);
        $pagesWithoutFindings = $pagesAudited - \count($pagesWithFindings);

        return [
            'summary' => [
                'pages_audited' => $pagesAudited,
                'pages_without_findings' => max(0, $pagesWithoutFindings),
                'bad_count' => $counts['bad'],
                'ok_count' => $counts['ok'],
                'feedback_count' => $counts['feedback'],
            ],
            'findings' => $findings,
        ];
    }

    /**
     * Output results in text format (tables).
     */
    private function outputText(OutputInterface $output, array $result): void
    {
        $summaryData = $result['summary'];
        $findings = $result['findings'];

        $summaryRows = [
            ['Pages audited', (string) $summaryData['pages_audited']],
            ['Pages without findings', (string) $summaryData['pages_without_findings']],
            ['Bad', (string) $summaryData['bad_count']],
            ['OK', (string) $summaryData['ok_count']],
        ];
        if ($this->includeFeedback) {
            $summaryRows[] = ['Feedback', (string) $summaryData['feedback_count']];
        }

        $summary = new Table($output);
        $summary
            ->setHeaderTitle('SEO audit summary')
            ->setHeaders(['Metric', 'Value'])
            ->setRows($summaryRows)
        ;
        $summary->setStyle('box')->render();

        if (!empty($findings)) {
            $rows = [];
            foreach ($findings as $finding) {
                $rows[] = [
                    $this->truncatePageLabel((string) $finding['page']),
                    $this->formatLevel($finding['level']),
                    $finding['check'],
                    $finding['details'],
                ];
            }

            $table = new Table($output);
            $table
                ->setHeaderTitle('SEO findings')
                ->setHeaders(['Page', 'Level', 'Check', 'Details'])
                ->setRows($rows)
            ;
            $table->setStyle('box')->render();
        }

        if ($summaryData['bad_count'] > 0) {
            $this->io->error(\sprintf('SEO audit found %d bad issue(s).', $summaryData['bad_count']));

            return;
        }

        if ($summaryData['ok_count'] > 0 || $summaryData['feedback_count'] > 0) {
            $this->io->warning(\sprintf('SEO audit found %d improvement(s).', $summaryData['ok_count'] + $summaryData['feedback_count']));

            return;
        }

        $this->io->success('No SEO issues found.');
    }

    private function formatLevel(string $level): string
    {
        return match ($level) {
            'bad' => '<error>Bad</error>',
            'ok' => '<comment>OK</comment>',
            default => '<info>Feedback</info>',
        };
    }

    private function truncatePageLabel(string $page): string
    {
        if (mb_strlen($page) <= self::PAGE_LABEL_MAX_LENGTH) {
            return $page;
        }

        $ellipsis = '...';
        $keepLength = self::PAGE_LABEL_MAX_LENGTH - \strlen($ellipsis);
        $startLength = (int) floor($keepLength / 2);
        $endLength = $keepLength - $startLength;

        return mb_substr($page, 0, $startLength) . $ellipsis . mb_substr($page, -$endLength);
    }
}
