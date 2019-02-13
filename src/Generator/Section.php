<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Page\Type;

/**
 * Class Section.
 */
class Section extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PagesCollection $pagesCollection, \Closure $messageCallback)
    {
        $generatedPages = new PagesCollection('sections');
        $sectionsList = [];

        // identify sections
        /* @var $page Page */
        foreach ($pagesCollection as $page) {
            if ($page->getSection()) {
                // ie: ['blog'][0]['blog/post-1']
                $sectionsList[$page->getSection()][] = $page->getId();
            }
        }

        // sections collections
        $sections = [];
        foreach ($sectionsList as $sectionName => $pagesList) {
            if (!array_key_exists($sectionName, $sections)) {
                $sections[$sectionName] = new PagesCollection($sectionName);
                foreach ($pagesList as $pageId) {
                    $sections[$sectionName]->add($pagesCollection->get($pageId));
                }
            }
        }

        // DEBUG
        //print_r($sections2);
        //print_r($section3);
        foreach ($section3 as $section => $collection) {
            //echo $section."\n";
            $collection->sortByDate();
            //echo $collection->getId()."\n";
            //echo $collection;
            print_r($collection);
        }
        die();

        // adds section pages to collection
        if (count($sections) > 0) {
            $menuWeight = 100;
            foreach ($sections as $section => $pages) {
                $pageId = Page::slugify($section);
                if (!$pagesCollection->has($pageId)) {
                    usort($pages, 'Cecil\Util::sortByDate');
                    $page = (new Page())
                        ->setId($pageId)
                        ->setPathname($pageId)
                        ->setVariable('title', ucfirst($section))
                        ->setType(Type::SECTION)
                        ->setVariable('pages', $pages)
                        ->setVariable('date', reset($pages)->getVariable('date'))
                        ->setVariable('menu', [
                            'main' => ['weight' => $menuWeight],
                        ]);
                    $generatedPages->add($page);
                }
                $menuWeight += 10;
            }
        }

        return $generatedPages;
    }
}
