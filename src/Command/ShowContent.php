<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Cecil\Command\ShowContent\FileExtensionFilter;
use Cecil\Command\ShowContent\FilenameRecursiveTreeIterator;
use RecursiveDirectoryIterator;
use RecursiveTreeIterator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowContent extends Command
{
    /**
     * @var string
     */
    protected $contentDir;

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
                    new InputArgument('path', InputArgument::OPTIONAL, 'If specified, use the given path as working directory'),
                ])
            )
            ->setHelp('Show content as tree.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->contentDir = $this->getBuilder($output)->getConfig()->get('content.dir');

        try {
            $output->writeln(sprintf('<info>%s/</info>', $this->contentDir));
            $pages = $this->getPagesTree($output);
            //if ($this->getConsole()->isUtf8()) {
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
                $unicodeTreePrefix($pages);
            //}
            foreach ($pages as $page) {
                $output->writeln($page);
            }
            $output->writeln('');
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }

        return 0;
    }

    /**
     * Return a console displayable tree of pages.
     *
     * @param OutputInterface $output
     *
     * @throws \Exception
     *
     * @return FilenameRecursiveTreeIterator
     */
    public function getPagesTree(OutputInterface $output)
    {
        $pagesPath = $this->getPath().'/'.$this->contentDir;
        if (!is_dir($pagesPath)) {
            throw new \Exception(sprintf('Invalid directory: %s.', $pagesPath));
        }
        $dirIterator = new RecursiveDirectoryIterator($pagesPath, RecursiveDirectoryIterator::SKIP_DOTS);
        $dirIterator = new FileExtensionFilter($dirIterator, $this->getBuilder($output)->getConfig()->get('content.ext'));
        $pages = new FilenameRecursiveTreeIterator(
            $dirIterator,
            FilenameRecursiveTreeIterator::SELF_FIRST
        );

        return $pages;
    }
}
