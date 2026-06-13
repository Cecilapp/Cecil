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
    private bool $includeVirtual = false;
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
        $output->writeln(\json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Output results in text format (tables).
     */
    private function outputText(OutputInterface $output, array $result): void
    {
        $summaryData = $result['summary'];
        $findings = $result['findings'];

        $summary = new Table($output);
        $summary
            ->setHeaderTitle('SEO audit summary')
            ->setHeaders(['Metric', 'Value'])
            ->setRows([
                ['Pages audited', (string) $summaryData['pages_audited']],
                ['Pages without findings', (string) $summaryData['pages_without_findings']],
                ['Bad', (string) $summaryData['bad_count']],
                ['OK', (string) $summaryData['ok_count']],
                ['Feedback', (string) $summaryData['feedback_count']],
            ])
        ;
        $summary->setStyle('box')->render();

        if (!empty($findings)) {
            $rows = [];
            foreach ($findings as $finding) {
                $rows[] = [
                    $finding['page'],
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
}
