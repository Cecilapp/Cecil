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

use PHPoole\Command\AbstractCommand;
use PHPoole\PHPoole;
use RecursiveDirectoryIterator;
use RecursiveTreeIterator;
use PHPoole\Command\ListContent\FilenameRecursiveTreeIterator;

class ListContent extends AbstractCommand
{
    public function processCommand()
    {
        try {
            $this->wlAnnonce('Content list:');
            $pages = $this->getPagesTree();
            if ($this->_console->isUtf8()) {
                $unicodeTreePrefix = function(RecursiveTreeIterator $tree) {
                    $prefixParts = [
                        RecursiveTreeIterator::PREFIX_LEFT         => ' ',
                        RecursiveTreeIterator::PREFIX_MID_HAS_NEXT => '│ ',
                        RecursiveTreeIterator::PREFIX_END_HAS_NEXT => '├ ',
                        RecursiveTreeIterator::PREFIX_END_LAST     => '└ '
                    ];
                    foreach ($prefixParts as $part => $string) {
                        $tree->setPrefixPart($part, $string);
                    }
                };
                $unicodeTreePrefix($pages);
            }
            foreach($pages as $page) {
                $this->_console->writeLine($page);
            }
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
        }
    }

    /**
     * Return a console displayable tree of pages
     *
     * @return FilenameRecursiveTreeIterator
     * @throws Exception
     */
    public function getPagesTree()
    {
        $pagesPath = $this->_phpoole->getWebsitePath() . '/' . PHPoole::CONTENT_DIRNAME;
        if (!is_dir($pagesPath)) {
            throw new Exception(sprintf("Invalid %s directory", PHPoole::CONTENT_DIRNAME));
        }
        $dirIterator = new RecursiveDirectoryIterator($pagesPath, RecursiveDirectoryIterator::SKIP_DOTS);
        $pages = new FilenameRecursiveTreeIterator(
            $dirIterator,
            FilenameRecursiveTreeIterator::SELF_FIRST
        );
        return $pages;
    }
}