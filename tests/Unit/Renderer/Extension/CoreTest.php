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
use Cecil\Renderer\Extension\Core;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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
}
