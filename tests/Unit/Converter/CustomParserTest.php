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

namespace Cecil\Test\Unit\Converter;

use Cecil\Builder;
use Cecil\Converter\Parsedown;
use Cecil\Converter\ParserFactory;
use Cecil\Exception\RuntimeException;
use Cecil\Renderer\Extension\Content;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CustomParserTest extends TestCase
{
    public function testDefaultParser(): void
    {
        $builder = new Builder(['baseurl' => 'https://example.com/'], new NullLogger());

        self::assertSame(Parsedown::class, ParserFactory::create($builder)::class);
    }

    public function testInvalidParserIsRejected(): void
    {
        $builder = $this->createBuilder(\stdClass::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('`pages.body.parser`');

        ParserFactory::create($builder);
    }

    public function testCustomParserIsUsedByConverter(): void
    {
        $builder = $this->createBuilder(MarkParsedown::class);

        self::assertSame('<p>A <mark>marked</mark> text</p>', $builder->getConverter()->convertBody('A ==marked== text'));
    }

    public function testCustomParserIsUsedByTwigFilters(): void
    {
        $content = new Content($this->createBuilder(MarkParsedown::class));

        self::assertSame('<p><mark>marked</mark></p>', $content->markdownToHtml('==marked=='));
        // the table of contents contains the text of the parsed heading
        self::assertStringContainsString('>A marked title</a>', (string) $content->markdownToToc('## A ==marked== title', 'html', ['h2']));
    }

    private function createBuilder(string $parser): Builder
    {
        return new Builder(['baseurl' => 'https://example.com/', 'pages' => ['body' => ['parser' => $parser]]], new NullLogger());
    }
}

/**
 * Adds the `==text==` inline syntax, rendered as `<mark>text</mark>`.
 */
class MarkParsedown extends Parsedown
{
    public function __construct(Builder $builder, ?array $options = null)
    {
        parent::__construct($builder, $options);
        $this->InlineTypes['='][] = 'Mark';
        $this->inlineMarkerList .= '=';
    }

    /**
     * @param array<string, mixed> $Excerpt
     *
     * @return array<string, mixed>|null
     */
    protected function inlineMark($Excerpt)
    {
        if (preg_match('/^==(?=\S)(.+?)(?<=\S)==/', $Excerpt['text'], $matches)) {
            return [
                'extent'  => \strlen($matches[0]),
                'element' => ['name' => 'mark', 'text' => $matches[1], 'handler' => 'line'],
            ];
        }

        return null;
    }
}
