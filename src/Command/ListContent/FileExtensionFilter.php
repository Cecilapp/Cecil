<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Command\ListContent;

use FilterIterator;

class FileExtensionFilter extends FilterIterator
{
    // whitelist of file extensions
    protected $ext = ['md', 'markdown'];

    public function __construct(\Iterator $iter, $ext = '')
    {
        if (!empty($ext)) {
            if (!is_array($ext)) {
                $ext = [$ext];
            }
            $this->ext = $ext;
        }
        parent::__construct($iter);
    }

    public function accept()
    {
        return !in_array($this->getExtension(), $this->ext);
    }
}
