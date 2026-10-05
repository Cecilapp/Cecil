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

namespace Cecil\Test\Unit\Collection;

use Cecil\Collection\Collection;
use Cecil\Collection\Item;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testCollectionLookupsStayInSyncAfterMutations(): void
    {
        $collection = new Collection('test');
        $first = new Item('first');
        $second = new Item('second');

        $collection->add($first);
        $collection->add($second);

        self::assertTrue($collection->has('first'));
        self::assertSame(0, $collection->getPosition('first'));
        self::assertSame($second, $collection->get('second'));

        $replacement = new Item('replacement');
        $collection->replace('first', $replacement);

        self::assertFalse($collection->has('first'));
        self::assertTrue($collection->has('replacement'));
        self::assertSame(0, $collection->getPosition('replacement'));
        self::assertSame($replacement, $collection->get('replacement'));

        $collection->remove('second');

        self::assertFalse($collection->has('second'));
        self::assertCount(1, $collection);
    }

    public function testDerivedCollectionsRebuildLookupIndex(): void
    {
        $collection = new Collection('test', [
            new Item('b'),
            new Item('a'),
        ]);

        $sorted = $collection->usort(function (Item $left, Item $right): int {
            return $left->getId() <=> $right->getId();
        });
        $filtered = $sorted->filter(function (Item $item): bool {
            return $item->getId() === 'b';
        });

        self::assertSame(0, $sorted->getPosition('a'));
        self::assertSame(1, $sorted->getPosition('b'));
        self::assertTrue($filtered->has('b'));
        self::assertSame(1, $filtered->getPosition('b'));
    }

    public function testAddDuplicateItemThrowsDomainException(): void
    {
        $collection = new Collection('test');
        $item = new Item('duplicate');
        $collection->add($item);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('item already exists');

        $collection->add(new Item('duplicate'));
    }

    public function testGetReplaceAndRemoveMissingItemThrowDomainException(): void
    {
        $collection = new Collection('test');

        try {
            $collection->get('missing');
            self::fail('Expected exception was not thrown.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('Failed getting "missing"', $e->getMessage());
        }

        try {
            $collection->replace('missing', new Item('other'));
            self::fail('Expected exception was not thrown.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('Failed replacing "missing"', $e->getMessage());
        }

        try {
            $collection->remove('missing');
            self::fail('Expected exception was not thrown.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('Failed removing "missing"', $e->getMessage());
        }
    }

    public function testFirstAndLastReturnNullOnEmptyCollection(): void
    {
        $collection = new Collection('empty');

        self::assertNull($collection->first());
        self::assertNull($collection->last());
    }

    public function testArrayAccessAndHelpersBehaveAsExpected(): void
    {
        $collection = new Collection('helpers');
        $first = new Item('first');
        $second = new Item('second');

        $collection[] = $first;
        $collection[] = $second;

        self::assertTrue(isset($collection['first']));
        self::assertSame($first, $collection['first']);
        self::assertSame([0, 1], $collection->keys());
        self::assertSame('helpers', (string) $collection);
        self::assertStringEndsWith("\n", $collection->toJson());

        unset($collection['first']);
        self::assertFalse($collection->has('first'));
    }

    public function testReverseAndMapReturnDerivedCollections(): void
    {
        $collection = new Collection('test', [
            new Item('a'),
            new Item('b'),
        ]);

        $reversed = $collection->reverse();
        $mapped = $collection->map(function (Item $item): Item {
            return new Item($item->getId() . '-mapped');
        });

        self::assertSame('b', $reversed->first()?->getId());
        self::assertSame('a-mapped', $mapped->first()?->getId());
    }
}
