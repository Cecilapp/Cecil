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

            // config
            $paginatePerPage = intval($this->config->get('site.paginate.max'));
            $paginatePath = $this->config->get('site.paginate.path');
            // page variables
            $path = $page->getPath();
            $pages = $page->getVariable('pages');

            // paginate
            $pagesTotal = count($pages);
            if ($pagesTotal > $paginatePerPage) {
                $paginatePagesCount = ceil($pagesTotal / $paginatePerPage);
                for ($i = 0; $i < $paginatePagesCount; $i++) {
                    $pagesInPagination = new \LimitIterator($pages->getIterator(), ($i * $paginatePerPage), $paginatePerPage);
                    $pagesInPagination = new PagesCollection($page->getId().'-page-'.($i + 1), iterator_to_array($pagesInPagination));
                    $alteredPage = clone $page;
                    // first page
                    $firstPath = Page::slugify(sprintf('%s', $path));
                    if ($i == 0) {
                        // ie: blog + blog/page/1 (alias)
                        $pageId = Page::slugify(sprintf('%s', $path));
                        // homepage special case
                        if ($path == '') {
                            $pageId = 'index';
                        }
                        $currentPath = $firstPath;
                        $alteredPage
                            ->setId($pageId)
                            ->setPath($firstPath)
                            ->setVariable('aliases', [
                                sprintf('%s/%s/%s', $path, $paginatePath, 1),
                            ]);
                    } else {
                        // ie: blog/page/2
                        $pageId = Page::slugify(sprintf('%s/%s/%s', $path, $paginatePath, $i + 1));
                        $currentPath = $pageId;
                        $alteredPage
                            ->setId($pageId)
                            ->setPath($currentPath)
                            ->unVariable('menu');
                    }
                    // create "pagination" variable
                    $pagination = [
                        'totalpages' => $pagesTotal,
                        'pages' => $pagesInPagination,
                    ];
                    // add links
                    $pagination['links'] = ['self' => $currentPath ?: 'index'];
                    $pagination['links'] += ['first' => $firstPath ?: 'index'];
                    if ($i == 1) {
                        $pagination['links'] += ['prev' => Page::slugify($path ?: 'index')];
                    }
                    if ($i > 1) {
                        $pagination['links'] += ['prev' => Page::slugify(sprintf('%s/%s/%s', $path, $paginatePath, $i))];
                    }
                    if ($i < $paginatePagesCount - 1) {
                        $pagination['links'] += ['next' => Page::slugify(sprintf('%s/%s/%s', $path, $paginatePath, $i + 2))];
                    }
                    $pagination['links'] += ['last' => Page::slugify(sprintf('%s/%s/%s', $path, $paginatePath, $paginatePagesCount))];
                    $alteredPage->setVariable('pagination', $pagination);
                    // update date with the first element of the collection
                    $alteredPage->setVariable('date', $pagesInPagination->getIterator()->current()->getVariable('date'));
                    $generatedPages->add($alteredPage);
                }
            }
        }

        return $generatedPages;
    }
}
