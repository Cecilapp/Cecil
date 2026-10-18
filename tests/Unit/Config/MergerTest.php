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
use Cecil\Config\Merger;
use PHPUnit\Framework\TestCase;

class MergerTest extends TestCase
{
    public function testScalarIsReplaced(): void
    {
        self::assertSame(['title' => 'High'], Merger::merge(['title' => 'Low'], ['title' => 'High'], Config::IMPORT_MERGE));
    }

    public function testBooleanAppliedToMapTogglesEnabledKey(): void
    {
        self::assertSame(
            ['cache' => ['dir' => '.cache', 'enabled' => false]],
            Merger::merge(['cache' => ['dir' => '.cache']], ['cache' => false], Config::IMPORT_REPLACE)
        );
    }

    public function testMapsAreMergedKeyByKey(): void
    {
        self::assertSame(
            ['images' => ['lazy' => false, 'resize' => 0]],
            Merger::merge(['images' => ['lazy' => true, 'resize' => 0]], ['images' => ['lazy' => false]], Config::IMPORT_REPLACE)
        );
    }

    public function testEmptyArrayDoesNotOverrideMap(): void
    {
        self::assertSame(['images' => ['lazy' => true]], Merger::merge(['images' => ['lazy' => true]], ['images' => []], Config::IMPORT_REPLACE));
    }

    public function testListOfValuesIsReplacedOrCombinedInPreserveMode(): void
    {
        self::assertSame(['ext' => ['md']], Merger::merge(['ext' => ['md', 'txt']], ['ext' => ['md']], Config::IMPORT_MERGE));
        self::assertSame(['ext' => ['md', 'txt']], Merger::merge(['ext' => ['txt']], ['ext' => ['md']], Config::IMPORT_PRESERVE));
    }

    public function testListPatchIsAppliedToListOfValues(): void
    {
        self::assertSame(['ext' => ['md', 'mdx']], Merger::merge(['ext' => ['md', 'txt']], ['ext' => ['txt' => false, 'mdx' => true]], Config::IMPORT_REPLACE));
    }

    public function testListOfItemsIsConcatenatedInMergeModeOnly(): void
    {
        $low = ['main' => [['id' => 'a']]];
        $high = ['main' => [['id' => 'b']]];

        self::assertSame(['main' => [['id' => 'a'], ['id' => 'b']]], Merger::merge($low, $high, Config::IMPORT_MERGE));
        self::assertSame($high, Merger::merge($low, $high, Config::IMPORT_REPLACE));
    }

    public function testListOfIdentifiedItemsIsMergedByIdentifier(): void
    {
        $merged = Merger::merge(
            ['output' => ['formats' => [['name' => 'rss', 'filename' => 'rss', 'extension' => 'xml']]]],
            ['output' => ['formats' => [['name' => 'rss', 'filename' => 'feed'], ['name' => 'custom']]]],
            Config::IMPORT_REPLACE
        );

        self::assertSame([
            ['name' => 'rss', 'filename' => 'feed', 'extension' => 'xml'],
            ['name' => 'custom'],
        ], $merged['output']['formats']);
    }
}
