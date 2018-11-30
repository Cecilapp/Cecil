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
 * Class Pagination.
 */
class Pagination extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PageCollection $pageCollection, \Closure $messageCallback)
    {
        $generatedPages = new PageCollection();

        $filteredPages = $pageCollection->filter(function (Page $page) {
            return in_array($page->getNodeType(), [NodeType::HOMEPAGE, NodeType::SECTION]);
        });

        /* @var $page Page */
        foreach ($filteredPages as $page) {
            if ($this->config->get('site.paginate.disabled')) {
                return $generatedPages;
            }

            $paginateMax = intval($this->config->get('site.paginate.max'));
            $paginatePath = $this->config->get('site.paginate.path');
            $pages = $page->getVariable('pages');
            $path = $page->getPathname();

            // paginate
            if (count($pages) > $paginateMax) {
                $paginateCount = ceil(count($pages) / $paginateMax);
                for ($i = 0; $i < $paginateCount; $i++) {
                    $pagesInPagination = array_slice($pages, ($i * $paginateMax), $paginateMax);
                    $alteredPage = clone $page;
                    // first page, if homepage id = 'index'
                    if ($i == 0) {
                        $alteredPage
                            ->setId(Page::urlize(sprintf('%s/%s', $path, $path == '' ? 'index' : '')))
                            ->setPathname(Page::urlize(sprintf('%s', $path)))
                            ->setVariable('aliases', [
                                sprintf('%s/%s/%s', $path, $paginatePath, 1),
                            ]);
                    } else {
                        $alteredPage
                            ->setId(Page::urlize(sprintf('%s/%s/%s/', $path, $paginatePath, $i + 1)))
                            ->setPathname(Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i + 1)))
                            ->unVariable('menu');
                    }
                    // pagination
                    $pagination = ['pages' => $pagesInPagination];
                    if ($i > 0) {
                        $pagination += ['prev' => Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i))];
                    }
                    if ($i < $paginateCount - 1) {
                        $pagination += ['next' => Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i + 2))];
                    }
                    $alteredPage
                        ->setVariable('pagination', $pagination)
                        ->setVariable('date', reset($pagination['pages'])->getDate());

                    $generatedPages->add($alteredPage);
                }
            }
        }

        return $generatedPages;
    }
}
