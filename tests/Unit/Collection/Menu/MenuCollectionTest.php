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

namespace Cecil\Test\Unit\Collection\Menu;

use Cecil\Collection\Menu\Collection;
use Cecil\Collection\Menu\Entry;
use Cecil\Collection\Menu\Menu;
use PHPUnit\Framework\TestCase;

class MenuCollectionTest extends TestCase
{
    public function testEntryGettersAndSetters(): void
    {
        $entry = new Entry('home');
        $entry->setName('Home')->setUrl('/')->setWeight(10);

        self::assertSame('Home', $entry->getName());
        self::assertSame('/', $entry->getUrl());
        self::assertSame(10, $entry->getWeight());
    }

    public function testMenuAddReplacesExistingEntryWithSameId(): void
    {
        $menu = new Menu('main');
        $menu->add((new Entry('home'))->setName('Old')->setWeight(1));
        $menu->add((new Entry('home'))->setName('New')->setWeight(2));

        self::assertCount(1, $menu);
        self::assertSame('New', $menu->get('home')->getName());
        self::assertSame(2, $menu->get('home')->getWeight());
    }

    public function testCollectionGetAndIsset(): void
    {
        $menus = new Collection('menus');
        $main = new Menu('main');
        $menus->add($main);

        self::assertTrue(isset($menus->main));
        self::assertFalse(isset($menus->footer));
        self::assertSame($main, $menus->get('main'));
    }
}
