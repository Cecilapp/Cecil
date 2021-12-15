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

        // feeds sections array: [<section>][<language>] = <page>
        /** @var Page $page */
        foreach ($this->builder->getPages() as $page) {
            if ($page->getSection()) {

                dump([
                    'ID     ' => $page->getId(),
                    'Section' => $page->getSection(),
                    'Folder ' => $page->getFolder()]
                );

                if ($page->getFolder() != $page->getSection()
                    && $this->builder->getPages()->has($page->getFolder())
                ) {
                    dump($this->builder->getPages()->get($page->getFolder())->getId());
                    dump($this->builder->getPages()->get($page->getFolder())->getType());
                }

                // excludes page from its section?
                if ($page->getVariable('published') !== true || $page->getVariable('exclude')) {
                    $alteredPage = clone $page;
                    $alteredPage->setSection('');
                    $this->builder->getPages()->replace($page->getId(), $alteredPage);
                    continue;
                }
                $sections[$page->getSection()][$page->getVariable('language') ?? $this->config->getLanguageDefault()][] = $page;
            }
        }

        //dump(array_slice($sections, 0, 1));

        // adds each section to pages collection
        if (count($sections) > 0) {
            $menuWeight = 100;

            foreach ($sections as $section => $languages) {
                foreach ($languages as $language => $pagesAsArray) {
                    $pageId = $path = Page::slugify($section);
                    if ($language != $this->config->getLanguageDefault()) {
                        $pageId = sprintf('%s.%s', $pageId, $language);
                    }
                    $page = (new Page($pageId))->setVariable('title', ucfirst($section));
                    // clones if exists
                    if ($this->builder->getPages()->has($pageId)) {
                        $page = clone $this->builder->getPages()->get($pageId);
                    }
                    $pages = new PagesCollection($section, $pagesAsArray);
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
                    // sorts
                    $pages = $pages->sortByDate();
                    if ($page->hasVariable('sortby')) {
                        $sortMethod = sprintf('sortBy%s', ucfirst((string) $page->getVariable('sortby')));
                        if (!method_exists($pages, $sortMethod)) {
                            throw new Exception(sprintf(
                                'In "%s" section "%s" is not a valid value for "sortby" variable.',
                                $page->getId(),
                                $page->getVariable('sortby')
                            ));
                        }
                        $pages = $pages->$sortMethod();
                    }
                    // adds navigation links (excludes taxonomy pages)
                    if (!in_array($page->getId(), array_keys((array) $this->config->get('taxonomies')))) {
                        $this->addNavigationLinks($pages, $page->getVariable('sortby'), $page->getVariable('circular'));
                    }
                    // creates page for each section
                    $page->setPath($path)
                        ->setType(Type::SECTION)
                        ->setVariable('pages', $pages)
                        ->setVariable('date', $pages->first()->getVariable('date'))
                        ->setVariable('language', $language)
                        ->setVariable('langref', $path);
                    // default menu
                    if (!$page->getVariable('menu')) {
                        $page->setVariable('menu', [
                            'main' => ['weight' => $menuWeight],
                        ]);
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
        if ($sort === null || $sort == 'date') {
            $pagesAsArray = array_reverse($pagesAsArray);
        }
        $count = count($pagesAsArray);
        if ($count > 1) {
            foreach ($pagesAsArray as $position => $page) {
                switch ($position) {
                    // first
                    case 0:
                        if ($circular) {
                            $page->setVariables([
                                'prev' => [
                                    'id'    => $pagesAsArray[$count - 1]->getId(),
                                    'path'  => $pagesAsArray[$count - 1]->getPath(),
                                    'title' => $pagesAsArray[$count - 1]->getVariable('title'),
                                ],
                            ]);
                        }
                        $page->setVariables([
                            'next' => [
                                'id'    => $pagesAsArray[$position + 1]->getId(),
                                'path'  => $pagesAsArray[$position + 1]->getPath(),
                                'title' => $pagesAsArray[$position + 1]->getVariable('title'),
                            ],
                        ]);
                        break;
                    // last
                    case $count - 1:
                        $page->setVariables([
                            'prev' => [
                                'id'    => $pagesAsArray[$position - 1]->getId(),
                                'path'  => $pagesAsArray[$position - 1]->getPath(),
                                'title' => $pagesAsArray[$position - 1]->getVariable('title'),
                            ],
                        ]);
                        if ($circular) {
                            $page->setVariables([
                                'next' => [
                                    'id'    => $pagesAsArray[0]->getId(),
                                    'path'  => $pagesAsArray[0]->getPath(),
                                    'title' => $pagesAsArray[0]->getVariable('title'),
                                ],
                            ]);
                        }
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
