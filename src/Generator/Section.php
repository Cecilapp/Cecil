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
use Cecil\Exception\Exception;

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
                // cascade
                if ($page->hasVariable('cascade')) {
                    $cascade = $page->getVariable('cascade');
                    $pages->map(function (Page $page) use ($cascade) {
                        $page->setVariables($cascade);
                    });
                }
                // sorts
                $pages = $pages->sortByDate();
                if ($page->getVariable('sortby')) {
                    $sortMethod = sprintf('sortBy%s', ucfirst($page->getVariable('sortby')));
                    if (!method_exists($pages, $sortMethod)) {
                        throw new Exception(sprintf(
                            'In "%s" section "%s" is not a valid value for "sortby" variable.',
                            $page->getId(),
                            $page->getVariable('sortby')
                        ));
                    }
                    $pages = $pages->$sortMethod();
                }
                // adds navigation links
                $this->addNavigationLinks($pages, $page->getVariable('sortby'));
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
     * @param string          $sort
     *
     * @return void
     */
    protected function addNavigationLinks(PagesCollection $pages, string $sort = null): void
    {
        $pagesAsArray = $pages->toArray();
        if ($sort === null || $sort == 'date') {
            $pagesAsArray = array_reverse($pagesAsArray);
        }
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
