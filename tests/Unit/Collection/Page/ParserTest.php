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

namespace Cecil\Test\Unit\Collection\Page;

use Cecil\Collection\Page\Parser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class ParserTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-parser-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testParseWithFrontmatterAndBody(): void
    {
        $file = $this->createFileInfo('with-frontmatter.md', "---\ntitle: Parsed\n---\nBody");

        $parser = (new Parser($file))->parse();

        self::assertSame('title: Parsed', $parser->getFrontmatter());
        self::assertSame('Body', $parser->getBody());
    }

    public function testParseWithoutFrontmatterSetsBodyOnly(): void
    {
        $file = $this->createFileInfo('without-frontmatter.md', "Plain body");

        $parser = (new Parser($file))->parse();

        self::assertNull($parser->getFrontmatter());
        self::assertSame('Plain body', $parser->getBody());
    }

    private function createFileInfo(string $filename, string $content): SplFileInfo
    {
        $path = $this->tmpDir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($path, $content);

        return new SplFileInfo($path, '', $filename);
    }
}
