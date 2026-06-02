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

use Cecil\Builder;
use Cecil\Config;
use Cecil\Util;
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
        $config = $builder->getConfig();

        $table = new Table($output);
        $table
            ->setHeaderTitle('Environment')
            ->setHeaders(['Check', 'Value'])
            ->setRows([
                ['Cecil version', Builder::getVersion()],
                ['PHP version', PHP_VERSION],
                ['OS', PHP_OS_FAMILY],
                ['Working directory', $this->getPath()],
            ])
        ;
        $table->setStyle('box')->render();

        $table = new Table($output);
        $table
            ->setHeaderTitle('Paths')
            ->setHeaders(['Item', 'Value'])
            ->setRows([
                ['Source', $config->getSourceDir()],
                ['Destination', $config->getDestinationDir()],
                ['Pages', $config->getPagesPath()],
                ['Layouts', $config->getLayoutsPath()],
                ['Assets', $config->getAssetsPath()],
                ['Static', $config->getStaticPath()],
                ['Cache', $this->getCachePathDisplay($config)],
                ['Config files', $this->formatConfigFiles()],
            ])
        ;
        $table->setStyle('box')->render();

        $checks = [];
        $warnings = 0;
        $errors = 0;

        $this->addCheck($checks, 'Base URL', trim((string) $config->get('baseurl'), '/') !== '', 'Configured', 'Not set', 'warning', $warnings, $errors);
        $this->addCheck($checks, 'Pages directory', is_dir($config->getPagesPath()), 'Found', 'Missing', 'warning', $warnings, $errors);
        $themeConfigured = false;
        $themeStatus = true;
        $themeDetails = 'None configured';
        try {
            $themes = $config->getTheme();
            if ($themes !== null) {
                $themeConfigured = true;
                $themeDetails = implode(', ', $themes);
                $config->hasTheme();
            }
        } catch (\Exception $e) {
            $themeStatus = false;
            $themeDetails = $e->getMessage();
        }

        $this->addCheck($checks, 'Layouts directory', is_dir($config->getLayoutsPath()) || ($themeConfigured && $themeStatus), 'Found or provided by a theme', 'Missing and no theme configured', 'error', $warnings, $errors);
        $this->addCheck($checks, 'Cache', $config->isEnabled('cache'), 'Enabled', 'Disabled', 'warning', $warnings, $errors);
        $this->addCheck($checks, 'Templates cache', $config->isEnabled('cache.templates'), 'Enabled', 'Disabled', 'warning', $warnings, $errors);
        $this->addCheck($checks, 'Translations cache', $config->isEnabled('cache.translations'), 'Enabled', 'Disabled', 'warning', $warnings, $errors);
        $this->addCheck($checks, 'Theme(s)', $themeStatus, $themeDetails, $themeDetails, 'error', $warnings, $errors);

        $table = new Table($output);
        $table
            ->setHeaderTitle('Checks')
            ->setHeaders(['Check', 'Status', 'Details'])
            ->setRows($checks)
        ;
        $table->setStyle('box')->render();

        if ($errors > 0) {
            $this->io->error(\sprintf('%d error(s) found.', $errors));
        } elseif ($warnings > 0) {
            $this->io->warning(\sprintf('%d warning(s) found.', $warnings));
        } else {
            $this->io->success('No problems found.');
        }

        return Command::SUCCESS;
    }

    /**
     * Adds a health check row.
     *
     * @param array<int, array<int, string>> $checks
     */
    private function addCheck(
        array &$checks,
        string $label,
        bool $success,
        string $successDetails,
        string $failureDetails,
        string $failureSeverity,
        int &$warnings,
        int &$errors
    ): void {
        if ($success) {
            $checks[] = [$label, '<info>OK</info>', $successDetails];

            return;
        }

        if ($failureSeverity === 'error') {
            $errors++;
            $status = '<error>FAIL</error>';
        } else {
            $warnings++;
            $status = '<comment>WARN</comment>';
        }

        $checks[] = [$label, $status, $failureDetails];
    }

    /**
     * Formats the list of configuration files.
     */
    private function formatConfigFiles(): string
    {
        $configFiles = $this->getConfigFiles();
        if (empty($configFiles)) {
            return 'None';
        }

        return implode(', ', $configFiles);
    }

    /**
     * Returns the configured cache path without creating directories.
     */
    private function getCachePathDisplay(Config $config): string
    {
        $cacheDir = (string) $config->get('cache.dir');
        if ($cacheDir === '') {
            return 'Undefined';
        }

        if (Util\File::getFS()->isAbsolutePath($cacheDir)) {
            return Util::joinFile($cacheDir, 'cecil');
        }

        return Util::joinFile($config->getDestinationDir(), $cacheDir);
    }
}
