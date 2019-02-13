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
 * Class Pagination.
 */
class Pagination extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PagesCollection $pagesCollection, \Closure $messageCallback)
    {
        $generatedPages = new PagesCollection();

        $filteredPages = $pagesCollection->filter(function (Page $page) {
            return in_array($page->getType(), [Type::HOMEPAGE, Type::SECTION]);
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
            $totalpages = count($pages);
            if ($totalpages > $paginateMax) {
                $paginateCount = ceil($totalpages / $paginateMax);
                for ($i = 0; $i < $paginateCount; $i++) {
                    //$pagesInPagination = array_slice($pages, ($i * $paginateMax), $paginateMax);
                    $pagesInPagination = new \LimitIterator((new \IteratorIterator($pages)), ($i * $paginateMax), $paginateMax);
                    $alteredPage = clone $page;
                    // first page
                    $firstPath = Page::slugify(sprintf('%s', $path));
                    if ($i == 0) {
                        // ie: blog/page/1 -> blog
                        $pageId = Page::slugify(sprintf('%s', $path));
                        // homepage special case
                        if ($path == '') {
                            $pageId = 'index';
                        }
                        $currentPath = $firstPath;
                        $alteredPage
                            ->setId($pageId)
                            ->setPathname($currentPath)
                            ->setVariable('aliases', [
                                sprintf('%s/%s/%s', $path, $paginatePath, 1),
                            ]);
                    } else {
                        // ie: blog/page/2
                        $pageId = Page::slugify(sprintf('%s/%s/%s', $path, $paginatePath, $i + 1));
                        $currentPath = $pageId;
                        $alteredPage
                            ->setId($pageId)
                            ->setPathname($currentPath)
                            ->unVariable('menu');
                    }
                    $alteredPage->setVariable('url', $currentPath);
                    $alteredPage->setVariable('totalpages', $totalpages);
                    $alteredPage->setVariable('pages', $pagesInPagination);
                    // links
                    $pagination = ['self' => $currentPath];
                    $pagination += ['first' => $firstPath];
                    if ($i > 0) {
                        $pagination += ['prev' => Page::slugify(sprintf('%s/%s/%s', $path, $paginatePath, $i))];
                    }
                    if ($i < $paginateCount - 1) {
                        $pagination += ['next' => Page::slugify(sprintf('%s/%s/%s', $path, $paginatePath, $i + 2))];
                    }
                    $pagination += ['last' => Page::slugify(sprintf('%s/%s/%s', $path, $paginatePath, $paginateCount))];

                    var_dump($pagesInPagination);
                    die();

                    $alteredPage
                        ->setVariable('pagination', $pagination)
                        ->setVariable('date', $pagesInPagination->current()->getVariable('date'));

                    $generatedPages->add($alteredPage);
                }
            }
        }

        return $generatedPages;
    }
}
