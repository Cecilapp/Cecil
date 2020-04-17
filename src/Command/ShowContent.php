<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Cecil\Command\ShowContent\FileExtensionFilter;
use Cecil\Command\ShowContent\FilenameRecursiveTreeIterator;
use Cecil\Util;
use RecursiveDirectoryIterator;
use RecursiveTreeIterator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowContent extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('show:content')
            ->setDescription('Show content')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                ])
            )
            ->setHelp('Show content as tree.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = 0;
        $contentDir = (string) $this->getBuilder($output)->getConfig()->get('content.dir');
        $dataDir = (string) $this->getBuilder($output)->getConfig()->get('data.dir');

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
            // pages content
            if (is_dir(Util::joinFile($this->getPath(), $contentDir))) {
                $output->writeln(sprintf('<info>%s/</info>', $contentDir));
                $pages = $this->getFilesTree($output, $contentDir);
                if (!Util\Plateform::isWindows()) {
                    $unicodeTreePrefix($pages);
                }
                foreach ($pages as $page) {
                    $output->writeln($page);
                    $count++;
                }
            }
            // data content
            if (is_dir(Util::joinFile($this->getPath(), $dataDir))) {
                $output->writeln(sprintf('<info>%s/</info>', $dataDir));
                $datas = $this->getFilesTree($output, $dataDir);
                if (!Util\Plateform::isWindows()) {
                    $unicodeTreePrefix($datas);
                }
                foreach ($datas as $data) {
                    $output->writeln($data);
                    $count++;
                }
            }
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }

        if ($count < 1) {
            $output->writeln(sprintf('Nothing in "%s" nor "%s".', $contentDir, $dataDir));
        }

        return 0;
    }

    /**
     * Returns a console displayable tree of files.
     *
     * @param OutputInterface $output
     * @param string          $directory
     *
     * @throws \Exception
     *
     * @return FilenameRecursiveTreeIterator
     */
    public function getFilesTree(OutputInterface $output, string $directory): FilenameRecursiveTreeIterator
    {
        $dir = (string) $this->getBuilder($output)->getConfig()->get("$directory.dir");
        $ext = $this->getBuilder($output)->getConfig()->get("$directory.ext");
        $path = Util::joinFile($this->getPath(), $dir);

        if (!is_dir($path)) {
            throw new \Exception(sprintf('Invalid directory: %s.', $path));
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
