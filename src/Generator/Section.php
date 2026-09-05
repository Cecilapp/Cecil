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

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Util;

/**
 * Section generator class.
 *
 * This class is responsible for generating sections from the pages in the builder.
 * It identifies sections based on the 'section' variable in each page, and
 * creates a new page for each section. The generated pages are added to the
 * collection of generated pages. It also handles sorting of subpages and
 * adding navigation links (next and previous) to the section pages.
 *
 * It also supports sub-sections: any nested folder that explicitly contains an
 * "index.md" file becomes its own (sub-)section. Pages located in a sub-section
 * belong to both their top level section and each of their ancestor sub-sections,
 * while a sub-section index page itself is not listed in its parent section.
 */
class Section extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function generate(): void
    {
        $sections = [];

        // identifying explicit sub-sections: nested folders containing an "index.md" file
        $subSections = [];
        /** @var Page $page */
        foreach ($this->builder->getPages() ?? [] as $page) {
            if ($page->isVirtual() || !$page->isSectionIndex()) {
                continue;
            }
            $path = (string) $page->getPath();
            // a sub-section is a section index located in a nested folder (its path contains a "/")
            if (str_contains($path, '/')) {
                $subSections[$path] = true;
            }
        }

        // identifying sections from all pages
        /** @var Page $page */
        foreach ($this->builder->getPages() ?? [] as $page) {
            if (!$page->getSection()) {
                continue;
            }
            // do not add "not published" and "not excluded" pages to its section
            if (
                $page->getVariable('published') !== true
                || ($page->getVariable('excluded') || $page->getVariable('exclude'))
            ) {
                continue;
            }
            // a sub-section index page is not listed in its parent section(s)
            if ($page->isSectionIndex() && isset($subSections[(string) $page->getPath()])) {
                continue;
            }
            $language = $page->getVariable('language', $this->config->getLanguageDefault());
            // the page belongs to its top level (root) section...
            $sectionsPaths = [explode('/', (string) $page->getPath())[0]];
            // ...and to each of its ancestor sub-sections
            $prefix = '';
            foreach (explode('/', (string) $page->getFolder()) as $ancestor) {
                $prefix = $prefix === '' ? $ancestor : "$prefix/$ancestor";
                if (isset($subSections[$prefix])) {
                    $sectionsPaths[] = $prefix;
                }
            }
            foreach (array_unique($sectionsPaths) as $sectionPath) {
                $sections[$sectionPath][$language][] = $page;
            }
        }

        // adds each section to pages collection
        if (\count($sections) > 0) {
            $menuWeight = 100;
            $sectionPages = []; // registry of created section pages, by language then path

            foreach ($sections as $section => $languages) {
                foreach ($languages as $language => $pagesAsArray) {
                    $pageId = $path = Util\Slugifier::slugify($section);
                    if ($language != $this->config->getLanguageDefault()) {
                        $pageId = "$language/$pageId";
                    }
                    $page = (new Page($pageId))->setVariable('title', ucfirst($section))
                        ->setPath($path);
                    if ($this->builder->getPages()->has($pageId)) {
                        $page = clone $this->builder->getPages()->get($pageId);
                    }
                    $pages = new PagesCollection("section-$pageId", $pagesAsArray);
                    // cascade variables
                    if ($page->hasVariable('cascade')) {
                        $cascade = $page->getVariable('cascade');
                        if (\is_array($cascade)) {
                            $pages->map(function (Page $page) use ($cascade) {
                                foreach ($cascade as $key => $value) {
                                    if (!$page->hasVariable($key)) {
                                        $page->setVariable($key, $value);
                                    }
                                }
                            });
                        }
                    }
                    // sorts pages
                    $sortBy = $page->getVariable('sortby') ?? $this->config->get('pages.sortby');
                    $pages = $pages->sortBy($sortBy);
                    // adds navigation links (excludes taxonomy pages)
                    $sortBy = $page->getVariable('sortby')['variable'] ?? $page->getVariable('sortby') ?? $this->config->get('pages.sortby')['variable'] ?? $this->config->get('pages.sortby') ?? 'date';
                    if (!\in_array($page->getId(), array_keys((array) $this->config->get('taxonomies')))) {
                        $this->addNavigationLinks($pages, $sortBy, $page->getVariable('circular') ?? false);
                    }
                    // creates page for each section
                    $toplevel = !str_contains($path, '/');
                    $page->setType(Type::SECTION->value)
                        ->setSection($path)
                        ->setPages($pages)
                        ->setVariable('language', $language)
                        ->setVariable('date', $pages->first()->getVariable('date'))
                        ->setVariable('langref', $path)
                        ->setVariable('toplevel', $toplevel);
                    // human readable title
                    if ($page->getVariable('title') == 'index') {
                        $page->setVariable('title', $section);
                    }
                    // default menu (only top level sections are added to the "main" menu)
                    if ($toplevel && !$page->getVariable('menu')) {
                        $page->setVariable('menu', ['main' => ['weight' => $menuWeight]]);
                    }

                    // sets parent references:
                    // the section's parent is its nearest ancestor section (if any),
                    // and each of its pages' parent is the section itself (deepest wins).
                    $sectionPages[$language][$path] = $page;
                    $parentPath = $path;
                    while (($pos = strrpos($parentPath, '/')) !== false) {
                        $parentPath = substr($parentPath, 0, $pos);
                        if (isset($sectionPages[$language][$parentPath])) {
                            $parentSection = $sectionPages[$language][$parentPath];
                            $page->setVariable('parent', $parentSection);
                            // registers the section as an immediate descendant section of its parent
                            $childSections = $parentSection->getVariable('sections') ?? new PagesCollection("sections-{$parentSection->getId()}");
                            $childSections->add($page);
                            $parentSection->setVariable('sections', $childSections);
                            break;
                        }
                    }
                    $pages->map(function (Page $subPage) use ($page) {
                        $subPage->setVariable('parent', $page);
                    });

                    try {
                        $this->generatedPages->add($page);
                    } catch (\DomainException) {
                        $this->generatedPages->replace($page->getId(), $page);
                    }
                }
                $menuWeight += 10;
            }
        }
    }

    /**
     * Adds navigation (next and prev) to each pages of a section.
     */
    protected function addNavigationLinks(PagesCollection $pages, string|null $sortBy = null, bool $circular = false): void
    {
        $pagesAsArray = $pages->toArray();
        if ($sortBy === null || $sortBy == 'date' || $sortBy == 'updated') {
            $pagesAsArray = array_reverse($pagesAsArray);
        }
        $count = \count($pagesAsArray);
        if ($count > 1) {
            foreach ($pagesAsArray as $position => $page) {
                switch ($position) {
                    case 0: // first
                        if ($circular) {
                            $page->setVariables([
                                'prev' => $pagesAsArray[$count - 1],
                            ]);
                        }
                        $page->setVariables([
                            'next' => $pagesAsArray[$position + 1],
                        ]);
                        break;
                    case $count - 1: // last
                        $page->setVariables([
                            'prev' => $pagesAsArray[$position - 1],
                        ]);
                        if ($circular) {
                            $page->setVariables([
                                'next' => $pagesAsArray[0],
                            ]);
                        }
                        break;
                    default:
                        $page->setVariables([
                            'prev' => $pagesAsArray[$position - 1],
                            'next' => $pagesAsArray[$position + 1],
                        ]);
                        break;
                }
                try {
                    $this->generatedPages->add($page);
                } catch (\DomainException) {
                    $this->generatedPages->replace($page->getId(), $page);
                }
            }
        }
    }
}
