<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Exception\RuntimeException;

/**
 * Class Generator\Section.
 */
class Section extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        $sections = [];

        // identifying sections
        /** @var Page $page */
        foreach ($this->builder->getPages() as $page) {
            if ($page->getSection()) {
                // excludes page from its section?
                if ($page->getVariable('published') !== true || $page->getVariable('exclude')) {
                    $alteredPage = clone $page;
                    $alteredPage->setSection('');
                    $this->builder->getPages()->replace($page->getId(), $alteredPage);
                    continue;
                }
                $sections[$page->getSection()][$page->getVariable('language', $this->config->getLanguageDefault())][] = $page;
            }
        }

        // adds section to pages collection
        if (\count($sections) > 0) {
            $menuWeight = 100;

            foreach ($sections as $section => $languages) {
                foreach ($languages as $language => $pagesAsArray) {
                    $pageId = $path = Page::slugify($section);
                    if ($language != $this->config->getLanguageDefault()) {
                        $pageId = sprintf('%s.%s', $pageId, $language);
                    }
                    $page = (new Page($pageId))->setVariable('title', ucfirst($section))
                        ->setPath($path);
                    if ($this->builder->getPages()->has($pageId)) {
                        $page = clone $this->builder->getPages()->get($pageId);
                    }
                    $pages = new PagesCollection("section-$pageId", $pagesAsArray);
                    // cascade
                    /** @var \Cecil\Collection\Page\Page $page */
                    if ($page->hasVariable('cascade')) {
                        $cascade = $page->getVariable('cascade');
                        $pages->map(function (Page $page) use ($cascade) {
                            foreach ($cascade as $key => $value) {
                                if (!$page->hasVariable($key)) {
                                    $page->setVariable($key, $value);
                                }
                            }
                        });
                    }
                    // sorts (by date by default)
                    $pages = $pages->sortByDate();
                    /*
                     * sortby: date|updated|title|weight
                     *
                     * sortby:
                     *   variable: date|updated
                     *   desc_title: false|true
                     *   reverse: false|true
                     */
                    if ($page->hasVariable('sortby')) {
                        $sortby = (string) $page->getVariable('sortby');
                        // options?
                        $sortby = $page->getVariable('sortby')['variable'] ?? $sortby;
                        $descTitle = $page->getVariable('sortby')['desc_title'] ?? false;
                        $reverse = $page->getVariable('sortby')['reverse'] ?? false;
                        // sortby: date, title or weight
                        $sortMethod = sprintf('sortBy%s', ucfirst(str_replace('updated', 'date', $sortby)));
                        if (!method_exists($pages, $sortMethod)) {
                            throw new RuntimeException(sprintf('In "%s" section "%s" is not a valid value for "sortby" variable.', $page->getId(), $sortby));
                        }
                        $pages = $pages->$sortMethod(['variable' => $sortby, 'descTitle' => $descTitle, 'reverse' => $reverse]);
                    }
                    // adds navigation links (excludes taxonomy pages)
                    if (!\in_array($page->getId(), array_keys((array) $this->config->get('taxonomies')))) {
                        $this->addNavigationLinks($pages, $sortby ?? 'date', $page->getVariable('circular'));
                    }
                    // creates page for each section
                    $page->setType(Type::SECTION)
                        ->setSection($path)
                        ->setPages($pages)
                        ->setVariable('language', $language)
                        ->setVariable('date', $pages->first()->getVariable('date'))
                        ->setVariable('langref', $path);
                    // clean title
                    if ($page->getVariable('title') == 'index') {
                        $page->setVariable('title', $section);
                    }
                    // default menu
                    if (!$page->getVariable('menu')) {
                        $page->setVariable('menu', ['main' => ['weight' => $menuWeight]]);
                    }
                    $this->generatedPages->add($page);
                }
                $menuWeight += 10;
            }
        }
    }

    /**
     * Adds navigation (next and prev) to section sub pages.
     */
    protected function addNavigationLinks(PagesCollection $pages, string $sort = null, $circular = false): void
    {
        $pagesAsArray = $pages->toArray();
        if ($sort === null || $sort == 'date' || $sort == 'updated') {
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
