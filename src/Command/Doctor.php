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

use Cecil\Doctor\SiteDoctor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Doctor command.
 *
 * This command inspects the current Cecil installation and site configuration.
 * It highlights the active paths, cache settings and common setup problems
 * so users can quickly spot why a build might fail or behave unexpectedly.
 */
class Doctor extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('doctor')
            ->setDescription('Diagnoses the site configuration')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to an extra configuration file'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command diagnoses the current site and Cecil environment.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>

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
        $doctor = new SiteDoctor();
        $diagnosis = $doctor->diagnose($builder, (string) $this->getPath(), $this->getConfigFiles());

        $this->io->title('Diagnose site configuration');

        $table = new Table($output);
        $table
            ->setHeaderTitle('Environment')
            ->setHeaders(['Item', 'Value'])
            ->setRows($diagnosis['environment'])
        ;
        $table->setStyle('box')->render();

        $table = new Table($output);
        $table
            ->setHeaderTitle('Paths')
            ->setHeaders(['Item', 'Value'])
            ->setRows($diagnosis['paths'])
        ;
        $table->setStyle('box')->render();

        $rows = [];
        foreach ($diagnosis['checks'] as $check) {
            $rows[] = [
                $check['item'],
                $this->formatStatus($check['status']),
                $check['details'],
            ];
        }

        $table = new Table($output);
        $table
            ->setHeaderTitle('Checks')
            ->setHeaders(['Item', 'Status', 'Details'])
            ->setRows($rows)
        ;
        $table->setStyle('box')->render();

        if ($diagnosis['errors'] > 0) {
            $this->io->error(\sprintf('%d error(s) found.', $diagnosis['errors']));
        } elseif ($diagnosis['warnings'] > 0) {
            $this->io->warning(\sprintf('%d warning(s) found.', $diagnosis['warnings']));
        } else {
            $this->io->success('No problems found.');
        }

        return Command::SUCCESS;
    }

    /**
     * Formats status from domain diagnostics for console output.
     */
    private function formatStatus(string $status): string
    {
        return match ($status) {
            'error' => '<error>FAIL</error>',
            'warning' => '<comment>WARN</comment>',
            default => '<info>OK</info>',
        };
    }
}
