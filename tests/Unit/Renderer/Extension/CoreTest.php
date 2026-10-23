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

namespace Cecil\Test\Unit\Renderer\Extension;

use Cecil\Builder;
use Cecil\Collection\Menu\Entry as MenuEntry;
use Cecil\Renderer\Extension\Core;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class CoreTest extends TestCase
{
    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function enabledProvider(): array
    {
        return [
            'true'                  => [true, true],
            'false'                 => [false, false],
            'null'                  => [null, false],
            'string'                => ['custom', true],
            'map without enabled'   => [['dir' => '.cache'], true],
            'map enabled'           => [['dir' => '.cache', 'enabled' => true], true],
            'map disabled'          => [['dir' => '.cache', 'enabled' => false], false],
            'map with enabled null' => [['enabled' => null], false],
            'list'                  => [['html', 'atom'], true],
        ];
    }

    #[DataProvider('enabledProvider')]
    public function testIsEnabled(mixed $value, bool $expected): void
    {
        self::assertSame($expected, (new Core(new Builder()))->isEnabled($value));
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function externalProvider(): array
    {
        return [
            'null'                   => [null, false],
            'empty'                  => ['', false],
            'relative path'          => ['/about/', false],
            'page ID'                => ['about', false],
            'external URL'           => ['https://example.org/', true],
            'external URL uppercase' => ['HTTPS://example.org/', true],
            'protocol-relative'      => ['//example.org/x', true],
            'mailto'                 => ['mailto:contact@example.org', true],
            'tel'                    => ['tel:+33123456789', true],
            'baseurl'                => ['https://example.com', false],
            'URL of the site'        => ['https://example.com/about/', false],
            'baseurl as prefix only' => ['https://example.com.org/', true],
            'menu entry internal'    => [(new MenuEntry('about'))->setUrl('/about/'), false],
            'menu entry external'    => [(new MenuEntry('ext'))->setUrl('https://example.org/'), true],
        ];
    }

    #[DataProvider('externalProvider')]
    public function testIsExternal(mixed $value, bool $expected): void
    {
        self::assertSame($expected, (new Core(new Builder(['baseurl' => 'https://example.com/'])))->isExternal($value));
    }

    public function testEnabledTwigTestWithConfigBooleanShorthand(): void
    {
        $builder = new Builder(['cache' => false, 'optimize' => true]);
        $twig = new Environment(new ArrayLoader([
            'test' => '{{ cache is enabled ? "on" : "off" }}|{{ optimize is enabled ? "on" : "off" }}',
        ]));
        $twig->addExtension(new Core($builder));

        self::assertSame('off|on', $twig->render('test', [
            'cache'    => $builder->getConfig()->get('cache'),
            'optimize' => $builder->getConfig()->get('optimize'),
        ]));
    }

    public function testAssetFiltersReuseCachedResults(): void
    {
        $filesystem = new Filesystem();
        $destinationDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-core-test-' . uniqid('', true);
        $filesystem->mkdir($destinationDir);

        try {
            $builder = (new Builder())->setDestinationDir($destinationDir);
            $core = new Core($builder);
            $css = 'a { color: red; }';
            $js = 'const answer = 42;';
            $scss = '$color: red; a { color: $color; }';

            $cssResult = $core->minifyCss($css);
            $jsResult = $core->minifyJs($js);
            $scssResult = $core->scssToCss($scss);

            self::assertSame($cssResult, $core->minifyCss($css));
            self::assertSame($jsResult, $core->minifyJs($js));
            self::assertSame($scssResult, $core->scssToCss($scss));
        } finally {
            $filesystem->remove($destinationDir);
        }
    }
}
