<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command\ShowContent;

use RecursiveFilterIterator;

/**
 * Filters files by extension type.
 */
class FileExtensionFilter extends RecursiveFilterIterator
{
    /** @var array */
    protected $allowedExt = ['md', 'markdown'];

    /** @var array */
    protected $excludedDir = ['.git', '.cecil', '.cache', '_site'];

    /**
     * @param \RecursiveIterator $iterator
     * @param string|array       $ext
     */
    public function __construct(\RecursiveIterator $iter, $ext = '')
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
     * Valid file.
     */
    public function accept(): bool
    {
        /** @var \SplFileInfo $file */
        $file = $this->current();
        if ($file->isFile()) {
            return in_array($file->getExtension(), $this->allowedExt);
        }
        if ($file->isDir()) {
            return !in_array($file->getBasename(), $this->excludedDir);
        }

        return true;
    }
}
