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
        $sections = [];

        // collects sections
        /* @var $page Page */
        foreach ($pagesCollection as $page) {
            if ($page->getSection() != '') {
                $sections[$page->getSection()][] = $page;
            }
        }
        // adds node pages to collection
        if (count($sections) > 0) {
            $menuWeight = 100;
            foreach ($sections as $section => $pages) {
                $pageId = Page::slugify(sprintf('%s', $section));
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
