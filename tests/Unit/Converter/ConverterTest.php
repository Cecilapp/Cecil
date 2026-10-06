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
use Cecil\Converter\Converter;
use Cecil\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ConverterTest extends TestCase
{
    private Converter $converter;

    protected function setUp(): void
    {
        $this->converter = new Converter(new Builder(['baseurl' => 'https://example.com/'], new NullLogger()));
    }

    public static function validFrontmatterProvider(): iterable
    {
        yield 'yaml' => ['yaml', "title: Hello\ntags: [a, b]"];
        yield 'ini' => ['ini', "title = Hello\ntags[] = a\ntags[] = b"];
        yield 'toml' => ['toml', "title = \"Hello\"\ntags = [\"a\", \"b\"]"];
        yield 'json' => ['json', '{"title": "Hello", "tags": ["a", "b"]}'];
    }

    #[DataProvider('validFrontmatterProvider')]
    public function testConvertValidFrontmatter(string $format, string $frontmatter): void
    {
        self::assertSame(['title' => 'Hello', 'tags' => ['a', 'b']], $this->converter->convertFrontmatter($frontmatter, $format));
    }

    public function testYamlIsTheDefaultFormat(): void
    {
        self::assertSame(['title' => 'Hello'], $this->converter->convertFrontmatter('title: Hello'));
    }

    public function testYamlDatesAreParsed(): void
    {
        $result = $this->converter->convertFrontmatter('date: 2024-01-31 12:00:00', 'yaml');

        self::assertInstanceOf(\DateTimeInterface::class, $result['date']);
    }

    public static function emptyFrontmatterProvider(): iterable
    {
        yield 'yaml' => ['yaml'];
        yield 'ini' => ['ini'];
        yield 'toml' => ['toml'];
    }

    #[DataProvider('emptyFrontmatterProvider')]
    public function testEmptyFrontmatterIsAnEmptyArray(string $format): void
    {
        self::assertSame([], $this->converter->convertFrontmatter('', $format));
    }

    public function testUnsupportedFormatThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The front matter format "xml" is not supported ("yaml", "ini", "toml" or "json").');

        $this->converter->convertFrontmatter('<title>Hello</title>', 'xml');
    }

    public function testInvalidYamlThrowsExceptionWithLine(): void
    {
        try {
            $this->converter->convertFrontmatter("title: Hello\nbad: [unclosed", 'yaml');
            self::fail('An exception should have been thrown.');
        } catch (RuntimeException $e) {
            self::assertGreaterThan(0, $e->getLine());
            self::assertNotSame('', $e->getMessage());
        }
    }

    public function testScalarYamlThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to parse YAML front matter.');

        $this->converter->convertFrontmatter('just a string', 'yaml');
    }

    public function testInvalidIniThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to parse INI front matter.');

        // parse_ini_string() emits a warning on syntax error
        set_error_handler(static fn (): bool => true, E_WARNING);
        try {
            $this->converter->convertFrontmatter('title = (', 'ini');
        } finally {
            restore_error_handler();
        }
    }

    public function testInvalidTomlThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('at line 2');

        $this->converter->convertFrontmatter("title = \"Hello\"\ntags = [", 'toml');
    }

    public static function invalidJsonProvider(): iterable
    {
        yield 'syntax error' => ['{"title": '];
        yield 'empty' => [''];
        yield 'null' => ['null'];
        yield 'scalar' => ['"just a string"'];
    }

    #[DataProvider('invalidJsonProvider')]
    public function testInvalidJsonThrowsException(string $frontmatter): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to parse JSON front matter.');

        $this->converter->convertFrontmatter($frontmatter, 'json');
    }

    public function testConvertBodyRendersMarkdownToHtml(): void
    {
        // the build ID is normally set by Builder::build()
        $setBuildId = \Closure::bind(static function (?string $id): void {
            self::$buildId = $id;
        }, null, Builder::class);
        $setBuildId('test');
        try {
            $html = $this->converter->convertBody("Some **bold** text.\n\n- item", 'en');
        } finally {
            $setBuildId(null);
        }

        self::assertStringContainsString('<strong>bold</strong>', $html);
        self::assertStringContainsString('<li>item</li>', $html);
    }

    public function testNoteKeepsIndentationAndBlankLines(): void
    {
        // the build ID is normally set by Builder::build()
        $setBuildId = \Closure::bind(static function (?string $id): void {
            self::$buildId = $id;
        }, null, Builder::class);
        $setBuildId('test');
        try {
            $html = $this->converter->convertBody(":::tip\nFirst paragraph.\n\nSecond paragraph.\n\n```yaml\npages:\n  default:\n    published: true\n```\n:::", 'en');
        } finally {
            $setBuildId(null);
        }

        self::assertStringContainsString('<p>First paragraph.</p>', $html);
        self::assertStringContainsString('<p>Second paragraph.</p>', $html);
        self::assertStringContainsString("pages:\n  default:\n    published: true", strip_tags($html));
    }
}
