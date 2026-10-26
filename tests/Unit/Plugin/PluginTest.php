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

namespace Cecil\Test\Unit\Plugin;

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Event\BuildEvents;
use Cecil\Exception\RuntimeException;
use Cecil\Generator\AbstractGenerator;
use Cecil\Plugin\AbstractPlugin;
use Cecil\Plugin\Registry;
use Cecil\Renderer\PostProcessor\AbstractPostProcessor;
use Cecil\Util;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class PluginTest extends TestCase
{
    private string $sourceDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->sourceDir = Util::joinFile(sys_get_temp_dir(), 'cecil-plugin-' . uniqid('', true));
        $this->filesystem->mkdir($this->sourceDir);
        TestPlugin::$finished = 0;
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->sourceDir);
    }

    public function testPluginRegistersExtensionsAndConfiguration(): void
    {
        $builder = $this->createBuilder();
        $builder->build([]);

        self::assertTrue($builder->getPages()->has('plugin-page'));
        self::assertStringContainsString('<!-- plugin -->', $this->readOutput());
        self::assertSame(1, TestPlugin::$finished);
        self::assertSame('Plugin page', $builder->getConfig()->get('testplugin.title'));
        self::assertSame(['class' => PluginGenerator::class, 'priority' => 35], $builder->getConfig()->get('pages.generators.PluginGenerator'));
    }

    public function testSiteConfigurationOverridesPlugin(): void
    {
        $builder = $this->createBuilder([
            'testplugin' => ['title' => 'Custom title'],
            'output'     => ['postprocessors' => ['Comment' => false]],
            'pages'      => ['generators' => ['PluginGenerator' => ['priority' => 45]]],
        ]);
        $builder->build([]);

        self::assertSame('Custom title', $builder->getPages()->get('plugin-page')->getVariable('title'));
        self::assertStringNotContainsString('<!-- plugin -->', $this->readOutput());
        self::assertSame(45, $builder->getConfig()->get('pages.generators.PluginGenerator.priority'));
    }

    public function testPluginsAreRegisteredOnce(): void
    {
        $builder = $this->createBuilder();
        $builder->build(['dry-run' => true]);
        $builder->build(['dry-run' => true]);

        self::assertSame(2, TestPlugin::$finished);
    }

    public function testInvalidPluginFailsTheBuild(): void
    {
        $builder = $this->createBuilder(['extensions' => ['plugins' => ['Invalid' => PluginGenerator::class]]]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to load extension "Invalid" (extensions.plugins)');

        $builder->build(['dry-run' => true]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createBuilder(array $config = []): Builder
    {
        $config = array_replace_recursive([
            'baseurl'    => 'https://example.com/',
            'cache'      => false,
            'extensions' => ['plugins' => ['Test' => TestPlugin::class]],
        ], $config);

        return (new Builder($config, new NullLogger()))->setSourceDir($this->sourceDir);
    }

    private function readOutput(): string
    {
        return (string) file_get_contents(Util::joinFile($this->sourceDir, '_site', 'plugin-page', 'index.html'));
    }
}

/**
 * Registers a generator, a post-processor, a listener and a default configuration.
 */
class TestPlugin extends AbstractPlugin
{
    public static int $finished = 0;

    public function register(Registry $registry): void
    {
        $registry
            ->config(['testplugin' => ['title' => 'Plugin page']])
            ->generator(PluginGenerator::class, priority: 35)
            ->postProcessor(PluginPostProcessor::class, name: 'Comment')
            ->listen(BuildEvents::BUILD_FINISHED, fn () => self::$finished++);
    }
}

class PluginGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        $page = (new Page('plugin-page'))
            ->setType(Type::PAGE)
            ->setPath('plugin-page')
            ->setBodyHtml('<p>Plugin</p>')
            ->setVariable('title', $this->config->get('testplugin.title'));
        $this->generatedPages->add($page);
    }
}

class PluginPostProcessor extends AbstractPostProcessor
{
    public function process(Page $page, string $output, string $format): string
    {
        return $format === 'html' ? $output . '<!-- plugin -->' : $output;
    }
}
