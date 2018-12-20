<?php
/*
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command\ListContent;

use RecursiveFilterIterator;

/**
 * Filter files by extension type.
 *
 * Class FileExtensionFilter
 */
class FileExtensionFilter extends RecursiveFilterIterator
{
    protected $allowedExt = ['md', 'markdown'];

    public function __construct($iter, $ext = '')
    {
        parent::__construct($iter);
        if (!empty($ext)) {
            if (!is_array($ext)) {
                $ext = [$ext];
            }
            $this->allowedExt = $ext;
        }
    }

    /**
     * @return bool
     */
    public function accept()
    {
        $file = $this->current();
        if ($file->isFile()) {
            return in_array($file->getExtension(), $this->allowedExt);
        }

        return true;
    }
}
