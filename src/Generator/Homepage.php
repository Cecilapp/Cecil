<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Collection as PageCollection;
use Cecil\Collection\Page\Page;
use Cecil\Page\NodeType;

/**
 * Class Homepage.
 */
class Homepage extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PageCollection $pageCollection, \Closure $messageCallback)
    {
        $generatedPages = new PageCollection();

        if (!$pageCollection->has('index')) {
            $filteredPages = $pageCollection->filter(function (Page $page) {
                return $page->getNodeType() === null
                && $page->getSection() == $this->config->get('site.paginate.homepage.section')
                && !empty($page->getBody());
            });
            $pages = $filteredPages->sortByDate()->toArray();

            /* @var $page Page */
            $page = (new Page())
                ->setId('index')
                ->setNodeType(NodeType::HOMEPAGE)
                ->setPathname(Page::urlize(''))
                ->setTitle('Home')
                ->setVariable('pages', $pages)
                ->setVariable('menu', [
                    'main' => ['weight' => 1],
                ]);
            $generatedPages->add($page);
        }

        return $generatedPages;
    }
}
