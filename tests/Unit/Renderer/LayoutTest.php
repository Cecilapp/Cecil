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

namespace Cecil\Test\Unit\Renderer;

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Renderer\Layout;
use PHPUnit\Framework\TestCase;

class LayoutTest extends TestCase
{
    public function testTermLookupOrder(): void
    {
        $page = (new Page('categories/data-sovereignty'))
            ->setType(Type::TERM->value)
            ->setVariable('term', 'categories/data-sovereignty')
            ->setVariable('plural', 'categories')
            ->setVariable('singular', 'category')
            ->setVariable('language', 'en');

        self::assertSame([
            'taxonomy/categories/data-sovereignty.html.twig',
            'taxonomy/category.html.twig',
            'term.html.twig',
            '_default/term.html.twig',
            '_default/list.html.twig',
        ], $this->lookup($page));
    }

    public function testVocabularyLookupOrder(): void
    {
        $page = (new Page('categories'))
            ->setType(Type::VOCABULARY->value)
            ->setVariable('plural', 'categories')
            ->setVariable('singular', 'category')
            ->setVariable('language', 'en');

        self::assertSame([
            'taxonomy/categories.html.twig',
            'vocabulary.html.twig',
            '_default/vocabulary.html.twig',
        ], $this->lookup($page));
    }

    private function lookup(Page $page): array
    {
        $config = (new Builder())->getConfig();

        return (new class () extends Layout {
            public function lookupPublic(Page $page, string $format, \Cecil\Config $config): array
            {
                return self::lookup($page, $format, $config);
            }
        })->lookupPublic($page, 'html', $config);
    }
}
