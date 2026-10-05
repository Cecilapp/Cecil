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

namespace Cecil\Test\Unit;

use Cecil\Builder;
use Cecil\Util;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class UtilTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-util-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testFormatClassAndMethodNames(): void
    {
        $object = new class () {};

        self::assertNotSame('', Util::formatClassName($object, ['lowercase' => true]));
        self::assertSame(strtolower(Util::formatClassName($object)), Util::formatClassName($object, ['lowercase' => true]));
        self::assertSame('method', Util::formatMethodName('A\\B::method'));
    }

    public function testJoinPathAndJoinFileNormalizeSeparators(): void
    {
        self::assertSame('a/b/c', Util::joinPath('a/', '/b', 'c'));

        $joinedFile = Util::joinFile('a/', '/b', 'c');
        self::assertStringContainsString('a', $joinedFile);
        self::assertStringContainsString('b', $joinedFile);
        self::assertStringContainsString('c', $joinedFile);
    }

    public function testMemoryAndDurationConverters(): void
    {
        self::assertSame('0', Util::convertMemory(0));
        self::assertStringContainsString('kb', Util::convertMemory(2048));

        self::assertStringEndsWith('ms', Util::convertDuration(0.25));
        self::assertStringEndsWith('s', Util::convertDuration(1.5));
        self::assertStringContainsString('ms', Util::convertMicrotime(microtime(true)));
    }

    public function testGetPhpRequirementsParsesComposerRequire(): void
    {
        $requirements = Util::getPhpRequirements();

        self::assertSame('8.3.0', $requirements['minimumVersion']);
        self::assertContains('fileinfo', $requirements['requiredExtensions']);
        self::assertContains('gd', $requirements['requiredExtensions']);
        self::assertContains('mbstring', $requirements['requiredExtensions']);
    }

    public function testMatchesUrlPatternForKnownServices(): void
    {
        $youtube = Util::matchesUrlPattern('https://youtu.be/dQw4w9WgXcQ');
        self::assertIsArray($youtube);
        self::assertSame('video', $youtube['type']);
        self::assertStringContainsString('youtube-nocookie.com/embed/dQw4w9WgXcQ', $youtube['url']);

        $vimeo = Util::matchesUrlPattern('https://vimeo.com/123456');
        self::assertIsArray($vimeo);
        self::assertStringContainsString('player.vimeo.com/video/123456', $vimeo['url']);

        $dailymotion = Util::matchesUrlPattern('https://www.dailymotion.com/video/x7tgcz');
        self::assertIsArray($dailymotion);
        self::assertStringContainsString('dailymotion.com/player.html?video=x7tgcz', $dailymotion['url']);

        $gist = Util::matchesUrlPattern('https://gist.github.com/user/abcdef');
        self::assertIsArray($gist);
        self::assertSame('script', $gist['type']);
        self::assertSame('https://gist.github.com/user/abcdef', $gist['url']);

        self::assertFalse(Util::matchesUrlPattern('https://example.com/nope'));
    }

    public function testAutoloadLoadsClassFromSourceExtensionsDirectory(): void
    {
        $extensionsDir = $this->tmpDir . DIRECTORY_SEPARATOR . 'extensions';
        $this->filesystem->mkdir($extensionsDir);

        $className = 'CecilTmpAutoloadClass';
        $classFile = $extensionsDir . DIRECTORY_SEPARATOR . $className . '.php';
        file_put_contents($classFile, "<?php\nclass $className {}\n");

        $builder = new Builder(['baseurl' => 'https://example.com/']);
        $builder->setSourceDir($this->tmpDir);

        Util::autoload($builder, 'extensions');

        self::assertTrue(class_exists($className));
    }
}
