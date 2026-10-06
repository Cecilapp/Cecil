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
 *
 * Navigation links follow the sections tree: pages of a root section and of all
 * its sub-sections are chained depth-first, each sub-section index page being
 * placed among the pages of its parent section, followed by its own pages.
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
        // registry of section index pages, by language then path
        // (a translated index page with a custom path has an ID that differs from its path)
        $sectionIndexes = [];
        /** @var Page $page */
        foreach ($this->builder->getPages() as $page) {
            if ($page->isVirtual() || !$page->isSectionIndex()) {
                continue;
            }
            $path = (string) $page->getPath();
            $sectionIndexes[$page->getVariable('language', $this->config->getLanguageDefault())][$path] = $page;
            // a sub-section is a section index located in a nested folder (its path contains a "/")
            if (str_contains($path, '/')) {
                $subSections[$path] = true;
            }
        }

        // identifying sections from all pages
        /** @var Page $page */
        foreach ($this->builder->getPages() as $page) {
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
            // (uses the language-independent section, not the path which may be customized per language)
            $sectionsPaths = [(string) $page->getSection()];
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
                    $langref = $path;
                    if (isset($sectionIndexes[$language][$path])) {
                        // the section index page is found by its path, which may be customized
                        $page = clone $sectionIndexes[$language][$path];
                        $pageId = $page->getId();
                        $langref = $page->getVariable('langref') ?? $path;
                    } elseif ($this->builder->getPages()->has($pageId)) {
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
                    // creates page for each section
                    $toplevel = !str_contains($path, '/');
                    // the section name and the language reference are language-independent (i.e.: not the custom path)
                    $page->setType(Type::SECTION->value)
                        ->setSection($langref)
                        ->setPages($pages)
                        ->setVariable('language', $language)
                        ->setVariable('date', $pages->first()?->getVariable('date'))
                        ->setVariable('langref', $langref)
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

            // adds navigation links (prev/next), once per root section (i.e.: without parent),
            // following the order of its sections tree (excludes taxonomy pages)
            foreach ($sectionPages as $sectionPagesByPath) {
                foreach ($sectionPagesByPath as $sectionPage) {
                    if ($sectionPage->getParent() !== null || \in_array($sectionPage->getId(), array_keys((array) $this->config->get('taxonomies')))) {
                        continue;
                    }
                    $this->addNavigationLinks($this->getNavigationPages($sectionPage), $sectionPage->getVariable('circular') ?? false);
                }
            }
        }
    }

    /**
     * Returns the pages of a section in reading order, walking its sections tree depth-first:
     * the section's own pages and sub-sections are sorted together (with the section's `sortby`),
     * and each sub-section (its index page) is followed by its own pages and sub-sections.
     *
     * @return Page[]
     */
    protected function getNavigationPages(Page $section): array
    {
        // immediate children: own pages (not those of a sub-section) and sub-sections
        $children = new PagesCollection("navigation-{$section->getId()}");
        foreach ($section->getPages() ?? [] as $page) {
            if ($page->getParent() === $section) {
                $children->add($page);
            }
        }
        $subSections = [];
        foreach ($section->getSections() as $subSection) {
            $subSections[$subSection->getId()] = true;
            $children->add($subSection);
        }
        // sorts children (chronological order for dates)
        $sortBy = $section->getVariable('sortby') ?? $this->config->get('pages.sortby');
        $children = $children->sortBy($sortBy);
        $sortByVariable = \is_array($sortBy) ? ($sortBy['variable'] ?? 'date') : ($sortBy ?? 'date');
        $childrenAsArray = $children->toArray();
        if ($sortByVariable == 'date' || $sortByVariable == 'updated') {
            $childrenAsArray = array_reverse($childrenAsArray);
        }
        // flattens the tree
        $pages = [];
        foreach ($childrenAsArray as $child) {
            $pages[] = $child;
            if (isset($subSections[$child->getId()])) {
                array_push($pages, ...$this->getNavigationPages($child));
            }
        }

        return $pages;
    }

    /**
     * Adds navigation (next and prev) to each pages of an ordered list.
     *
     * @param Page[] $pagesAsArray
     */
    protected function addNavigationLinks(array $pagesAsArray, bool $circular = false): void
    {
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
