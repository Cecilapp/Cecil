<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Command\ShowContent;

use RecursiveFilterIterator;

/**
 * FileExtensionFilter class.
 *
 * This class extends RecursiveFilterIterator to filter files based on their extensions.
 * It allows only files with specified extensions (default: 'md' and 'yml') to be accepted,
 * while excluding certain directories (like '.git', '.cecil', '.cache', '_site', 'vendor', 'node_modules').
 * It can be used to traverse a directory structure and filter out unwanted files and directories.
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
