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

namespace Cecil\Test\Unit\Step\Pages;

use Cecil\Builder;
use Cecil\Exception\FrontmatterException;
use Cecil\Step\Pages\Convert;
use Cecil\Step\Pages\Create;
use Cecil\Step\Pages\Load;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\Filesystem\Filesystem;

class ConvertTest extends TestCase
{
    private string $root;

    private string $sourceDir;

    private Filesystem $filesystem;

    /** @var array<int, array{string, string}> */
    private array $logs = [];

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-pages-convert-test-' . uniqid('', true);
        $this->sourceDir = $this->root . DIRECTORY_SEPARATOR . 'source';
        $pagesDir = $this->sourceDir . DIRECTORY_SEPARATOR . 'pages';
        $this->filesystem->dumpFile($pagesDir . DIRECTORY_SEPARATOR . 'blog' . DIRECTORY_SEPARATOR . 'post.md', "---\ntitle: Post\n---\nBody");
        $this->filesystem->dumpFile($pagesDir . DIRECTORY_SEPARATOR . 'about.md', "---\ntitle: About\n---\nBody");
        $this->filesystem->mkdir($this->root . DIRECTORY_SEPARATOR . 'destination');
        $this->logs = [];
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    public function testInvalidPageIsSkippedByDefault(): void
    {
        $builder = $this->convert([]);

        self::assertFalse($builder->getPages()->has('blog/post'));
        self::assertTrue($builder->getPages()->has('about'));
        self::assertNotEmpty($this->logsOf('error', 'Invalid front matter of "blog/post.md"'));
    }

    public function testInvalidPageIsKeptWithWarningMode(): void
    {
        $builder = $this->convert(['validation' => 'warning']);

        self::assertTrue($builder->getPages()->has('blog/post'));
        self::assertNotEmpty($this->logsOf('warning', "'author' is missing"));
    }

    public function testBuildFailsWithStrictMode(): void
    {
        $this->expectException(FrontmatterException::class);
        $this->expectExceptionMessage("'author' is missing");

        $this->convert(['validation' => 'strict']);
    }

    public function testValidationCanBeDisabled(): void
    {
        $builder = $this->convert(['validation' => false]);

        self::assertTrue($builder->getPages()->has('blog/post'));
        self::assertEmpty($this->logsOf('warning', 'Invalid front matter'));
        self::assertEmpty($this->logsOf('error', 'Invalid front matter'));
    }

    public function testDefaultValuesAreApplied(): void
    {
        $builder = $this->convert(['schema' => ['blog' => ['stage' => ['type' => 'string', 'default' => 'draft']]]]);

        self::assertSame('draft', $builder->getPages()->get('blog/post')->getVariable('stage'));
        self::assertNull($builder->getPages()->get('about')->getVariable('stage'));
    }

    /**
     * Runs pages Load, Create and Convert steps with the given `pages` options.
     *
     * @param array<string, mixed> $pages
     */
    private function convert(array $pages): Builder
    {
        $logs = &$this->logs;
        $logger = new class ($logs) extends AbstractLogger {
            /** @param array<int, array{string, string}> $logs */
            public function __construct(private array &$logs)
            {
            }

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->logs[] = [(string) $level, (string) $message];
            }
        };
        $pages += ['schema' => ['blog' => ['author' => ['type' => 'string', 'required' => true]]]];
        $builder = new Builder(['baseurl' => 'https://example.com/', 'pages' => $pages], $logger);
        $builder->setSourceDir($this->sourceDir);
        $builder->setDestinationDir($this->root . DIRECTORY_SEPARATOR . 'destination');

        foreach ([Load::class, Create::class, Convert::class] as $class) {
            $step = new $class($builder);
            $step->init(['drafts' => false]);
            if ($step->canProcess()) {
                $step->process();
            }
        }

        return $builder;
    }

    /**
     * @return array<int, array{string, string}>
     */
    private function logsOf(string $level, string $needle): array
    {
        return array_filter($this->logs, fn ($log) => $log[0] === $level && str_contains($log[1], $needle));
    }
}
