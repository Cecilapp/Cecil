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

namespace Cecil\Test\Unit\Converter\Highlighter;

use Cecil\Config;
use Cecil\Converter\Highlighter\HighlighterFactory;
use Cecil\Converter\Highlighter\HighlightJs;
use Cecil\Converter\Highlighter\Tempest;
use Cecil\Exception\ConfigException;
use Cecil\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

class HighlighterTest extends TestCase
{
    public function testFactoryReturnsHighlightJsByDefault(): void
    {
        self::assertInstanceOf(HighlightJs::class, HighlighterFactory::create(new Config()));
        self::assertInstanceOf(HighlightJs::class, HighlighterFactory::create(new Config(['pages' => ['body' => ['highlight' => true]]])));
    }

    public function testFactoryReturnsConfiguredEngine(): void
    {
        $config = new Config(['pages' => ['body' => ['highlight' => ['engine' => 'tempest']]]]);

        self::assertInstanceOf(Tempest::class, HighlighterFactory::create($config));
    }

    public function testFactoryThrowsOnUnknownEngine(): void
    {
        $config = new Config(['pages' => ['body' => ['highlight' => ['engine' => 'unknown']]]]);

        $this->expectException(ConfigException::class);
        HighlighterFactory::create($config);
    }

    public function testFactoryReturnsGivenEngine(): void
    {
        self::assertInstanceOf(HighlightJs::class, HighlighterFactory::fromEngine('highlightjs'));
        self::assertInstanceOf(Tempest::class, HighlighterFactory::fromEngine('tempest'));
    }

    public function testFactoryThrowsOnUnknownGivenEngine(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Highlighter engine must be one of: "highlightjs", "tempest".');
        HighlighterFactory::fromEngine('unknown');
    }

    public function testHighlightJsHighlightsCode(): void
    {
        $highlighted = (new HighlightJs())->highlight('<?php echo "a<b";', 'php');

        self::assertSame('hljs php', $highlighted->class);
        self::assertStringContainsString('<span class="hljs-keyword">echo</span>', $highlighted->html);
        self::assertStringContainsString('&lt;b', $highlighted->html);
    }

    public function testTempestHighlightsCode(): void
    {
        $highlighted = (new Tempest())->highlight('<?php echo "a<b";', 'php');

        self::assertSame('hl', $highlighted->class);
        self::assertStringContainsString('<span class="hl-keyword">echo</span>', $highlighted->html);
        self::assertStringContainsString('&lt;b', $highlighted->html);
    }

    public function testHighlightJsThrowsOnUnknownLanguage(): void
    {
        $this->expectException(RuntimeException::class);
        (new HighlightJs())->highlight('code', 'unknown-language');
    }

    public function testTempestThrowsOnUnknownLanguage(): void
    {
        $this->expectException(RuntimeException::class);
        (new Tempest())->highlight('code', 'unknown-language');
    }
}
