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

use Cecil\Config;
use Cecil\Config\SchemaLoader;
use Cecil\Exception\ConfigException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class SchemaLoaderTest extends TestCase
{
    private string $sourceDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->sourceDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-schema-loader-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->sourceDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->sourceDir);
    }

    public function testWithoutSchemasDirectoryTheConfigurationIsUnchanged(): void
    {
        $config = $this->config(['pages' => ['schema' => ['blog' => ['author' => 'string']]]]);

        self::assertSame(['blog' => ['author' => 'string']], $config->get('pages.schema'));
        self::assertNull($this->config()->get('pages.schema'));
    }

    public function testFilesAreLoadedByKey(): void
    {
        $this->dump('schemas/_all.yml', "title: {type: string, required: true}\n");
        $this->dump('schemas/blog.yml', "author: string\n");
        $this->dump('schemas/blog/index.yml', "intro: string\n");
        $this->dump('schemas/blog/post-1.yaml', "cover: string\n");
        $this->dump('schemas/cv.yml', "sections:\n  type: list\n  items:\n    type: map\n    fields:\n      title: {type: string, required: true}\n");

        $schemas = $this->config()->get('pages.schema');

        self::assertSame(['*', 'blog', 'blog/index', 'blog/post-1', 'cv'], array_keys($schemas));
        self::assertSame(['title' => ['type' => 'string', 'required' => true]], $schemas['*']);
        self::assertSame(['author' => 'string'], $schemas['blog']);
        self::assertSame(['intro' => 'string'], $schemas['blog/index']);
        self::assertSame(['cover' => 'string'], $schemas['blog/post-1']);
        self::assertSame('map', $schemas['cv']['sections']['items']['type']);
    }

    public function testOtherFilesAreIgnored(): void
    {
        $this->dump('schemas/blog.yml', "author: string\n");
        $this->dump('schemas/README.md', "# Schemas\n");

        self::assertSame(['blog' => ['author' => 'string']], $this->config()->get('pages.schema'));
    }

    public function testEmptyFileIsAnEmptySchema(): void
    {
        $this->dump('schemas/blog.yml', '');

        self::assertSame(['blog' => []], $this->config()->get('pages.schema'));
    }

    public function testSameKeyInYmlAndYamlFilesIsRejected(): void
    {
        $this->dump('schemas/blog.yml', "author: string\n");
        $this->dump('schemas/blog.yaml', "author: string\n");

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Schema files "schemas/blog.yaml" and "schemas/blog.yml" have the same key "blog"');

        $this->config();
    }

    public function testInvalidYamlNamesTheFile(): void
    {
        $this->dump('schemas/blog.yml', "author: [unterminated\n");

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Schema file "schemas/blog.yml" parsing error');

        $this->config();
    }

    public function testInvalidDefinitionNamesTheFileAndTheField(): void
    {
        $this->dump('schemas/cv.yml', "sections:\n  type: list\n  items:\n    type: map\n    fields:\n      title: {type: text}\n");

        try {
            $this->config();
            self::fail('An invalid schema file must be rejected.');
        } catch (ConfigException $e) {
            self::assertStringContainsString('Invalid schema file "schemas/cv.yml"', $e->getMessage());
            self::assertStringContainsString("'sections\u{a0}›\u{a0}items\u{a0}›\u{a0}fields\u{a0}›\u{a0}title", $e->getMessage());
            self::assertStringNotContainsString('pages', $e->getMessage());
        }
    }

    public function testFailedAssertionNamesTheFileAndTheField(): void
    {
        $this->dump('schemas/blog/index.yml', "intro: {type: int, format: markdown}\n");

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage("Invalid schema file \"schemas/blog/index.yml\":\nInvalid 'intro': the `format` rule expects the `string` type");

        $this->config();
    }

    public function testFileMustHoldAMapOfFields(): void
    {
        $this->dump('schemas/blog.yml', "- author\n- tags\n");

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Invalid schema file "schemas/blog.yml"');

        $this->config();
    }

    public function testConfigurationOverridesFilesFieldByField(): void
    {
        $this->dump('schemas/blog.yml', "author: {type: string, required: true}\ntags: {type: list, items: string}\n");

        $schemas = $this->config(['pages' => ['schema' => ['blog' => ['author' => ['type' => 'string', 'label' => 'Author']]]]])->get('pages.schema');

        // the definition of a field is replaced as a whole, the other fields are kept
        self::assertSame(['type' => 'string', 'label' => 'Author'], $schemas['blog']['author']);
        self::assertSame(['type' => 'list', 'items' => 'string'], $schemas['blog']['tags']);
    }

    public function testThemesSchemasArePrioritized(): void
    {
        $this->dump('themes/base/schemas/blog.yml', "author: string\ntags: {type: list, enum: [a, b]}\ncover: string\nrating: int\nstage: string\n");
        $this->dump('themes/child/schemas/blog.yml', "cover: {type: string, format: path}\n");
        $this->dump('themes/child/config.yml', "pages:\n  schema:\n    blog:\n      tags: {type: list, enum: [c]}\n      rating: number\n");
        $this->dump('schemas/blog.yml', "rating: {type: int, min: 0}\nstage: {type: string, enum: [draft]}\n");

        $config = $this->config(['theme' => ['child', 'base'], 'pages' => ['schema' => ['blog' => ['stage' => 'string']]]]);
        $config->import(Config::loadFile($this->sourceDir . '/themes/child/config.yml'), Config::IMPORT_PRESERVE);
        $schema = $config->get('pages.schema.blog');

        self::assertSame('string', $schema['author']);                                // themes/base/schemas
        self::assertSame(['type' => 'string', 'format' => 'path'], $schema['cover']); // themes/child/schemas overrides themes/base/schemas
        self::assertSame(['type' => 'list', 'enum' => ['c']], $schema['tags']);       // themes configuration overrides themes schemas
        self::assertSame(['type' => 'int', 'min' => 0], $schema['rating']);           // site schemas override themes configuration
        self::assertSame('string', $schema['stage']);                                 // site configuration overrides site schemas
    }

    public function testThemeSchemaErrorNamesTheThemeFile(): void
    {
        $this->dump('themes/base/schemas/blog.yml', "author: text\n");

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Invalid schema file "themes/base/schemas/blog.yml"');

        $this->config(['theme' => 'base']);
    }

    public function testMergeReplacesDefinitionsOfFields(): void
    {
        self::assertSame(
            ['blog' => ['author' => ['type' => 'string'], 'tags' => 'list'], '*' => ['title' => 'string']],
            SchemaLoader::merge(
                ['blog' => ['author' => ['type' => 'string', 'required' => true], 'tags' => 'list']],
                ['*' => ['title' => 'string'], 'blog' => ['author' => ['type' => 'string']]],
            )
        );
    }

    public function testLoadReturnsAnEmptyArrayWithoutDirectory(): void
    {
        self::assertSame([], SchemaLoader::load($this->sourceDir . '/schemas'));
    }

    /**
     * Returns a configuration of the source directory.
     *
     * @param array<string, mixed> $config
     */
    private function config(array $config = []): Config
    {
        return (new Config($config))->setSourceDir($this->sourceDir);
    }

    private function dump(string $file, string $content): void
    {
        $this->filesystem->dumpFile($this->sourceDir . '/' . $file, $content);
    }
}
