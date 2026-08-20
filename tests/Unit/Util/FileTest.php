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

use Cecil\Exception\RuntimeException;
use Cecil\Util\File;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class FileTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-file-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testGetFsReturnsFilesystemInstance(): void
    {
        self::assertInstanceOf(Filesystem::class, File::getFS());
    }

    public function testFileGetContentsHandlesEmptyAndMissingFiles(): void
    {
        self::assertFalse(File::fileGetContents(''));
        self::assertFalse(File::fileGetContents($this->tmpDir . DIRECTORY_SEPARATOR . 'missing.txt'));
    }

    public function testFileGetContentsReadsLocalFile(): void
    {
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'sample.txt';
        file_put_contents($file, 'hello');

        self::assertSame('hello', File::fileGetContents($file));
    }

    public function testGetMediaTypeReturnsMainAndSubtype(): void
    {
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'sample.txt';
        file_put_contents($file, 'hello');

        [$type, $subtype] = File::getMediaType($file);

        self::assertNotSame('', $type);
        self::assertStringContainsString('/', $subtype);
        self::assertSame(explode('/', $subtype)[0], $type);
    }

    public function testGetMediaTypeThrowsForMissingFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to get media type');

        File::getMediaType($this->tmpDir . DIRECTORY_SEPARATOR . 'does-not-exist.txt');
    }

    public function testGetExtensionUsesPathInfoWhenAvailable(): void
    {
        self::assertSame('css', File::getExtension('style.min.css'));
    }

    public function testGetExtensionCanBeGuessedForFileWithoutExtension(): void
    {
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'README';
        file_put_contents($file, 'hello');

        self::assertSame('txt', File::getExtension($file));
    }

    public function testGetExtensionThrowsWhenFileWithoutExtensionCannotBeGuessed(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to get extension');

        File::getExtension($this->tmpDir . DIRECTORY_SEPARATOR . 'missing-no-extension');
    }

    public function testReadExifReturnsEmptyArrayForInvalidSource(): void
    {
        self::assertSame([], File::readExif(''));

        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'sample.txt';
        file_put_contents($file, 'hello');
        self::assertSame([], File::readExif($file));
    }

    public function testGetRealPathResolvesExistingRelativePath(): void
    {
        $path = File::getRealPath('../config/default.php');

        self::assertFileExists($path);
        self::assertStringEndsWith('config' . DIRECTORY_SEPARATOR . 'default.php', $path);
    }

    public function testGetRealPathThrowsForUnknownPathOutsidePhar(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to get the real path');

        File::getRealPath('../config/does-not-exist.php');
    }

    public function testIsRemoteRecognizesHttpLikeSchemes(): void
    {
        self::assertTrue(File::isRemote('http://example.com/file.css'));
        self::assertTrue(File::isRemote('https://example.com/file.css'));
        self::assertTrue(File::isRemote('ftp://example.com/file.css'));
        self::assertFalse(File::isRemote('/local/path/file.css'));
    }

    public function testIsRemoteExistsReturnsFalseForLocalPath(): void
    {
        self::assertFalse(File::isRemoteExists($this->tmpDir . DIRECTORY_SEPARATOR . 'local.txt'));
    }
}
