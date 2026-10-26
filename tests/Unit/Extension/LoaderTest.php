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
use Cecil\Exception\RuntimeException;
use Cecil\Extension\Loader;
use Cecil\Generator\GeneratorInterface;
use Cecil\Renderer\PostProcessor\HtmlExcerpt;
use Cecil\Renderer\PostProcessor\MarkdownLink;
use Cecil\Renderer\PostProcessor\PostProcessorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

class LoaderTest extends TestCase
{
    public function testBuiltInGeneratorsAreSortedByPriority(): void
    {
        $definitions = (new Loader(new Builder()))->getDefinitions('pages.generators', GeneratorInterface::class);

        self::assertSame(
            ['DefaultPages', 'ExternalBody', 'Section', 'Taxonomy', 'Homepage', 'Pagination', 'Alias', 'Redirect'],
            array_column($definitions, 'name')
        );
    }

    public function testBuiltInGeneratorCanBeDisabledAndMoved(): void
    {
        $builder = new Builder(['pages' => ['generators' => [
            'Redirect' => false,
            'Alias'    => ['enabled' => false],
            'Section'  => ['priority' => 75],
        ]]]);
        $definitions = (new Loader($builder))->getDefinitions('pages.generators', GeneratorInterface::class);

        self::assertSame(
            ['DefaultPages', 'ExternalBody', 'Taxonomy', 'Homepage', 'Pagination', 'Section'],
            array_column($definitions, 'name')
        );
    }

    public function testDefaultPriorityKeepsDeclarationOrder(): void
    {
        $builder = new Builder(['output' => ['postprocessors' => [
            'GeneratorMetaTag' => false,
            'HtmlExcerpt'      => false,
            'MarkdownLink'     => false,
            'Second'           => MarkdownLink::class,
            'Third'            => '\\' . HtmlExcerpt::class,
            'First'            => ['class' => HtmlExcerpt::class, 'priority' => 50],
        ]]]);
        $postprocessors = (new Loader($builder))->load('output.postprocessors', PostProcessorInterface::class);

        self::assertSame(['First', 'Second', 'Third'], array_keys($postprocessors));
        self::assertInstanceOf(MarkdownLink::class, $postprocessors['Second']);
        self::assertInstanceOf(HtmlExcerpt::class, $postprocessors['Third']);
    }

    /**
     * @return iterable<string, array{0: mixed, 1: string}>
     */
    public static function invalidEntryProvider(): iterable
    {
        yield 'priority as key' => [[35 => 'App\Generator\Database'], '`Database: {class: App\Generator\Database, priority: 35}`'];
        yield 'class not found' => [['Missing' => 'App\Generator\Missing'], 'class "App\Generator\Missing" not found'];
        yield 'wrong interface' => [['Wrong' => HtmlExcerpt::class], 'must implement'];
        yield 'missing class' => [['NoClass' => ['priority' => 10]], 'option "class" is required'];
        yield 'invalid priority' => [['BadPriority' => ['class' => HtmlExcerpt::class, 'priority' => 'high']], 'option "priority" must be an integer'];
        yield 'unknown option' => [['Typo' => ['class' => HtmlExcerpt::class, 'priorty' => 10]], 'unknown option(s) "priorty"'];
        yield 'invalid entry' => [['Invalid' => 42], 'must be a class name'];
    }

    #[DataProvider('invalidEntryProvider')]
    public function testInvalidEntryFailsInStrictMode(mixed $generators, string $message): void
    {
        $builder = new Builder(['pages' => ['generators' => $generators]]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        (new Loader($builder))->getDefinitions('pages.generators', GeneratorInterface::class);
    }

    public function testInvalidEntryIsLoggedAndSkippedWhenNotStrict(): void
    {
        $logger = new class () extends AbstractLogger {
            /** @var list<string> */
            public array $errors = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                if ($level === 'error') {
                    $this->errors[] = (string) $message;
                }
            }
        };
        $builder = new Builder([
            'extensions' => ['strict' => false],
            'pages'      => ['generators' => ['Missing' => 'App\Generator\Missing']],
        ], $logger);
        $definitions = (new Loader($builder))->getDefinitions('pages.generators', GeneratorInterface::class);

        self::assertCount(8, $definitions);
        self::assertCount(1, $logger->errors);
        self::assertStringContainsString('Unable to load extension "Missing" (pages.generators)', $logger->errors[0]);
    }
}
