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

        $this->io->title('Diagnose site configuration');

        $table = new Table($output);
        $table
            ->setHeaderTitle('Environment')
            ->setHeaders(['Item', 'Value'])
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
                ['Config files', $this->formatConfigFiles()],
                ['Pages', $config->getPagesPath()],
                ['Layouts', $config->getLayoutsPath()],
                ['Assets', $config->getAssetsPath()],
                ['Static', $config->getStaticPath()],
                ['Output', $config->getOutputPath()],
                ['Cache', $this->getCachePathDisplay($config)],
            ])
        ;
        $table->setStyle('box')->render();

        $checks = [];
        $warnings = 0;
        $errors = 0;

        $baseUrlRaw = trim((string) $config->get('baseurl'));
        $baseUrlValid = false;
        if ($baseUrlRaw === '') {
            $this->addCheck($checks, 'Base URL', false, 'Configured', 'Not set', 'warning', $warnings, $errors);
        } else {
            try {
                $baseUrlNormalized = self::validateUrl($baseUrlRaw);
                $baseUrlValid = true;
                $this->addCheck($checks, 'Base URL', true, $baseUrlNormalized, 'Invalid URL', 'error', $warnings, $errors);
            } catch (\Exception $e) {
                $this->addCheck($checks, 'Base URL', false, 'Configured', $e->getMessage(), 'error', $warnings, $errors);
            }
        }

        $this->addCheck($checks, 'Canonical URL mode', !$config->isEnabled('canonicalurl') || $baseUrlValid, $config->isEnabled('canonicalurl') ? 'Enabled (base URL is valid)' : 'Disabled', 'Enabled but base URL is missing or invalid', 'error', $warnings, $errors);

        $this->addCheck($checks, 'Pages directory', is_dir($config->getPagesPath()), 'Found', 'Missing', 'warning', $warnings, $errors);

        $this->addCheck($checks, 'Data directory', is_dir($config->getDataPath()), 'Found', 'Missing (optional)', 'warning', $warnings, $errors);
        $this->addCheck($checks, 'Assets directory', is_dir($config->getAssetsPath()), 'Found', 'Missing (optional)', 'warning', $warnings, $errors);
        $this->addCheck($checks, 'Static directory', is_dir($config->getStaticPath()), 'Found', 'Missing (optional)', 'warning', $warnings, $errors);

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

        [$outputWritable, $outputDetails] = $this->checkDirectoryWritable($config->getOutputPath());
        $this->addCheck($checks, 'Output directory', $outputWritable, $outputDetails, $outputDetails, 'error', $warnings, $errors);

        if ($config->isEnabled('cache')) {
            $cachePath = $this->getCachePathDisplay($config);
            if ($cachePath === 'Undefined') {
                $this->addCheck($checks, 'Cache directory', false, 'Configured', 'The cache directory (`cache.dir`) is not defined.', 'error', $warnings, $errors);
            } else {
                [$cacheWritable, $cacheDetails] = $this->checkDirectoryWritable($cachePath);
                $this->addCheck($checks, 'Cache directory', $cacheWritable, $cacheDetails, $cacheDetails, 'error', $warnings, $errors);
            }
        } else {
            $this->addCheck($checks, 'Cache directory', true, 'Not required (cache is disabled)', 'Not required (cache is disabled)', 'warning', $warnings, $errors);
        }

        $this->addCheck($checks, 'Cache', $config->isEnabled('cache'), 'Enabled', 'Disabled', 'warning', $warnings, $errors);
        $this->addCheck($checks, 'Templates cache', $config->isEnabled('cache.templates'), 'Enabled', 'Disabled', 'warning', $warnings, $errors);
        $this->addCheck($checks, 'Translations cache', $config->isEnabled('cache.translations'), 'Enabled', 'Disabled', 'warning', $warnings, $errors);

        $this->addCheck($checks, 'PHP extension: fileinfo', \extension_loaded('fileinfo'), 'Loaded', 'Missing', 'error', $warnings, $errors);
        $this->addCheck($checks, 'PHP extension: gd', \extension_loaded('gd'), 'Loaded', 'Missing', 'error', $warnings, $errors);
        $this->addCheck($checks, 'PHP extension: mbstring', \extension_loaded('mbstring'), 'Loaded', 'Missing', 'error', $warnings, $errors);

        [$formatsStatus, $formatsDetails] = $this->checkOutputFormatsMapping($config);
        $this->addCheck($checks, 'Output formats mapping', $formatsStatus, $formatsDetails, $formatsDetails, 'error', $warnings, $errors);

        [$languagesStatus, $languagesDetails] = $this->checkLanguagesConfiguration($config);
        $this->addCheck($checks, 'Languages configuration', $languagesStatus, $languagesDetails, $languagesDetails, 'error', $warnings, $errors);

        $this->addCheck($checks, 'Theme(s)', $themeStatus, $themeDetails, $themeDetails, 'error', $warnings, $errors);

        $table = new Table($output);
        $table
            ->setHeaderTitle('Checks')
            ->setHeaders(['Item', 'Status', 'Details'])
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

        return implode(",\n", $configFiles);
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

    /**
     * Checks if a directory exists or can be created and is writable.
     *
     * @return array{0: bool, 1: string}
     */
    private function checkDirectoryWritable(string $directory): array
    {
        if ($directory === '') {
            return [false, 'Directory path is empty'];
        }

        if (file_exists($directory) && !is_dir($directory)) {
            return [false, 'Path exists and is not a directory'];
        }

        if (is_dir($directory)) {
            $writable = is_writable($directory);

            return [$writable, $writable ? 'Writable' : 'Not writable'];
        }

        $parent = $directory;
        while (!is_dir($parent)) {
            $nextParent = \dirname($parent);
            if ($nextParent === $parent) {
                return [false, 'Cannot determine parent directory'];
            }
            $parent = $nextParent;
        }

        $parentWritable = is_writable($parent);

        return [$parentWritable, $parentWritable ? 'Creatable (parent writable)' : 'Not creatable (parent not writable)'];
    }

    /**
     * Checks that page type formats reference existing output formats.
     *
     * @return array{0: bool, 1: string}
     */
    private function checkOutputFormatsMapping(Config $config): array
    {
        $available = [];
        foreach ((array) $config->get('output.formats') as $format) {
            if (\is_array($format) && isset($format['name']) && \is_string($format['name']) && trim($format['name']) !== '') {
                $available[] = $format['name'];
            }
        }

        if (empty($available)) {
            return [false, 'No output format defined'];
        }

        $available = array_values(array_unique($available));
        $missing = [];
        foreach ((array) $config->get('output.pagetypeformats') as $pageType => $formats) {
            foreach ((array) $formats as $format) {
                if (!\in_array($format, $available, true)) {
                    $missing[] = \sprintf('%s:%s', (string) $pageType, (string) $format);
                }
            }
        }

        if (!empty($missing)) {
            return [false, \sprintf('Unknown format reference(s): %s', implode(', ', array_unique($missing)))];
        }

        return [true, \sprintf('%d format(s), mapping is coherent', \count($available))];
    }

    /**
     * Checks language configuration consistency.
     *
     * @return array{0: bool, 1: string}
     */
    private function checkLanguagesConfiguration(Config $config): array
    {
        try {
            $default = $config->getLanguageDefault();
            $languages = $config->getLanguages();
            $codes = array_column($languages, 'code');

            if (\count($codes) !== \count(array_unique($codes))) {
                return [false, 'Duplicate language codes found'];
            }

            return [true, \sprintf('%d language(s), default: %s', \count($languages), $default)];
        } catch (\Exception $e) {
            return [false, $e->getMessage()];
        }
    }
}
