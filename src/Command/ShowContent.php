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

use Cecil\Exception\RuntimeException;
use Cecil\Util;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TreeHelper;
use Symfony\Component\Console\Helper\TreeStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ShowContent command.
 *
 * This command displays the website's content as a tree structure.
 * It can be used to quickly review the content files organized by type (pages, data).
 * It supports displaying content from specified directories and can filter files by their extensions.
 */
class ShowContent extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('show:content')
            ->setDescription('Shows content as tree')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to an extra configuration file'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command shows the website\'s content as a tree.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>

To show the content with an extra configuration file, run:

  <info>%command.full_name% --config=config.yml</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $contentTypes = ['pages', 'data'];

        $this->io->title('Show pages and data content');

        try {
            $merged = [];
            foreach ($contentTypes as $type) {
                $dir = (string) $this->getBuilder()->getConfig()->get("$type.dir");
                $path = Util::joinFile($this->getPath(), $dir);

                if (!is_dir($path)) {
                    continue;
                }

                $ext = (array) $this->getBuilder()->getConfig()->get("$type.ext");
                $treeStructure = $this->buildTreeStructure($path, $ext);

                if (!empty($treeStructure)) {
                    $merged[$type] = $treeStructure;
                }
            }

            if (empty($merged)) {
                $output->writeln('<comment>Nothing to show.</comment>');
            } else {
                $tree = TreeHelper::createTree($output, $this->getPath(), $merged, TreeStyle::rounded());
                $tree->render();
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf($e->getMessage()));
        }
    }

    /**
     * Build a tree structure (array) from the directory, filtering by file extensions.
     *
     * @param string $path
     * @param array  $allowedExtensions
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    private function buildTreeStructure(string $path, array $allowedExtensions): array
    {
        if (!is_dir($path)) {
            throw new RuntimeException(\sprintf('Invalid directory: %s.', $path));
        }

        $items = $this->getSortedItems($path);
        $structure = [];
        $excludedDirs = ['.git', '.cecil', '.cache', '_site', 'vendor', 'node_modules'];

        foreach ($items as $item) {
            if ($item->isDir() && !\in_array($item->getBasename(), $excludedDirs)) {
                $subStructure = $this->buildSubdirectoryStructure($item->getRealPath(), $allowedExtensions, $excludedDirs);
                if (!empty($subStructure)) {
                    $structure[$item->getBasename()] = $subStructure;
                }
            } elseif ($item->isFile() && \in_array($item->getExtension(), $allowedExtensions)) {
                $structure[] = $item->getBasename();
            }
        }

        return $structure;
    }

    /**
     * Build subdirectory tree structure recursively.
     *
     * @param string $path
     * @param array  $allowedExtensions
     * @param array  $excludedDirs
     *
     * @return array<string, mixed>
     */
    private function buildSubdirectoryStructure(string $path, array $allowedExtensions, array $excludedDirs): array
    {
        $items = $this->getSortedItems($path);
        $structure = [];

        foreach ($items as $item) {
            if ($item->isDir() && !\in_array($item->getBasename(), $excludedDirs)) {
                $subStructure = $this->buildSubdirectoryStructure($item->getRealPath(), $allowedExtensions, $excludedDirs);
                if (!empty($subStructure)) {
                    $structure[$item->getBasename()] = $subStructure;
                }
            } elseif ($item->isFile() && \in_array($item->getExtension(), $allowedExtensions)) {
                $structure[] = $item->getBasename();
            }
        }

        return $structure;
    }

    /**
     * Get sorted items (files first, then directories) from a directory.
     *
     * @param string $path
     *
     * @return array<int, SplFileInfo>
     */
    private function getSortedItems(string $path): array
    {
        $files = [];
        $dirs = [];

        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . \DIRECTORY_SEPARATOR . $item;
            $fileInfo = new SplFileInfo($fullPath);

            if ($fileInfo->isDir()) {
                $dirs[$item] = $fileInfo;
            } else {
                $files[$item] = $fileInfo;
            }
        }

        // Sort each group alphabetically
        ksort($files);
        ksort($dirs);

        // Merge: files first, then directories
        return array_merge($files, $dirs);
    }
}
