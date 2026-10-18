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

namespace Cecil\Test\Unit\Renderer;

use Cecil\Builder;
use Cecil\Config;
use Cecil\Renderer\Config as RendererConfig;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testLocalizedOnlyKeyExistsInItsLanguage(): void
    {
        $builder = Builder::create(new Config([
            'language'  => 'en',
            'title'     => 'Global title',
            'languages' => [
                ['code' => 'en', 'locale' => 'en_US'],
                ['code' => 'fr', 'locale' => 'fr_FR', 'config' => ['slogan' => 'Bonjour']],
            ],
        ]));
        $fr = new RendererConfig($builder, 'fr');
        $en = new RendererConfig($builder, 'en');

        self::assertTrue(isset($fr['slogan']));
        self::assertSame('Bonjour', $fr['slogan']);
        self::assertFalse(isset($en['slogan']));
        // fallback to the global value
        self::assertTrue(isset($fr['title']));
        self::assertSame('Global title', $fr['title']);
    }
}
