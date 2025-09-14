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
use Cecil\Exception\RuntimeException;

/**
 * Section generator class.
 *
 * This class is responsible for generating sections from the pages in the builder.
 * It identifies sections based on the 'section' variable in each page, and
 * creates a new page for each section. The generated pages are added to the
 * collection of generated pages. It also handles sorting of subpages and
 * adding navigation links (next and previous) to the section pages.
 */
class Section extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        $sections = [];

        // identifying sections from all pages
        /** @var Page $page */
        foreach ($this->builder->getPages() ?? [] as $page) {
            // top level (root) sections
            if ($page->getSection()) {
                // do not add "not published" and "not excluded" pages to its section
                if (
                    $page->getVariable('published') !== true
                    || ($page->getVariable('excluded') || $page->getVariable('exclude'))
                ) {
                    continue;
                }
                $sections[$page->getSection()][$page->getVariable('language', $this->config->getLanguageDefault())][] = $page;
            }
        }

        // adds each section to pages collection
        if (\count($sections) > 0) {
            $menuWeight = 100;

            foreach ($sections as $section => $languages) {
                foreach ($languages as $language => $pagesAsArray) {
                    $pageId = $path = Page::slugify($section);
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
                    $page->setType(Type::SECTION->value)
                        ->setSection($path)
                        ->setPages($pages)
                        ->setVariable('language', $language)
                        ->setVariable('date', $pages->first()->getVariable('date'))
                        ->setVariable('langref', $path);
                    // human readable title
                    if ($page->getVariable('title') == 'index') {
                        $page->setVariable('title', $section);
                    }
                    // default menu
                    if (!$page->getVariable('menu')) {
                        $page->setVariable('menu', ['main' => ['weight' => $menuWeight]]);
                    }

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
                $this->generatedPages->add($page);
            }
        }
    }
}
