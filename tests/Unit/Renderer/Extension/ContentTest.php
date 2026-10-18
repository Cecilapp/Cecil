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
use Cecil\Exception\ConfigException;
use Cecil\Renderer\Extension\Content;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    private function createExtension(array $config = []): Content
    {
        return new Content(new Builder(array_replace_recursive(['baseurl' => 'https://example.com/'], $config)));
    }

    public function testHighlightUsesHighlightJsByDefault(): void
    {
        self::assertStringContainsString('hljs-keyword', $this->createExtension()->highlight('<?php echo 1;', 'php'));
    }

    public function testHighlightIgnoresPagesEngine(): void
    {
        $extension = $this->createExtension(['pages' => ['body' => ['highlight' => ['engine' => 'tempest']]]]);

        self::assertStringContainsString('hljs-keyword', $extension->highlight('<?php echo 1;', 'php'));
    }

    public function testHighlightUsesLayoutsEngine(): void
    {
        $extension = $this->createExtension([
            'pages' => ['body' => ['highlight' => ['engine' => 'highlightjs']]],
            'layouts' => ['highlight' => ['engine' => 'tempest']],
        ]);

        self::assertStringContainsString('hl-keyword', $extension->highlight('<?php echo 1;', 'php'));
    }

    public function testHighlightUsesEngineArgument(): void
    {
        $extension = $this->createExtension(['layouts' => ['highlight' => ['engine' => 'tempest']]]);

        self::assertStringContainsString('hljs-keyword', $extension->highlight('<?php echo 1;', 'php', 'highlightjs'));
    }

    public function testHighlightReturnsEscapedCodeOnUnknownLanguage(): void
    {
        $extension = $this->createExtension();

        self::assertSame('a &lt; b', $extension->highlight('a < b', 'unknown-language'));
        self::assertSame('a &lt; b', $extension->highlight('a < b', 'unknown-language', 'tempest'));
    }

    public function testHighlightThrowsOnUnknownEngine(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Option "layouts.highlight.engine" must be one of');
        $this->createExtension(['layouts' => ['highlight' => ['engine' => 'unknown']]])->highlight('code', 'php');
    }

    public function testReadtimeReturnsOneMinuteMinimum(): void
    {
        $extension = $this->createExtension();

        self::assertSame('1', $extension->readtime(null));
        self::assertSame('1', $extension->readtime(''));
        self::assertSame('1', $extension->readtime('<p>Less than two hundred words.</p>'));
    }

    public function testReadtimeCountsTwoHundredWordsPerMinute(): void
    {
        $extension = $this->createExtension();

        self::assertSame('1', $extension->readtime(implode(' ', array_fill(0, 200, 'word'))));
        self::assertSame('2', $extension->readtime(implode(' ', array_fill(0, 400, 'word'))));
        self::assertSame('3', $extension->readtime(implode(' ', array_fill(0, 700, 'word'))));
    }

    public function testReadtimeIgnoresMarkup(): void
    {
        $extension = $this->createExtension();

        $text = str_repeat("<p><strong>word</strong> word</p>\n", 200); // 400 words

        self::assertSame('2', $extension->readtime($text));
    }
}
