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

use RecursiveFilterIterator;

/**
 * Filters files by extension type.
 */
class FileExtensionFilter extends RecursiveFilterIterator
{
    /** @var array */
    protected $allowedExt = ['md'];

    /** @var array */
    protected $excludedDir = ['.git', '.cecil', '.cache', '_site'];

    /**
     * @param \RecursiveIterator $iterator
     * @param string|array       $extensions
     */
    public function __construct(\RecursiveIterator $iterator, $extensions = '')
    {
        parent::__construct($iterator);
        if (!empty($extensions)) {
            if (!\is_array($extensions)) {
                $extensions = [$extensions];
            }
            $this->allowedExt = $extensions;
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
            return \in_array($file->getExtension(), $this->allowedExt);
        }
        if ($file->isDir()) {
            return !\in_array($file->getBasename(), $this->excludedDir);
        }

        return true;
    }
}
