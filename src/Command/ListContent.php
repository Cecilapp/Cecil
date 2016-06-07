<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Command;

use PHPoole\Command\ListContent\FilenameRecursiveTreeIterator;
use PHPoole\Command\ListContent\FileExtensionFilter;
use RecursiveDirectoryIterator;
use RecursiveTreeIterator;

class ListContent extends AbstractCommand
{
    public function processCommand()
    {
        try {
            $this->wlAnnonce('Content:');
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
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
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
        $pagesPath = $this->path.'/'.$this->getPHPoole()->getOption('content.dir');
        if (!is_dir($pagesPath)) {
            throw new \Exception(sprintf('Invalid %s directory', 'content'));
        }
        $dirIterator = new RecursiveDirectoryIterator($pagesPath, RecursiveDirectoryIterator::SKIP_DOTS);
        $pages = new FilenameRecursiveTreeIterator(
            $dirIterator,
            FilenameRecursiveTreeIterator::SELF_FIRST
        );
        $pages = new FileExtensionFilter($pages, $this->getPHPoole()->getOption('content.ext'));

        return $pages;
    }
}
