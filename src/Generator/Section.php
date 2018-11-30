<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

use PHPoole\Collection\Collection as PageCollection;
use PHPoole\Collection\Page\Page;
use PHPoole\Page\NodeType;

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
        $generatedPages = new PageCollection();
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
                if (!$pageCollection->has($section.'/')) {
                    usort($pages, 'PHPoole\Util::sortByDate');
                    $page = (new Page())
                        ->setId(Page::urlize(sprintf('%s/', $section)))
                        ->setPathname(Page::urlize(sprintf('%s', $section)))
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
