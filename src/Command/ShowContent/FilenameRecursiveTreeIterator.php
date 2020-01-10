<?php
/*
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command\ShowContent;

use RecursiveTreeIterator;

/**
 * Replace Filepath by Filename.
 *
 * Class FilenameRecursiveTreeIterator
 */
class FilenameRecursiveTreeIterator extends RecursiveTreeIterator
{
    /**
     * @return mixed
     */
    public function current()
    {
        return str_replace(
            $this->getInnerIterator()->current(),
            substr(strrchr($this->getInnerIterator()->current(), DIRECTORY_SEPARATOR), 1),
            parent::current()
        );
    }
}
