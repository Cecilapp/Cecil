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
        $sections = [];

        // identify sections
        /* @var $page Page */
        foreach ($pagesCollection as $page) {
            if ($page->getSection()) {
                // ie:
                // [blog][0] = blog/post-1
                // [blog][1] = blog/post-2
                $sectionsList[$page->getSection()][] = $page->getId();
            }
        }

        // sections collections
        // ie:
        // [blog] = Collection(blog)
        foreach ($sectionsList as $sectionName => $pagesList) {
            $sections[$sectionName] = new PagesCollection($sectionName);
            foreach ($pagesList as $pageId) {
                $sections[$sectionName]->add($pagesCollection->get($pageId));
            }
        }

        // adds section to pages collection
        if (count($sections) > 0) {
            $menuWeight = 100;
            foreach ($sections as $section => $pages) {
                $pageId = $pathname = Page::slugify($section);
                if (!$pagesCollection->has($pageId)) {
                    //usort($pages, 'Cecil\Util::sortByDate');
                    $pages = $pages->sortByDate();
                    $page = (new Page())
                        ->setId($pageId)
                        ->setPathname($pathname)
                        ->setType(Type::SECTION)
                        ->setVariable('title', ucfirst($section))
                        ->setVariable('pages', $pages)
                        ->setVariable('date', $pages->getIterator()->current()->getVariable('date'))
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
