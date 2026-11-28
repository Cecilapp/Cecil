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

namespace Cecil\Test\Unit\Generator;

use Cecil\Builder;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Generator\ExternalBody;
use Cecil\Logger\PrintLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ExternalBodyTest extends TestCase
{
    private string $root;

    private string $siteDir;

    private Filesystem $filesystem;

    private Builder $builder;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        // root/                  <- outside the allowed directory
        // root/repo/             <- allowed directory (parent of the site)
        // root/repo/site/        <- site directory
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-external-test-' . uniqid('', true);
        $this->siteDir = $this->root . DIRECTORY_SEPARATOR . 'repo' . DIRECTORY_SEPARATOR . 'site';
        $this->filesystem->mkdir($this->siteDir);
        $this->filesystem->dumpFile($this->siteDir . '/content/included.md', 'Included');
        $this->filesystem->dumpFile($this->root . '/repo/README.md', 'Readme');
        $this->filesystem->dumpFile($this->root . '/secret.txt', 'Secret');

        $this->builder = new Builder([], new PrintLogger(Builder::VERBOSITY_QUIET));
        $this->builder->setSourceDir($this->siteDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    public function testLocalFileInsideSiteIsRead(): void
    {
        $generated = $this->generate('content/included.md');

        self::assertTrue($generated->has('external'));
        self::assertStringContainsString('Included', (string) $generated->get('external')->getBodyHtml());
    }

    public function testLocalFileInParentOfSiteIsRead(): void
    {
        $generated = $this->generate('../README.md');

        self::assertTrue($generated->has('external'));
        self::assertStringContainsString('Readme', (string) $generated->get('external')->getBodyHtml());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function forbiddenSourceProvider(): array
    {
        return [
            'relative path outside the allowed directory' => ['../../secret.txt'],
            'file wrapper' => ['file:///etc/passwd'],
            'php wrapper' => ['php://filter/convert.base64-encode/resource=../../secret.txt'],
            'data wrapper' => ['data:text/plain,Injected'],
            'phar wrapper' => ['phar://archive.phar/file.md'],
            'missing file' => ['missing.md'],
            'directory' => ['content'],
        ];
    }

    #[DataProvider('forbiddenSourceProvider')]
    public function testForbiddenSourceIsNotRead(string $external): void
    {
        self::assertFalse($this->generate($external)->has('external'));
    }

    public function testAbsolutePathOutsideTheAllowedDirectoryIsNotRead(): void
    {
        self::assertFalse($this->generate($this->root . DIRECTORY_SEPARATOR . 'secret.txt')->has('external'));
    }

    private function generate(string $external): PagesCollection
    {
        $page = (new Page('external'))
            ->setVariable('external', $external)
            ->setVariable('language', $this->builder->getConfig()->getLanguageDefault());
        $this->builder->setPages(new PagesCollection('all-pages', [$page]));

        return (new ExternalBody($this->builder))->runGenerate();
    }
}
