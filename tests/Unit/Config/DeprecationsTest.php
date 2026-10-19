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

namespace Cecil\Test\Unit\Config;

use Cecil\Config\Deprecations;
use Cecil\Exception\ConfigException;
use PHPUnit\Framework\TestCase;

class DeprecationsTest extends TestCase
{
    public function testValidConfigurationPasses(): void
    {
        Deprecations::check(['pages' => ['frontmatter' => 'yaml']], '"cecil.yml"');

        $this->addToAssertionCount(1);
    }

    public function testMovedOptionIsReportedWithNewSnippet(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage("Deprecated configuration option(s) in \"cecil.yml\":\n- Option `frontmatter` must be moved to:\n  ```\n  pages:\n    frontmatter: yaml\n  ```");

        Deprecations::check(['frontmatter' => 'yaml'], '"cecil.yml"');
    }

    public function testVirtualPagesListIsReportedAsDefaultPagesMap(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage("- Option `pages.virtual` must be moved to:\n  ```\n  pages:\n    default:\n      code:\n        path: code\n        redirect: 'https://github.com'\n      index:\n        path: ''\n        title: Home\n  ```");

        Deprecations::check(['pages' => ['virtual' => [
            ['path' => 'code', 'redirect' => 'https://github.com'],
            ['path' => '', 'title' => 'Home'],
        ]]]);
    }

    public function testRemovedOptionIsReported(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('- Option `cache.assets.dir` is deprecated and must be removed.');

        Deprecations::check(['cache' => ['assets' => ['dir' => 'assets']]]);
    }

    public function testEnvironmentVariablesAreReportedByName(): void
    {
        try {
            Deprecations::check(['pages.frontmatter.format' => 'yaml', 'cache.assets.dir' => 'x'], 'environment variables', true);
            self::fail('Expected deprecated variables exception.');
        } catch (ConfigException $e) {
            self::assertStringContainsString('Variable `CECIL_PAGES_FRONTMATTER_FORMAT` must be renamed to `CECIL_PAGES_FRONTMATTER`.', $e->getMessage());
            self::assertStringContainsString('Variable `CECIL_CACHE_ASSETS_DIR` is deprecated and must be removed.', $e->getMessage());
        }
    }
}
