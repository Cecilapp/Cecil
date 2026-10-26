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
use Cecil\Generator\AbstractGenerator;
use Cecil\Generator\GeneratorInterface;
use Cecil\Generator\Section;
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
        yield 'map indexed by position' => [[0 => ['class' => HtmlExcerpt::class]], 'extensions must be indexed by name'];
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
        $builder = new Builder(['extensions' => ['strict' => true], 'pages' => ['generators' => $generators]]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        (new Loader($builder))->getDefinitions('pages.generators', GeneratorInterface::class);
    }

    public function testInvalidEntryIsLoggedAndSkippedWhenNotStrict(): void
    {
        $logger = $this->createLogger();
        $builder = new Builder([
            'extensions' => ['strict' => false],
            'pages'      => ['generators' => ['Missing' => 'App\Generator\Missing']],
        ], $logger);
        $definitions = (new Loader($builder))->getDefinitions('pages.generators', GeneratorInterface::class);

        self::assertCount(8, $definitions);
        self::assertCount(1, $logger->messages['error']);
        self::assertStringContainsString('Unable to load extension "Missing" (pages.generators)', $logger->messages['error'][0]);
        self::assertStringNotContainsString('Cecil 10', $logger->messages['error'][0]);
    }

    public function testInvalidEntryIsLoggedWithWarningByDefault(): void
    {
        $logger = $this->createLogger();
        $builder = new Builder(['pages' => ['generators' => ['Missing' => 'App\Generator\Missing']]], $logger);
        $definitions = (new Loader($builder))->getDefinitions('pages.generators', GeneratorInterface::class);

        self::assertCount(8, $definitions);
        self::assertStringContainsString('this will fail the build in Cecil 10', $logger->messages['error'][0]);
    }

    public function testLegacyGeneratorIndexedByPriority(): void
    {
        $logger = $this->createLogger();
        $builder = new Builder(['pages' => ['generators' => [35 => LegacyGenerator::class]]], $logger);
        $definitions = (new Loader($builder))->getDefinitions('pages.generators', GeneratorInterface::class);

        self::assertContains(['name' => 'LegacyGenerator', 'class' => LegacyGenerator::class, 'priority' => 35], $definitions);
        self::assertSame('Section', $definitions[3]['name']);
        self::assertStringContainsString('use `LegacyGenerator: {class: ' . LegacyGenerator::class . ', priority: 35}`', $logger->messages['warning'][0]);
    }

    public function testLegacyGeneratorReplacesBuiltInWithSamePriority(): void
    {
        $builder = new Builder(['pages' => ['generators' => [40 => LegacyGenerator::class]]], $this->createLogger());
        $definitions = (new Loader($builder))->getDefinitions('pages.generators', GeneratorInterface::class);

        self::assertCount(8, $definitions);
        self::assertContains(['name' => 'Section', 'class' => LegacyGenerator::class, 'priority' => 40], $definitions);
    }

    public function testLegacyGeneratorDoesNotReplaceBuiltInWithSameName(): void
    {
        $builder = new Builder(['pages' => ['generators' => [99 => Section::class]]], $this->createLogger());
        $definitions = (new Loader($builder))->getDefinitions('pages.generators', GeneratorInterface::class);

        self::assertCount(9, $definitions);
        self::assertSame(['name' => 'Section-99', 'class' => Section::class, 'priority' => 99], end($definitions));
    }

    public function testLegacyListOfPostProcessors(): void
    {
        $logger = $this->createLogger();
        $builder = new Builder(['output' => ['postprocessors' => [MarkdownLink::class]]], $logger);
        $postprocessors = (new Loader($builder))->load('output.postprocessors', PostProcessorInterface::class);

        // as before, a list replaces the built-in post-processors (map) of the default configuration
        self::assertSame(['MarkdownLink-0'], array_keys($postprocessors));
        self::assertStringContainsString('is indexed by its position', $logger->messages['warning'][0]);
    }

    /**
     * Returns a logger collecting messages by level.
     *
     * @return AbstractLogger&object{messages: array<string, list<string>>}
     */
    private function createLogger(): AbstractLogger
    {
        return new class () extends AbstractLogger {
            /** @var array<string, list<string>> */
            public array $messages = ['error' => [], 'warning' => []];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->messages[(string) $level][] = (string) $message;
            }
        };
    }
}

class LegacyGenerator extends AbstractGenerator
{
    public function generate(): void
    {
    }
}
