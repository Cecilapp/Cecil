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
}
