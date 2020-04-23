<?php
/**
 * This file is part of the Cecil/Cecil package.
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
                // excludes page from section
                if ($page->getVariable('exclude')) {
                    $alteredPage = clone $page;
                    $alteredPage->setSection('');
                    $this->builder->getPages()->replace($page->getId(), $alteredPage);
                    continue;
                }
                $sections[$page->getSection()][] = $page;
            }
        }

        // adds section to pages collection
        if (count($sections) > 0) {
            $menuWeight = 100;
            foreach ($sections as $section => $pagesAsArray) {
                $pageId = $path = Page::slugify($section);
                $page = (new Page($pageId))->setVariable('title', ucfirst($section));
                if ($this->builder->getPages()->has($pageId)) {
                    $page = clone $this->builder->getPages()->get($pageId);
                }
                $pages = new PagesCollection($section, $pagesAsArray);
                // sorts
                $pages = $pages->sortByDate();
                /** @var \Cecil\Collection\Page\Page $page */
                if ($page->getVariable('sortby')) {
                    $sortMethod = sprintf('sortBy%s', ucfirst($page->getVariable('sortby')));
                    if (method_exists($pages, $sortMethod)) {
                        $pages = $pages->$sortMethod();
                    }
                }
                // adds navigation links
                $this->addNavigationLinks($pages);
                // creates page for each section
                $page->setPath($path)
                    ->setType(Type::SECTION)
                    ->setVariable('pages', $pages)
                    ->setVariable('date', $pages->first()->getVariable('date'));
                // default menu
                if (!$page->getVariable('menu')) {
                    $page->setVariable('menu', [
                        'main' => ['weight' => $menuWeight],
                    ]);
                }
                $this->generatedPages->add($page);
                $menuWeight += 10;
            }
        }
    }

    /**
     * Adds navigation (next and prev) to section sub pages.
     *
     * @param PagesCollection $pages
     *
     * @return void
     */
    protected function addNavigationLinks(PagesCollection $pages): void
    {
        $pagesAsArray = $pages->toArray();
        if (count($pagesAsArray) > 1) {
            foreach ($pagesAsArray as $position => $page) {
                switch ($position) {
                    // first
                    case 0:
                        $page->setVariables([
                            'next' => [
                                'id'    => $pagesAsArray[$position + 1]->getId(),
                                'path'  => $pagesAsArray[$position + 1]->getPath(),
                                'title' => $pagesAsArray[$position + 1]->getVariable('title'),
                            ],
                        ]);
                        break;
                    // last
                    case count($pagesAsArray) - 1:
                        $page->setVariables([
                            'prev' => [
                                'id'    => $pagesAsArray[$position - 1]->getId(),
                                'path'  => $pagesAsArray[$position - 1]->getPath(),
                                'title' => $pagesAsArray[$position - 1]->getVariable('title'),
                            ],
                        ]);
                        break;
                    default:
                        $page->setVariables([
                            'prev' => [
                                'id'    => $pagesAsArray[$position - 1]->getId(),
                                'path'  => $pagesAsArray[$position - 1]->getPath(),
                                'title' => $pagesAsArray[$position - 1]->getVariable('title'),
                            ],
                            'next' => [
                                'id'    => $pagesAsArray[$position + 1]->getId(),
                                'path'  => $pagesAsArray[$position + 1]->getPath(),
                                'title' => $pagesAsArray[$position + 1]->getVariable('title'),
                            ],
                        ]);
                        break;
                }
                $this->generatedPages->add($page);
            }
        }
    }
}
