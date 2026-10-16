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

namespace Cecil\Test\Unit\Util;

use Cecil\Util\FileWatcher;
use Cecil\Util\FileWatcherResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FileWatcherTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-filewatcher-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testFirstScanReturnsNoChanges(): void
    {
        $this->write('a.md', 'a');

        $result = $this->createWatcher()->findChanges();

        $this->assertFalse($result->hasChanges());
    }

    public function testNoChangesWhenFilesAreUntouched(): void
    {
        $this->write('a.md', 'a');
        $watcher = $this->createWatcher();
        $watcher->initialize();

        $this->assertFalse($watcher->findChanges()->hasChanges());
    }

    public function testDetectsNewFile(): void
    {
        $watcher = $this->createWatcher();
        $watcher->initialize();
        $this->write('new.md', 'new');

        $result = $watcher->findChanges();

        $this->assertTrue($result->hasChanges());
        $this->assertSame([$this->path('new.md')], $result->getNewFiles());
        $this->assertSame([], $result->getUpdatedFiles());
        $this->assertSame([], $result->getDeletedFiles());
    }

    public function testDetectsDeletedFile(): void
    {
        $this->write('a.md', 'a');
        $watcher = $this->createWatcher();
        $watcher->initialize();
        $this->filesystem->remove($this->path('a.md'));

        $result = $watcher->findChanges();

        $this->assertSame([$this->path('a.md')], $result->getDeletedFiles());
    }

    public function testDetectsSameSizeUpdateWithinTheSameSecond(): void
    {
        $this->write('a.md', 'aaa');
        $watcher = $this->createWatcher();
        $watcher->initialize();
        $this->write('a.md', 'bbb');
        touch($this->path('a.md'), (int) filemtime($this->path('a.md')));

        $result = $watcher->findChanges();

        $this->assertSame([$this->path('a.md')], $result->getUpdatedFiles());
    }

    public function testDetectsUpdateOfOldFile(): void
    {
        $this->write('a.md', 'a');
        touch($this->path('a.md'), time() - 3600);
        $watcher = $this->createWatcher();
        $watcher->initialize();
        $this->write('a.md', 'b');
        touch($this->path('a.md'), time() - 60);

        $result = $watcher->findChanges();

        $this->assertSame([$this->path('a.md')], $result->getUpdatedFiles());
    }

    public function testIgnoresTouchWithoutContentChangeOfRecentFile(): void
    {
        $this->write('a.md', 'a');
        $watcher = $this->createWatcher();
        $watcher->initialize();
        touch($this->path('a.md'), time() + 1);

        $this->assertFalse($watcher->findChanges()->hasChanges());
    }

    public function testDetectsNewUpdatedAndDeletedFilesTogether(): void
    {
        $this->write('updated.md', 'a');
        $this->write('deleted.md', 'a');
        $watcher = $this->createWatcher();
        $watcher->initialize();
        $this->write('updated.md', 'ab');
        $this->filesystem->remove($this->path('deleted.md'));
        $this->write('new1.md', 'a');
        $this->write('new2.md', 'a');

        $result = $watcher->findChanges();

        $newFiles = $result->getNewFiles();
        sort($newFiles);
        $this->assertSame([$this->path('new1.md'), $this->path('new2.md')], $newFiles);
        $this->assertSame([$this->path('updated.md')], $result->getUpdatedFiles());
        $this->assertSame([$this->path('deleted.md')], $result->getDeletedFiles());
    }

    public function testChangesAreReportedOnlyOnce(): void
    {
        $watcher = $this->createWatcher();
        $watcher->initialize();
        $this->write('new.md', 'new');

        $this->assertTrue($watcher->findChanges()->hasChanges());
        $this->assertFalse($watcher->findChanges()->hasChanges());
    }

    public function testEmptyResultHasNoChanges(): void
    {
        $this->assertFalse((new FileWatcherResult())->hasChanges());
    }

    private function createWatcher(): FileWatcher
    {
        return new FileWatcher((new Finder())->files()->in($this->tmpDir));
    }

    private function write(string $name, string $content): void
    {
        $this->filesystem->dumpFile($this->path($name), $content);
    }

    private function path(string $name): string
    {
        return $this->tmpDir . DIRECTORY_SEPARATOR . $name;
    }
}
