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

use Cecil\Doctor\FrontmatterDoctor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Front matter doctor command.
 *
 * This command validates front matter syntax for all pages found in the
 * configured pages directory, using the configured front matter format.
 */
class DoctorFrontmatter extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('doctor:frontmatter')
            ->setAliases(['doctor:fm'])
            ->setDescription('Validates pages front matter syntax')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to an extra configuration file'),
                new InputOption('page', 'p', InputOption::VALUE_REQUIRED, 'Validate a single page relative to the pages directory'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command validates front matter syntax for pages.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>

To validate a single page, run:

  <info>%command.full_name% --page=blog/post.md</>

To inspect a site with an extra configuration file, run:

  <info>%command.full_name% --config=config.yml</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $builder = $this->getBuilder();
        $doctor = new FrontmatterDoctor();
        $diagnosis = $doctor->diagnose($builder, [
            'page' => (string) ($input->getOption('page') ?? ''),
        ]);

        $this->io->title('Front matter audit summary');

        $summary = $diagnosis['summary'];
        $summaryTable = new Table($output);
        $summaryTable
            ->setHeaders(['Metric', 'Value'])
            ->setRows([
                ['Files scanned', (string) $summary['files_scanned']],
                ['Files with front matter', (string) $summary['files_with_frontmatter']],
                ['Files without front matter', (string) $summary['files_without_frontmatter']],
                ['Files with valid front matter', (string) $summary['valid_frontmatters']],
                ['Errors in front matter', (string) $summary['invalid_frontmatters']],
            ])
        ;
        $summaryTable->setStyle('box')->render();

        if (!empty($diagnosis['findings'])) {
            $rows = [];
            foreach ($diagnosis['findings'] as $finding) {
                $rows[] = [
                    $this->formatFileLink($finding['file'], $finding['file_absolute']),
                    $this->formatStatus($finding['status']),
                    $finding['line'] !== null ? (string) $finding['line'] : '-',
                    $this->formatDetails($finding['details']),
                ];
            }

            $detailsTable = new Table($output);
            $detailsTable
                ->setHeaders(['File', 'Status', 'Line', 'Details'])
                ->setRows($rows)
            ;
            $detailsTable->setStyle('box')->render();
        }

        if ($summary['invalid_frontmatters'] > 0) {
            $this->io->error(\sprintf('%d error(s) found.', $summary['invalid_frontmatters']));
        } else {
            $this->io->success('No front matter errors found.');
        }

        return Command::SUCCESS;
    }

    /**
     * Formats status for console output.
     */
    private function formatStatus(string $status): string
    {
        return match ($status) {
            'error' => '<error>FAIL</error>',
            'warning' => '<comment>WARN</comment>',
            default => '<info>OK</info>',
        };
    }

    /**
     * Formats a clickable file label using console hyperlinks.
     */
    private function formatFileLink(string $label, string $absolutePath): string
    {
        $normalizedPath = str_replace('\\', '/', $absolutePath);
        $uriPath = preg_match('/^[A-Za-z]:\//', $normalizedPath) ? '/' . $normalizedPath : $normalizedPath;
        $encodedPath = implode('/', array_map(static fn (string $segment): string => str_replace('%3A', ':', rawurlencode($segment)), explode('/', $uriPath)));

        return \sprintf('<href=file://%s>%s</>', $encodedPath, $label);
    }

    /**
     * Wraps details lines to keep table width under control.
     */
    private function formatDetails(string $details): string
    {
        return wordwrap($details, 120, "\n", true);
    }
}
