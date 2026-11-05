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

    /**
     * Schema of the illustrations section: a list of images (maps with a file name and an alternative text).
     */
    private function illustrationsSchema(): Schema
    {
        return new Schema([
            '*'             => ['title' => ['type' => 'string', 'required' => true]],
            'illustrations' => [
                'description' => ['type' => 'string', 'required' => true],
                'images'      => [
                    'type'  => 'list',
                    'min'   => 1,
                    'items' => [
                        'type'   => 'map',
                        'fields' => [
                            'file' => ['type' => 'string', 'required' => true, 'pattern' => '^[\w-]+\.(png|jpe?g|gif|webp)$'],
                            'alt'  => ['type' => 'string', 'required' => true, 'min' => 20],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Returns the front matter of an illustration page, with the given second image.
     *
     * @return array<string, mixed>
     */
    private function illustration(mixed $image): array
    {
        return [
            'title'       => 'Abura sumashi • 油すまし',
            'description' => "Peut-être qu'en vous promenant sur un petit chemin montagneux…",
            'date'        => '2026-08-20',
            'images'      => [
                ['file' => '01.png', 'alt' => "L'abura sumashi, yōkai à tête de pomme de terre vêtu d'un mino de paille, traverse une mare sous la pluie."],
                $image,
            ],
        ];
    }

    public function testListOfMapsIsValid(): void
    {
        $schema = $this->illustrationsSchema();
        $image = ['file' => '02.png', 'alt' => "Vue rapprochée de l'abura sumashi souriant, les yeux fermés, qui enjambe les pierres de la mare."];

        self::assertSame($this->illustration($image), $schema->validate($this->illustration($image), 'illustrations'));
        // undeclared keys of items are allowed (open structure)
        $image['credit'] = 'Cécile';
        self::assertSame($this->illustration($image), $schema->validate($this->illustration($image), 'illustrations'));
    }

    public function testListOfMapsRejectsInvalidItems(): void
    {
        $schema = $this->illustrationsSchema();
        $alt = 'Une description suffisamment longue.';

        $this->assertInvalid($schema, $this->illustration(['file' => '02.png']), 'illustrations', "The mandatory variable 'images\u{a0}›\u{a0}1\u{a0}›\u{a0}alt' is missing.");
        $this->assertInvalid($schema, $this->illustration(['file' => '02.png', 'alt' => '']), 'illustrations', "The length of variable 'images\u{a0}›\u{a0}1\u{a0}›\u{a0}alt' expects to be in range 20..");
        $this->assertInvalid($schema, $this->illustration(['file' => '02.png', 'alt' => 'Trop court']), 'illustrations', "The length of variable 'images\u{a0}›\u{a0}1\u{a0}›\u{a0}alt' expects to be in range 20..");
        $this->assertInvalid($schema, $this->illustration(['file' => 'images/illustrations/01.png', 'alt' => $alt]), 'illustrations', "The variable 'images\u{a0}›\u{a0}1\u{a0}›\u{a0}file' expects to match pattern");
        $this->assertInvalid($schema, $this->illustration('01.png'), 'illustrations', "The variable 'images\u{a0}›\u{a0}1' expects to be array, '01.png' given.");
        $this->assertInvalid($schema, $this->illustration(null), 'illustrations', "The variable 'images\u{a0}›\u{a0}1' expects to be array, null given.");
        $this->assertInvalid($schema, ['images' => []] + $this->illustration(null), 'illustrations', "The length of variable 'images' expects to be in range 1.., 0 items given.");
    }

    public function testListItemsDefinitionRulesApplyToItems(): void
    {
        $schema = new Schema(['*' => [
            // rules of the list apply to the count of items, rules of `items` apply to each item
            'tags'    => ['type' => 'list', 'max' => 2, 'items' => ['type' => 'string', 'min' => 3, 'enum' => ['php', 'twig', 'yaml']]],
            'ratings' => ['type' => 'list', 'items' => ['type' => 'int', 'max' => 5]],
            'matrix'  => ['type' => 'list', 'items' => ['type' => 'list', 'items' => 'int']],
        ]]);

        $valid = ['tags' => ['php', 'twig'], 'ratings' => [1, 5], 'matrix' => [[1, 2], [3]]];
        self::assertSame($valid, $schema->validate($valid));
        $this->assertInvalid($schema, ['tags' => ['php', 'twig', 'yaml']], null, "The length of variable 'tags' expects to be in range ..2, 3 items given.");
        $this->assertInvalid($schema, ['tags' => ['css']], null, "The variable 'tags\u{a0}›\u{a0}0' expects to be one of 'php', 'twig', 'yaml'.");
        $this->assertInvalid($schema, ['ratings' => [3, 9]], null, "The variable 'ratings\u{a0}›\u{a0}1' expects to be in range ..5, 9 given.");
        $this->assertInvalid($schema, ['ratings' => [null]], null, "'ratings\u{a0}›\u{a0}0'");
        $this->assertInvalid($schema, ['matrix' => [[1], ['a']]], null, "'matrix\u{a0}›\u{a0}1\u{a0}›\u{a0}0'");
    }

    public function testNestedMaps(): void
    {
        $schema = new Schema(['blog' => [
            'author' => [
                'type'     => 'map',
                'required' => true,
                'fields'   => [
                    'name'    => ['type' => 'string', 'required' => true],
                    'contact' => ['type' => 'map', 'fields' => ['email' => ['type' => 'string', 'pattern' => '.+@.+']]],
                    'links'   => ['type' => 'list', 'items' => ['type' => 'map', 'fields' => ['url' => ['type' => 'string', 'required' => true]]]],
                ],
            ],
        ]]);

        $valid = ['author' => ['name' => 'Cécile', 'contact' => ['email' => 'cecile@example.com'], 'links' => [['url' => 'https://example.com']], 'bio' => 'Illustratrice']];
        self::assertSame($valid, $schema->validate($valid, 'blog'));
        // optional nested fields can be empty
        self::assertSame(['author' => ['name' => 'Cécile', 'contact' => null]], $schema->validate(['author' => ['name' => 'Cécile', 'contact' => null]], 'blog'));

        $this->assertInvalid($schema, [], 'blog', "The mandatory variable 'author' is missing.");
        $this->assertInvalid($schema, ['author' => null], 'blog', "The variable 'author' expects to be array, null given.");
        $this->assertInvalid($schema, ['author' => 'Cécile'], 'blog', "'author'");
        $this->assertInvalid($schema, ['author' => []], 'blog', "The mandatory variable 'author\u{a0}›\u{a0}name' is missing.");
        $this->assertInvalid($schema, ['author' => ['name' => 'C', 'contact' => ['email' => 'nope']]], 'blog', "The variable 'author\u{a0}›\u{a0}contact\u{a0}›\u{a0}email' expects to match pattern");
        $this->assertInvalid($schema, ['author' => ['name' => 'C', 'links' => [['url' => 'a'], ['title' => 'b']]]], 'blog', "The mandatory variable 'author\u{a0}›\u{a0}links\u{a0}›\u{a0}1\u{a0}›\u{a0}url' is missing.");
    }

    public function testNestedDefaultValues(): void
    {
        $schema = new Schema(['*' => [
            'author' => ['type' => 'map', 'fields' => [
                'role'  => ['type' => 'string', 'default' => 'writer'],
                'links' => ['type' => 'list', 'items' => ['type' => 'map', 'fields' => ['rel' => ['type' => 'string', 'default' => 'me']]]],
            ]],
        ]]);

        self::assertSame(
            ['author' => ['links' => [['url' => 'a', 'rel' => 'me'], ['rel' => 'alt']], 'role' => 'writer']],
            $schema->validate(['author' => ['links' => [['url' => 'a'], ['rel' => 'alt']]]])
        );
        self::assertSame(['author' => ['role' => 'editor']], $schema->validate(['author' => ['role' => 'editor']]));
        // a missing or empty map is not created
        self::assertSame([], $schema->validate([]));
        self::assertSame(['author' => null], $schema->validate(['author' => null]));
    }

    public function testMapWithoutFieldsIsUnchanged(): void
    {
        $schema = new Schema(['*' => [
            'meta'   => 'map',
            'social' => ['type' => 'map', 'max' => 1],
            'images' => ['type' => 'list', 'items' => 'map'],
        ]]);

        $valid = ['meta' => ['any' => ['thing']], 'social' => ['x' => 'y'], 'images' => [['file' => '01.png']]];
        self::assertSame($valid, $schema->validate($valid));
        self::assertSame(['meta' => null], $schema->validate(['meta' => null]));
        $this->assertInvalid($schema, ['meta' => 'string'], null, "'meta'");
        $this->assertInvalid($schema, ['social' => ['x' => 1, 'y' => 2]], null, "'social'");
        $this->assertInvalid($schema, ['images' => ['01.png']], null, "'images\u{a0}›\u{a0}0'");
    }

    public function testListItemsTypeKeepsListRules(): void
    {
        // with a type of items, `enum` and `pattern` rules of the list apply to items
        $schema = new Schema(['*' => [
            'tags'  => ['type' => 'list', 'items' => 'string', 'enum' => ['php', 'twig']],
            'codes' => ['type' => 'list', 'items' => 'string', 'pattern' => '[A-Z]{2}', 'min' => 1],
        ]]);

        self::assertSame(['tags' => ['php'], 'codes' => ['FR']], $schema->validate(['tags' => ['php'], 'codes' => ['FR']]));
        $this->assertInvalid($schema, ['tags' => ['css']], null, "The variable 'tags\u{a0}›\u{a0}0' expects to be one of 'php', 'twig'.");
        $this->assertInvalid($schema, ['codes' => ['fr']], null, "'codes\u{a0}›\u{a0}0'");
        $this->assertInvalid($schema, ['codes' => []], null, "'codes'");
    }
}
