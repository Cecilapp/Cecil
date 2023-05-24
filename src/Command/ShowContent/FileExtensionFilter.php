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
    protected $allowedExt = ['md', 'yml'];

    /** @var array */
    protected $excludedDir = ['.git', '.cecil', '.cache', '_site', 'vendor', 'node_modules'];

    /**
     * @param \RecursiveIterator $iterator
     * @param string|array|null  $extensions
     */
    public function __construct(\RecursiveIterator $iterator, $extensions = null)
    {
        if (!\is_null($extensions)) {
            if (!\is_array($extensions)) {
                $extensions = [$extensions];
            }
            $this->allowedExt = $extensions;
        }
        parent::__construct($iterator);
    }

    /**
     * Get children with allowed extensions.
     */
    public function getChildren(): ?RecursiveFilterIterator
    {
        return new self($this->getInnerIterator()->getChildren(), $this->allowedExt); // @phpstan-ignore-line
    }

    /**
     * Valid file with allowed extensions.
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
