<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Cecil\Command\ShowContent\FileExtensionFilter;
use Cecil\Command\ShowContent\FilenameRecursiveTreeIterator;
use Cecil\Exception\RuntimeException;
use Cecil\Util;
use RecursiveDirectoryIterator;
use RecursiveTreeIterator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shows content.
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
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to the config file'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command shows the website\'s content as a tree.

To show the content, run:

  <info>%command.full_name%</>

To show the content from a specific directory, run:

  <info>%command.full_name% path/to/directory</>

To show the content from a specific configuration file, run:

  <info>%command.full_name% --config=config.yml</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = 0;
        $contentTypes = ['pages', 'data'];

        // formating output
        $unicodeTreePrefix = function (RecursiveTreeIterator $tree) {
            $prefixParts = [
                RecursiveTreeIterator::PREFIX_LEFT         => ' ',
                RecursiveTreeIterator::PREFIX_MID_HAS_NEXT => '│ ',
                RecursiveTreeIterator::PREFIX_END_HAS_NEXT => '├ ',
                RecursiveTreeIterator::PREFIX_END_LAST     => '└ ',
            ];
            foreach ($prefixParts as $part => $string) {
                $tree->setPrefixPart($part, $string);
            }
        };

        try {
            foreach ($contentTypes as $type) {
                $dir = (string) $this->getBuilder()->getConfig()->get("$type.dir");
                if (is_dir(Util::joinFile($this->getPath(), $dir))) {
                    $output->writeln(\sprintf('<info>%s:</info>', $dir));
                    $pages = $this->getFilesTree($type);
                    if (!Util\Platform::isWindows()) {
                        $unicodeTreePrefix($pages);
                    }
                    foreach ($pages as $page) {
                        $output->writeln($page);
                        $count++;
                    }
                }
                if ($count < 1) {
                    $output->writeln(\sprintf('<comment>Nothing in "%s".</comment>', $dir));
                }
            }

            return 0;
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf($e->getMessage()));
        }
    }

    /**
     * Returns a console displayable tree of files.
     *
     * @throws RuntimeException
     */
    private function getFilesTree(string $contentType): FilenameRecursiveTreeIterator
    {
        $dir = (string) $this->getBuilder()->getConfig()->get("$contentType.dir");
        $ext = (array) $this->getBuilder()->getConfig()->get("$contentType.ext");
        $path = Util::joinFile($this->getPath(), $dir);

        if (!is_dir($path)) {
            throw new RuntimeException(\sprintf('Invalid directory: %s.', $path));
        }

        $dirIterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $dirIterator = new FileExtensionFilter($dirIterator, $ext);
        $files = new FilenameRecursiveTreeIterator(
            $dirIterator,
            FilenameRecursiveTreeIterator::SELF_FIRST
        );

        return $files;
    }
}
