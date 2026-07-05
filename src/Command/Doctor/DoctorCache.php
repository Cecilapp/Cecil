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
use Cecil\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cache doctor command.
 *
 * This command displays cache settings and usage details.
 */
class DoctorCache extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('doctor:cache')
            ->setDescription('Shows cache status')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command shows cache settings and usage details.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->getBuilder()->getConfig();
        $cachePath = $config->getCachePath();
        $cacheExists = Util\File::getFS()->exists($cachePath);

        $this->io->title('Cache status');

        $settingsTable = new Table($output);
        $settingsTable
            ->setHeaderTitle('Settings')
            ->setHeaders(['Item', 'Value'])
            ->setRows([
                ['Cache', $this->toEnabledState($config->isEnabled('cache'))],
                ['Templates cache', $this->toEnabledState($config->isEnabled('cache.templates'))],
                ['Translations cache', $this->toEnabledState($config->isEnabled('cache.translations'))],
                ['Directory', $cachePath],
                ['Exists', $cacheExists ? 'Yes' : 'No'],
            ])
        ;
        $settingsTable->setStyle('box')->render();

        $assetsStats = $this->getDirectoryStats($config->getCacheAssetsPath());
        $optimizedStats = $this->getDirectoryStats(Util::joinFile($cachePath, 'optimized'));
        $templatesStats = $this->getDirectoryStats($config->getCacheTemplatesPath());
        $translationsStats = $this->getDirectoryStats($config->getCacheTranslationsPath());
        $totalStats = $this->getDirectoryStats($cachePath);

        $usageTable = new Table($output);
        $usageTable
            ->setHeaderTitle('Usage')
            ->setHeaders(['Pool', 'Files', 'Size'])
            ->setRows([
                ['Assets', (string) $assetsStats['files'], $this->formatBytes($assetsStats['bytes'])],
                ['Templates', (string) $templatesStats['files'], $this->formatBytes($templatesStats['bytes'])],
                ['Translations', (string) $translationsStats['files'], $this->formatBytes($translationsStats['bytes'])],
                ['Optimized', (string) $optimizedStats['files'], $this->formatBytes($optimizedStats['bytes'])],
                ['Total', (string) $totalStats['files'], $this->formatBytes($totalStats['bytes'])],
            ])
        ;
        $usageTable->setStyle('box')->render();

        return Command::SUCCESS;
    }

    /**
     * Converts boolean state to readable value.
     */
    private function toEnabledState(bool $enabled): string
    {
        return $enabled ? 'Enabled' : 'Disabled';
    }

    /**
     * Returns files count and size for a directory.
     */
    private function getDirectoryStats(string $directory): array
    {
        if (!Util\File::getFS()->exists($directory)) {
            return ['files' => 0, 'bytes' => 0];
        }

        $files = 0;
        $bytes = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $files++;
            $bytes += (int) $file->getSize();
        }

        return ['files' => $files, 'bytes' => $bytes];
    }

    /**
     * Formats bytes as a human-readable size.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) $bytes;
        $index = 0;
        while ($size >= 1024 && $index < \count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return \sprintf('%s %s', number_format($size, $index > 0 ? 1 : 0, '.', ''), $units[$index]);
    }
}
