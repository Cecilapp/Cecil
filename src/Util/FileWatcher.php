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

use Symfony\Component\Finder\Finder;

/**
 * File watcher.
 *
 * Detects new, updated and deleted files by polling the files matched by a Symfony Finder.
 * A file is considered as changed when its modification time or size differs from the previous scan.
 * Files modified during the second of a scan are also compared by content hash, because
 * modification times have a one second resolution.
 */
class FileWatcher
{
    /** @var array<string, array{mtime: int, size: int, hash: ?string}>|null Files state from the previous scan. */
    private ?array $snapshot = null;

    public function __construct(
        private readonly Finder $finder,
    ) {
    }

    /**
     * Takes the initial snapshot of the watched files, if not already done.
     */
    public function initialize(): void
    {
        if ($this->snapshot === null) {
            $this->findChanges();
        }
    }

    /**
     * Finds changes since the previous scan.
     *
     * The first call only takes a snapshot and returns no changes.
     */
    public function findChanges(): FileWatcherResult
    {
        clearstatcache();
        $racyTime = time() - 1;
        $previous = $this->snapshot;
        $snapshot = [];
        $newFiles = [];
        $updatedFiles = [];

        foreach ($this->finder as $file) {
            $path = $file->getPathname();
            $mtime = (int) $file->getMTime();
            $size = (int) $file->getSize();
            // a file modified during this scan second may be modified again without mtime change
            $hash = $mtime >= $racyTime ? $this->hash($path) : null;
            $snapshot[$path] = ['mtime' => $mtime, 'size' => $size, 'hash' => $hash];

            if ($previous === null) {
                continue;
            }
            if (!isset($previous[$path])) {
                $newFiles[] = $path;
                continue;
            }
            $before = $previous[$path];
            $changed = $before['hash'] !== null
                ? $before['hash'] !== ($hash ?? $this->hash($path))
                : $before['mtime'] !== $mtime || $before['size'] !== $size;
            if ($changed) {
                $updatedFiles[] = $path;
            }
        }

        $deletedFiles = $previous === null ? [] : array_keys(array_diff_key($previous, $snapshot));
        $this->snapshot = $snapshot;

        return new FileWatcherResult($newFiles, $updatedFiles, $deletedFiles);
    }

    /**
     * Returns the content hash of a file.
     */
    private function hash(string $path): ?string
    {
        $hash = @hash_file('xxh3', $path);

        return $hash === false ? null : $hash;
    }
}
