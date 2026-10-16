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

namespace Cecil\Util;

/**
 * Changes found by the file watcher.
 */
class FileWatcherResult
{
    /**
     * @param array<int, string> $newFiles
     * @param array<int, string> $updatedFiles
     * @param array<int, string> $deletedFiles
     */
    public function __construct(
        private readonly array $newFiles = [],
        private readonly array $updatedFiles = [],
        private readonly array $deletedFiles = [],
    ) {
    }

    /**
     * Returns true if at least one file was created, updated or deleted.
     */
    public function hasChanges(): bool
    {
        return \count($this->newFiles) > 0 || \count($this->updatedFiles) > 0 || \count($this->deletedFiles) > 0;
    }

    /**
     * Returns the paths of new files.
     *
     * @return array<int, string>
     */
    public function getNewFiles(): array
    {
        return $this->newFiles;
    }

    /**
     * Returns the paths of updated files.
     *
     * @return array<int, string>
     */
    public function getUpdatedFiles(): array
    {
        return $this->updatedFiles;
    }

    /**
     * Returns the paths of deleted files.
     *
     * @return array<int, string>
     */
    public function getDeletedFiles(): array
    {
        return $this->deletedFiles;
    }
}
