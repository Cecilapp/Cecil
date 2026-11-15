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

namespace Cecil\Test\Unit\Command\Build;

use Cecil\Builder;
use Cecil\Command\Build\IncrementalBuildResolver;
use Cecil\Util\FileWatcherResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class IncrementalBuildResolverTest extends TestCase
{
    private string $sourceDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->sourceDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-incremental-resolver-test-' . uniqid('', true);
        $this->filesystem->dumpFile($this->sourceDir . '/pages/blog/post.md', "---\ntitle: Post\n---\nBody");
        $this->filesystem->dumpFile($this->sourceDir . '/schemas/blog.yml', "author: string\n");
        $this->filesystem->dumpFile($this->sourceDir . '/themes/base/schemas/blog.yml', "author: string\n");
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->sourceDir);
    }

    public function testChangedPageIsRebuiltAlone(): void
    {
        self::assertSame(['blog/post.md'], $this->resolve([$this->sourceDir . '/pages/blog/post.md']));
    }

    public function testChangedSchemaFileRequiresAFullRebuild(): void
    {
        // the configuration is loaded again by a full build
        self::assertNull($this->resolve([$this->sourceDir . '/pages/blog/post.md', $this->sourceDir . '/schemas/blog.yml']));
        self::assertNull($this->resolve([$this->sourceDir . '/themes/base/schemas/blog.yml']));
    }

    /**
     * @param array<int, string> $updated
     *
     * @return array<int, string>|null
     */
    private function resolve(array $updated): ?array
    {
        $builder = (new Builder(['theme' => 'base']))->setSourceDir($this->sourceDir);

        return (new IncrementalBuildResolver($builder))->resolve(new FileWatcherResult([], $updated));
    }
}
