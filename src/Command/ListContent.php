<?php
/*
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Cecil\Command\ListContent\FileExtensionFilter;
use Cecil\Command\ListContent\FilenameRecursiveTreeIterator;
use RecursiveDirectoryIterator;
use RecursiveTreeIterator;

/**
 * Class ListContent.
 */
class ListContent extends AbstractCommand
{
    /**
     * @var string
     */
    protected $contentDir;

    /**
     * {@inheritdoc}
     */
    public function processCommand()
    {
        $this->contentDir = $this->getBuilder()->getConfig()->get('content.dir');

        try {
            $this->wlAnnonce(sprintf('%s/', $this->contentDir));
            $pages = $this->getPagesTree();
            if ($this->getConsole()->isUtf8()) {
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
            }
            foreach ($pages as $page) {
                $this->getConsole()->writeLine($page);
            }
            $this->getConsole()->writeLine('');
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }
    }

    /**
     * Return a console displayable tree of pages.
     *
     * @throws \Exception
     *
     * @return FilenameRecursiveTreeIterator
     */
    public function getPagesTree()
    {
        $pagesPath = $this->path.'/'.$this->contentDir;
        if (!is_dir($pagesPath)) {
            throw new \Exception(sprintf('Invalid directory: %s.', $pagesPath));
        }
        $dirIterator = new RecursiveDirectoryIterator($pagesPath, RecursiveDirectoryIterator::SKIP_DOTS);
        $dirIterator = new FileExtensionFilter($dirIterator, $this->getBuilder()->getConfig()->get('content.ext'));
        $pages = new FilenameRecursiveTreeIterator(
            $dirIterator,
            FilenameRecursiveTreeIterator::SELF_FIRST
        );

        return $pages;
    }
}
