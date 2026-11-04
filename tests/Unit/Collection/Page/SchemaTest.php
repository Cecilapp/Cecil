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

use Cecil\Collection\Page\Schema;
use Cecil\Exception\FrontmatterException;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    private function assertInvalid(Schema $schema, array $variables, ?string $section, string $expected): void
    {
        try {
            $schema->validate($variables, $section);
            self::fail('Expected front matter exception.');
        } catch (FrontmatterException $e) {
            self::assertStringContainsString($expected, $e->getMessage());
        }
    }

    public function testBuiltinSchemaAcceptsPredefinedVariables(): void
    {
        $variables = [
            'title'     => 'Title',
            'date'      => new \DateTimeImmutable('2024-01-01'),
            'updated'   => '2024-02-01',
            'weight'    => 10,
            'language'  => 'fr',
            'published' => true,
            'draft'     => 'false',
            'excluded'  => '1', // INI front matter
            'menu'      => ['main' => ['weight' => 10, 'name' => 'Home'], 'footer' => null],
            'schedule'  => ['publish' => '2024-01-01', 'expiry' => '2030-01-01'],
            'alias'     => ['old-path'],
            'output'    => 'html',
            'redirect'  => 'https://cecil.app',
            'custom'    => ['any' => 'value'],
        ];

        self::assertSame($variables, (new Schema())->validate($variables));
    }

    public function testBuiltinSchemaAcceptsMenuShorthands(): void
    {
        $schema = new Schema();

        self::assertSame(['menu' => 'main'], $schema->validate(['menu' => 'main']));
        self::assertSame(['menu' => ['main', 'footer']], $schema->validate(['menu' => ['main', 'footer']]));
    }

    public function testBuiltinSchemaRejectsInvalidDate(): void
    {
        $this->assertInvalid(new Schema(), ['date' => 'not a date'], null, "The variable 'date' expects to be a valid date.");
    }

    public function testBuiltinSchemaRejectsInvalidSchedule(): void
    {
        $this->assertInvalid(new Schema(), ['schedule' => '2024-01-01'], null, "'schedule'");
    }

    public function testBuiltinSchemaRejectsInvalidBoolean(): void
    {
        $this->assertInvalid(new Schema(), ['draft' => 'maybe'], null, "The variable 'draft' expects to be a boolean.");
    }

    public function testBuiltinSchemaRejectsInvalidOutput(): void
    {
        $this->assertInvalid(new Schema(), ['output' => ['html' => true]], null, "'output");
    }

    public function testUserSchemaOfAllPagesIsApplied(): void
    {
        $schema = new Schema(['*' => ['title' => ['type' => 'string', 'required' => true]]]);

        $this->assertInvalid($schema, ['description' => 'Desc'], 'blog', "The mandatory variable 'title' is missing.");
        self::assertSame(['title' => 'Title'], $schema->validate(['title' => 'Title']));
    }

    public function testUserSchemaIsAppliedBySection(): void
    {
        $schema = new Schema(['blog' => ['author' => ['type' => 'string', 'required' => true]]]);

        $this->assertInvalid($schema, ['title' => 'Post'], 'blog', "'author' is missing");
        self::assertSame(['title' => 'Page'], $schema->validate(['title' => 'Page'], 'docs'));
        self::assertSame(['title' => 'Page'], $schema->validate(['title' => 'Page']));
    }

    public function testSectionFieldsOverrideFieldsOfAllPages(): void
    {
        $schema = new Schema([
            '*'    => ['subtitle' => ['type' => 'string', 'required' => true]],
            'blog' => ['subtitle' => 'string'],
        ]);

        self::assertSame([], $schema->validate([], 'blog'));
        $this->assertInvalid($schema, [], 'docs', "'subtitle' is missing");
    }

    public function testUserSchemaTypeShorthand(): void
    {
        $schema = new Schema(['blog' => ['author' => 'string', 'rating' => 'int']]);

        $this->assertInvalid($schema, ['author' => 42], 'blog', "The variable 'author' expects to be null or string, 42 given.");
        $this->assertInvalid($schema, ['rating' => '5'], 'blog', "'rating'");
        self::assertSame(['author' => null], $schema->validate(['author' => null], 'blog'));
    }

    public function testUserSchemaRules(): void
    {
        $schema = new Schema(['blog' => [
            'rating'   => ['type' => 'int', 'min' => 1, 'max' => 5],
            'status'   => ['type' => 'string', 'enum' => ['draft', 'review']],
            'isbn'     => ['type' => 'string', 'pattern' => '\d{13}'],
            'tags'     => ['type' => 'list', 'items' => 'string', 'max' => 2],
            'released' => 'date',
        ]]);

        $this->assertInvalid($schema, ['rating' => 9], 'blog', "The variable 'rating' expects to be in range 1..5, 9 given.");
        $this->assertInvalid($schema, ['status' => 'published'], 'blog', "The variable 'status' expects to be one of 'draft', 'review'.");
        $this->assertInvalid($schema, ['isbn' => '123'], 'blog', "'isbn'");
        $this->assertInvalid($schema, ['tags' => ['a', 'b', 'c']], 'blog', "'tags'");
        $this->assertInvalid($schema, ['tags' => [1]], 'blog', "'tags\u{a0}›\u{a0}0'");
        $this->assertInvalid($schema, ['released' => 'someday'], 'blog', "'released'");

        $valid = ['rating' => 4, 'status' => 'review', 'isbn' => '9782070612758', 'tags' => ['php'], 'released' => '2024-01-01'];
        self::assertSame($valid, $schema->validate($valid, 'blog'));
    }

    public function testUserSchemaDefaultValues(): void
    {
        $schema = new Schema(['blog' => ['status' => ['type' => 'string', 'default' => 'draft']]]);

        self::assertSame(['title' => 'Post', 'status' => 'draft'], $schema->validate(['title' => 'Post'], 'blog'));
        self::assertSame(['status' => 'review'], $schema->validate(['status' => 'review'], 'blog'));
        self::assertSame([], $schema->validate([], 'docs'));
    }

    public function testAllErrorsAreReported(): void
    {
        $schema = new Schema(['blog' => ['author' => ['type' => 'string', 'required' => true]]]);

        try {
            $schema->validate(['date' => 'not a date'], 'blog');
            self::fail('Expected front matter exception.');
        } catch (FrontmatterException $e) {
            self::assertStringContainsString("'date'", $e->getMessage());
            self::assertStringContainsString("'author'", $e->getMessage());
        }
    }
}
