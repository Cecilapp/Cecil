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

namespace Cecil\Test\Unit\Collection\Taxonomy;

use Cecil\Collection\Taxonomy\Collection;
use Cecil\Collection\Taxonomy\Vocabulary;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testGetReturnsVocabulary(): void
    {
        $taxonomies = new Collection('taxonomies');
        $tags = new Vocabulary('tags');
        $taxonomies->add($tags);

        self::assertSame($tags, $taxonomies->get('tags'));
    }
}
