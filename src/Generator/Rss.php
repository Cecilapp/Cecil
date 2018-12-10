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
 * Class Rss.
 */
class Rss extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PageCollection $pageCollection, \Closure $messageCallback)
    {
        $generatedPages = new PageCollection();

        $filteredPages = $pageCollection->filter(function (Page $page) {
            //return in_array($page->getNodeType(), [NodeType::HOMEPAGE, NodeType::SECTION]);
            return in_array($page->getNodeType(), [NodeType::SECTION]);
        });

        /* @var $page Page */
        foreach ($filteredPages as $page) {
            //
            printf("%s\n", $page->getId());
            //
            /* @var $aliasPage Page */
            $rssPage = clone $page;
            $rssPage
                //->setId($page->getId().'/rss')
                //->setPathname(Page::urlize($page->getId().'/rss'))
                //->setTitle($page->getTitle().' - RSS')
                //->setNodeType(NodeType::SECTION)
                //->setSection($page->getSection())
                ->setLayout('rss.xml')
                ->setPermalink($page->getPathname().'/rss.xml')
                //->setDate($page->getDate())
                ;
            $generatedPages->add($rssPage);
        }

        return $generatedPages;
    }
}
