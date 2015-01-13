<?php
namespace PHPoole\Spl;

use RecursiveTreeIterator;

/**
 * Replace Filepath by Filename
 */
class FilenameRecursiveTreeIterator extends RecursiveTreeIterator
{
    public function current()
    {
        return str_replace(
            $this->getInnerIterator()->current(),
            substr(strrchr($this->getInnerIterator()->current(), DIRECTORY_SEPARATOR), 1),
            parent::current()
        );
    }
}