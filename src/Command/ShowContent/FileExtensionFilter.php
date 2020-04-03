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
 * Filter files by extension type.
 *
 * Class FileExtensionFilter
 */
class FileExtensionFilter extends RecursiveFilterIterator
{
    /** @var array */
    protected $allowedExt = ['md', 'markdown'];

    /**
     * @param RecursiveIterator $iterator
     * @param mixed             $ext
     */
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
    public function accept(): bool
    {
        $file = $this->current();
        if ($file->isFile()) {
            return in_array($file->getExtension(), $this->allowedExt);
        }

        return true;
    }
}
