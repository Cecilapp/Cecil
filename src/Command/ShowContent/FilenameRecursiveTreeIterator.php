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

namespace Cecil\Command\ShowContent;

use RecursiveTreeIterator;

/**
 * FilenameRecursiveTreeIterator class.
 *
 * This class extends RecursiveTreeIterator to modify the current method
 * so that it returns only the filename of the current item, instead of the full path.
 * It is useful for displaying a tree structure of files with just their names.
 */
class FilenameRecursiveTreeIterator extends RecursiveTreeIterator
{
    /**
     * @return mixed
     */
    public function current(): mixed
    {
        return str_replace(
            (string) $this->getInnerIterator()->current(),
            substr(strrchr((string) $this->getInnerIterator()->current(), DIRECTORY_SEPARATOR), 1),
            parent::current()
        );
    }
}
