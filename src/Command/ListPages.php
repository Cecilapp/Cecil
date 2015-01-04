<?php
namespace PHPoole\Command;

use PHPoole\Command\AbstractCommand;
use PHPoole;

class ListPages extends AbstractCommand
{
    public function processCommand()
    {
        try {
            $this->wlInfo('Lists pages');
            $pages = $this->_api->getPagesTree();
            if ($this->_console->isUtf8()) {
                $unicodeTreePrefix = function(\RecursiveTreeIterator $tree) {
                    $prefixParts = [
                        \RecursiveTreeIterator::PREFIX_LEFT         => ' ',
                        \RecursiveTreeIterator::PREFIX_MID_HAS_NEXT => '│ ',
                        \RecursiveTreeIterator::PREFIX_END_HAS_NEXT => '├ ',
                        \RecursiveTreeIterator::PREFIX_END_LAST     => '└ '
                    ];
                    foreach ($prefixParts as $part => $string) {
                        $tree->setPrefixPart($part, $string);
                    }
                };
                $unicodeTreePrefix($pages);
            }
            $this->_console->writeLine('[pages]');
            foreach($pages as $page) {
                $this->_console->writeLine($page);
            }
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
        }
    }
}