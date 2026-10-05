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

use Cecil\Collection\Taxonomy\Term;
use Cecil\Collection\Taxonomy\Vocabulary;
use PHPUnit\Framework\TestCase;

class VocabularyTest extends TestCase
{
    public function testAddSkipsDuplicateTermsAndGetReturnsTerm(): void
    {
        $vocabulary = new Vocabulary('tags');
        $term = (new Term('php'))->setName('PHP');

        $vocabulary->add($term);
        $vocabulary->add((new Term('php'))->setName('Duplicate'));

        self::assertCount(1, $vocabulary);
        self::assertSame($term, $vocabulary->get('php'));
        self::assertSame('PHP', $vocabulary->get('php')->getName());
    }
}
