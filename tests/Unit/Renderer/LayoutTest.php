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

    public function testSectionLookupOrder(): void
    {
        $page = $this->section('documentation');

        self::assertSame([
            'documentation/index.html.twig',
            'documentation/list.html.twig',
            'section/documentation.html.twig',
            '_default/section.html.twig',
            'list.html.twig',
            '_default/list.html.twig',
        ], $this->lookup($page));
    }

    public function testSubSectionLookupOrderFallsBackToParentSection(): void
    {
        $page = $this->section('documentation/content');

        self::assertSame([
            'documentation/content/index.html.twig',
            'documentation/content/list.html.twig',
            'section/documentation/content.html.twig',
            'documentation/index.html.twig',
            'documentation/list.html.twig',
            'section/documentation.html.twig',
            '_default/section.html.twig',
            'list.html.twig',
            '_default/list.html.twig',
        ], $this->lookup($page));
    }

    public function testNestedSubSectionLookupOrderFallsBackToAncestorSections(): void
    {
        $page = $this->section('documentation/templates/reference');

        self::assertSame([
            'documentation/templates/reference/index.html.twig',
            'documentation/templates/reference/list.html.twig',
            'section/documentation/templates/reference.html.twig',
            'documentation/templates/index.html.twig',
            'documentation/templates/list.html.twig',
            'section/documentation/templates.html.twig',
            'documentation/index.html.twig',
            'documentation/list.html.twig',
            'section/documentation.html.twig',
            '_default/section.html.twig',
            'list.html.twig',
            '_default/list.html.twig',
        ], $this->lookup($page));
    }

    public function testSubSectionLookupOrderWithLayoutsSectionsMapping(): void
    {
        // the mapping of the sub-section itself, then the mapping of its ancestor sections
        $page = $this->section('documentation/templates/reference');

        self::assertSame([
            'reference/index.html.twig',
            'reference/list.html.twig',
            'section/reference.html.twig',
            'documentation/templates/index.html.twig',
            'documentation/templates/list.html.twig',
            'section/documentation/templates.html.twig',
            'docs/index.html.twig',
            'docs/list.html.twig',
            'section/docs.html.twig',
            '_default/section.html.twig',
            'list.html.twig',
            '_default/list.html.twig',
        ], $this->lookup($page, [
            'documentation/templates/reference' => 'reference',
            'documentation'                     => 'docs',
        ]));

        // a sub-section mapped to its parent section: no duplicate
        $page = $this->section('documentation/content');

        self::assertSame([
            'documentation/index.html.twig',
            'documentation/list.html.twig',
            'section/documentation.html.twig',
            '_default/section.html.twig',
            'list.html.twig',
            '_default/list.html.twig',
        ], $this->lookup($page, ['documentation/content' => 'documentation']));
    }

    public function testSubSectionLookupOrderWithLayoutVariable(): void
    {
        $page = $this->section('documentation/content')
            ->setVariable('layout', 'custom');

        self::assertSame([
            'custom.html.twig',
            'documentation/content/index.html.twig',
            'documentation/content/list.html.twig',
            'section/documentation/content.html.twig',
            'documentation/index.html.twig',
            'documentation/list.html.twig',
            'section/documentation.html.twig',
            '_default/section.html.twig',
            'list.html.twig',
            '_default/list.html.twig',
        ], $this->lookup($page));
    }

    public function testPageInSubSectionLookupOrder(): void
    {
        // a regular page located in a sub-section belongs to its top level section
        $page = (new Page('documentation/content/pages'))
            ->setSection('documentation')
            ->setVariable('layout', 'custom')
            ->setVariable('language', 'en');

        self::assertSame([
            'documentation/custom.html.twig',
            'custom.html.twig',
            'documentation/page.html.twig',
            '_default/custom.html.twig',
            'page.html.twig',
            '_default/page.html.twig',
        ], $this->lookup($page));
    }

    private function section(string $section): Page
    {
        return (new Page($section))
            ->setType(Type::SECTION->value)
            ->setPath($section)
            ->setSection($section)
            ->setVariable('language', 'en');
    }

    private function lookup(Page $page, array $sectionsMapping = []): array
    {
        $config = (new Builder($sectionsMapping ? ['layouts' => ['sections' => $sectionsMapping]] : null))->getConfig();

        return (new class () extends Layout {
            public function lookupPublic(Page $page, string $format, \Cecil\Config $config): array
            {
                return self::lookup($page, $format, $config);
            }
        })->lookupPublic($page, 'html', $config);
    }
}
