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

namespace Cecil\Doctor;

use Cecil\Builder;
use Cecil\Config;
use Cecil\Util;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

/**
 * Site diagnosis domain service.
 */
class SiteDoctor
{
    /**
     * @param array<int, string> $configFiles
     *
     * @return array{
     *   environment: array<int, array{0: string, 1: string}>,
     *   paths: array<int, array{0: string, 1: string}>,
     *   checks: array<int, array{item: string, status: string, details: string}>,
     *   warnings: int,
     *   errors: int
     * }
     */
    public function diagnose(Builder $builder, string $workingDirectory, array $configFiles): array
    {
        $config = $builder->getConfig();

        $checks = [];
        $warnings = 0;
        $errors = 0;

        $baseUrlRaw = trim((string) $config->get('baseurl'));
        $baseUrlValid = false;
        if ($baseUrlRaw === '') {
            $this->addCheck($checks, 'Base URL', false, 'Configured', 'Not set', 'warning', $warnings, $errors);
        } else {
            try {
                $baseUrlNormalized = $this->validateUrl($baseUrlRaw);
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

        try {
            $phpRequirements = Util::getPhpRequirements();
            $phpMinimumVersion = $phpRequirements['minimumVersion'];
            $phpVersionDetails = \sprintf('Required >= %s (current: %s)', $phpMinimumVersion, PHP_VERSION);
            $this->addCheck(
                $checks,
                'PHP version requirement',
                version_compare(PHP_VERSION, $phpMinimumVersion, '>='),
                $phpVersionDetails,
                $phpVersionDetails,
                'error',
                $warnings,
                $errors
            );

            foreach ($phpRequirements['requiredExtensions'] as $extension) {
                $this->addCheck($checks, \sprintf('PHP extension: %s', $extension), \extension_loaded($extension), 'Loaded', 'Missing', 'error', $warnings, $errors);
            }
        } catch (\RuntimeException $e) {
            $this->addCheck($checks, 'PHP requirements (composer.json)', false, 'Loaded', $e->getMessage(), 'warning', $warnings, $errors);
        }

        [$formatsStatus, $formatsDetails] = $this->checkOutputFormatsMapping($config);
        $this->addCheck($checks, 'Output formats mapping', $formatsStatus, $formatsDetails, $formatsDetails, 'error', $warnings, $errors);

        [$languagesStatus, $languagesDetails] = $this->checkLanguagesConfiguration($config);
        $this->addCheck($checks, 'Languages configuration', $languagesStatus, $languagesDetails, $languagesDetails, 'error', $warnings, $errors);

        $this->addCheck($checks, 'Theme(s)', $themeStatus, $themeDetails, $themeDetails, 'error', $warnings, $errors);

        return [
            'environment' => [
                ['Cecil version', Builder::getVersion()],
                ['PHP version', PHP_VERSION],
                ['OS', PHP_OS_FAMILY],
                ['Working directory', $workingDirectory],
            ],
            'paths' => [
                ['Config files', $this->formatConfigFiles($configFiles)],
                ['Pages', $config->getPagesPath()],
                ['Layouts', $config->getLayoutsPath()],
                ['Assets', $config->getAssetsPath()],
                ['Static', $config->getStaticPath()],
                ['Output', $config->getOutputPath()],
                ['Cache', $this->getCachePathDisplay($config)],
            ],
            'checks' => $checks,
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    /**
     * @param array<int, array{item: string, status: string, details: string}> $checks
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
            $checks[] = [
                'item' => $label,
                'status' => 'ok',
                'details' => $successDetails,
            ];

            return;
        }

        if ($failureSeverity === 'error') {
            $errors++;
            $status = 'error';
        } else {
            $warnings++;
            $status = 'warning';
        }

        $checks[] = [
            'item' => $label,
            'status' => $status,
            'details' => $failureDetails,
        ];
    }

    /**
     * @param array<int, string> $configFiles
     */
    private function formatConfigFiles(array $configFiles): string
    {
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

    /**
     * Validate URL.
     */
    private function validateUrl(string $url): string
    {
        if ($url === '/') {
            return $url;
        }

        $validator = Validation::createValidator();
        $violations = $validator->validate($url, new Url());
        if (\count($violations) > 0) {
            foreach ($violations as $violation) {
                throw new \RuntimeException($violation->getMessage());
            }
        }

        return rtrim($url, '/') . '/';
    }
}
