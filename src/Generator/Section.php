<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PageCollection;
use Cecil\Collection\Page\Page;
use Cecil\Page\NodeType;

/**
 * Class Section.
 */
class Section extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PageCollection $pageCollection, \Closure $messageCallback)
    {
        $generatedPages = new PageCollection('sections');
        $sections = [];

        // collects sections
        /* @var $page Page */
        foreach ($pageCollection as $page) {
            if ($page->getSection() != '') {
                $sections[$page->getSection()][] = $page;
            }
        }
        // adds node pages to collection
        if (count($sections) > 0) {
            $menuWeight = 100;
            foreach ($sections as $section => $pages) {
                $pageId = Page::urlize(sprintf('%s', $section));
                if (!$pageCollection->has($pageId)) {
                    usort($pages, 'Cecil\Util::sortByDate');
                    $page = (new Page())
                        ->setId($pageId)
                        ->setPathname($pageId)
                        ->setTitle(ucfirst($section))
                        ->setNodeType(NodeType::SECTION)
                        ->setVariable('pages', $pages)
                        ->setVariable('date', reset($pages)->getDate())
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
