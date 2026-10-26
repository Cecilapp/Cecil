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

namespace Cecil\Test\Unit\Extension;

use Cecil\Builder;
use Cecil\Util;
use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\Filesystem\Filesystem;

class AutoloaderTest extends TestCase
{
    private string $sourceDir;

    private string $namespace;

    private Filesystem $filesystem;

    /** @var list<Builder> */
    private array $builders = [];

    /** @var AbstractLogger&object{messages: list<array{0: string, 1: string}>} */
    private AbstractLogger $logger;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->sourceDir = Util::joinFile(sys_get_temp_dir(), 'cecil-autoloader-' . uniqid('', true));
        $this->filesystem->mkdir($this->sourceDir);
        // classes can't be unloaded: each test uses its own namespace
        $this->namespace = 'AutoloaderTest' . bin2hex(random_bytes(4));
        $this->logger = new class () extends AbstractLogger {
            /** @var list<array{0: string, 1: string}> */
            public array $messages = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->messages[] = [(string) $level, (string) $message];
            }
        };
    }

    protected function tearDown(): void
    {
        foreach ($this->builders as $builder) {
            spl_autoload_unregister([$builder->getAutoloader(), 'loadClass']);
        }
        $this->filesystem->remove($this->sourceDir);
    }

    public function testClassIsLoadedFromExtensionsDirectoryByNamespacePath(): void
    {
        $this->createClass("extensions/{$this->namespace}/Foo.php", $this->namespace, 'Foo');
        $this->createBuilder();

        self::assertTrue(class_exists("{$this->namespace}\\Foo"));
    }

    public function testClassIsLoadedWithPsr4Mapping(): void
    {
        $this->createClass('src/Generator/Foo.php', "{$this->namespace}\\Generator", 'Foo');
        $this->createBuilder(['extensions' => ['autoload' => ["{$this->namespace}\\" => 'src']]]);

        self::assertTrue(class_exists("{$this->namespace}\\Generator\\Foo"));
    }

    public function testLongestPsr4PrefixWins(): void
    {
        $this->createClass('generators/Foo.php', "{$this->namespace}\\Generator", 'Foo');
        $this->createBuilder(['extensions' => ['autoload' => [
            "{$this->namespace}\\"            => 'src',
            "{$this->namespace}\\Generator\\" => 'generators',
        ]]]);

        self::assertTrue(class_exists("{$this->namespace}\\Generator\\Foo"));
    }

    public function testClassIsLoadedFromTheme(): void
    {
        $this->createClass("themes/mytheme/extensions/{$this->namespace}/Foo.php", $this->namespace, 'Foo');
        $this->createBuilder(['theme' => ['mytheme']]);

        self::assertTrue(class_exists("{$this->namespace}\\Foo"));
    }

    public function testReservedNamespaceIsReportedAsWarningForSiteClass(): void
    {
        $this->createClass("extensions/Cecil/{$this->namespace}/Foo.php", "Cecil\\{$this->namespace}", 'Foo');
        $this->createBuilder();

        self::assertTrue(class_exists("Cecil\\{$this->namespace}\\Foo"));
        self::assertContains('warning', array_column($this->logger->messages, 0));
        self::assertStringContainsString('reserved "Cecil" namespace', implode("\n", array_column($this->logger->messages, 1)));
    }

    public function testReservedNamespaceIsReportedAsDebugForThemeClass(): void
    {
        $this->createClass("themes/mytheme/extensions/Cecil/{$this->namespace}/Foo.php", "Cecil\\{$this->namespace}", 'Foo');
        $this->createBuilder(['theme' => ['mytheme']]);

        self::assertTrue(class_exists("Cecil\\{$this->namespace}\\Foo"));
        self::assertNotContains('warning', array_column($this->logger->messages, 0));
    }

    public function testDependenciesAreLoadedFromVendorAutoload(): void
    {
        $this->filesystem->dumpFile(
            Util::joinFile($this->sourceDir, 'extensions', 'vendor', 'autoload.php'),
            "<?php\nspl_autoload_register(function (\$class) {\n    if (\$class === '{$this->namespace}\\\\Lib') {\n        eval('namespace {$this->namespace}; class Lib {}');\n    }\n});\n"
        );
        $this->createBuilder();

        self::assertTrue(class_exists("{$this->namespace}\\Lib"));
    }

    public function testComposerClassLoaderIsRegisteredAfterCecilLoaders(): void
    {
        $this->createClass('extensions/vendor/lib/Lib.php', $this->namespace, 'Lib');
        $this->filesystem->dumpFile(
            Util::joinFile($this->sourceDir, 'extensions', 'vendor', 'autoload.php'),
            "<?php\n\$loader = new \\Composer\\Autoload\\ClassLoader();\n\$loader->addPsr4('{$this->namespace}\\\\', __DIR__ . '/lib');\n\$loader->register(true);\n\nreturn \$loader;\n"
        );
        $builder = $this->createBuilder();

        self::assertTrue($builder->getAutoloader()->requireVendors());
        self::assertFalse($builder->getAutoloader()->requireVendors());
        $functions = spl_autoload_functions();
        $last = end($functions);
        self::assertIsArray($last);
        self::assertInstanceOf(ClassLoader::class, $last[0]);
        self::assertTrue(class_exists("{$this->namespace}\\Lib"));
        $last[0]->unregister();
    }

    private function createClass(string $path, string $namespace, string $class): void
    {
        $this->filesystem->dumpFile(Util::joinFile($this->sourceDir, $path), "<?php\nnamespace $namespace;\n\nclass $class {}\n");
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createBuilder(array $config = []): Builder
    {
        $builder = new Builder(['baseurl' => 'https://example.com/'] + $config, $this->logger);
        $builder->setSourceDir($this->sourceDir);
        $this->builders[] = $builder;

        return $builder;
    }
}
