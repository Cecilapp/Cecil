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

use ArrayIterator;
use RecursiveDirectoryIterator;
use RecursiveIterator;
use SplFileInfo;

/**
 * SortingRecursiveDirectoryIterator class.
 *
 * This class extends RecursiveDirectoryIterator to sort entries with a custom order:
 * files come first (sorted alphabetically), then directories (sorted alphabetically).
 */
class SortingRecursiveDirectoryIterator extends ArrayIterator implements RecursiveIterator
{
    /** @var string */
    private $path;

    /** @var int */
    private $flags;

    /**
     * @param string $path
     * @param int    $flags
     */
    public function __construct(string $path, int $flags = 0)
    {
        $this->path = $path;
        $this->flags = $flags;

        $sorted = $this->getSortedItems($path);
        parent::__construct($sorted);
    }

    /**
     * Get sorted items (files first, then directories).
     *
     * @param string $path
     *
     * @return array<int, SplFileInfo>
     */
    private function getSortedItems(string $path): array
    {
        $files = [];
        $dirs = [];

        // Skip dots entries if SKIP_DOTS flag is set
        $skipDots = ($this->flags & RecursiveDirectoryIterator::SKIP_DOTS) !== 0;

        foreach (scandir($path) as $item) {
            if ($skipDots && ($item === '.' || $item === '..')) {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;
            $fileInfo = new SplFileInfo($fullPath);

            if ($fileInfo->isDir()) {
                $dirs[$item] = $fileInfo;
            } else {
                $files[$item] = $fileInfo;
            }
        }

        // Sort each group alphabetically
        ksort($files);
        ksort($dirs);

        // Merge: files first, then directories
        return array_merge($files, $dirs);
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren(): bool
    {
        $current = $this->current();
        if ($current instanceof SplFileInfo) {
            return $current->isDir();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(): ?RecursiveIterator
    {
        $current = $this->current();
        if (!$current instanceof SplFileInfo) {
            return null;
        }
        if (!$current->isDir()) {
            return null;
        }

        return new self($current->getRealPath(), $this->flags);
    }
}
