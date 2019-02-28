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
use Cecil\Collection\Page\Type;

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
        $sections = [];

        // identify sections
        /* @var $page Page */
        foreach ($pagesCollection as $page) {
            if ($page->getSection()) {
                $sections[$page->getSection()][] = $page;
            }
        }

        // adds section to pages collection
        if (count($sections) > 0) {
            $menuWeight = 100;
            foreach ($sections as $sectionName => $pagesArray) {
                $pageId = $path = Page::slugify($sectionName);
                if (!$pagesCollection->has($pageId)) {
                    $pages = (new PagesCollection($sectionName, $pagesArray))->sortByDate();
                    $page = (new Page($pageId))
                        ->setId($pageId)
                        ->setPath($path)
                        ->setType(Type::SECTION)
                        ->setVariable('title', ucfirst($sectionName))
                        ->setVariable('pages', $pages)
                        ->setVariable('date', $pages->first()->getVariable('date'))
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
