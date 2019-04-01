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
 * Class Pagination.
 */
class Pagination extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        if (false === $this->config->get('site.pagination.enabled')) {
            return;
        }

        // global config
        $configPaginationPerPage = intval($this->config->get('site.pagination.max'));
        $configPaginationPath = $this->config->get('site.pagination.path');

        // filter pages: home and sections
        $filteredPages = $this->pagesCollection->filter(function (Page $page) {
            return in_array($page->getType(), [Type::HOMEPAGE, Type::SECTION]);
        });
        /* @var $page Page */
        foreach ($filteredPages as $page) {
            $path = $page->getPath();
            $pages = $page->getVariable('pages');
            $sortby = $page->getVariable('sortby');
            $paginate = $page->getVariable('paginate');
            $paginationPerPage = $configPaginationPerPage;
            $paginationPath = $configPaginationPath;
            // page config
            if ($paginate) {
                if (array_key_exists('enabled', $paginate) && !$paginate['enabled']) {
                    continue;
                }
                if (array_key_exists('max', $paginate)) {
                    $paginationPerPage = $paginate['max'];
                }
                if (array_key_exists('path', $paginate)) {
                    $paginationPath = $paginate['path'];
                }
            }
            // abord pagination?
            if (count($pages) <= $paginationPerPage) {
                continue;
            }
            // sort
            $pages = $pages->sortByDate();
            if ($sortby) {
                $sortMethod = sprintf('sortBy%s', ucfirst($sortby));
                if (method_exists($pages, $sortMethod)) {
                    $pages = $pages->$sortMethod();
                }
            }

            // build pagination
            $pagesTotal = count($pages);
            if ($pagesTotal > $paginationPerPage) {
                $paginationPagesCount = ceil($pagesTotal / $paginationPerPage);
                for ($i = 0; $i < $paginationPagesCount; $i++) {
                    $pagesInPagination = new \LimitIterator(
                        $pages->getIterator(),
                        ($i * $paginationPerPage),
                        $paginationPerPage
                    );
                    $pagesInPagination = new PagesCollection(
                        $page->getId().'-page-'.($i + 1),
                        iterator_to_array($pagesInPagination)
                    );
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
                                sprintf('%s/%s/%s', $path, $paginationPath, 1),
                            ]);
                    } else {
                        // ie: blog/page/2
                        $pageId = Page::slugify(sprintf('%s/%s/%s', $path, $paginationPath, $i + 1));
                        $currentPath = $pageId;
                        $alteredPage
                            ->setId($pageId)
                            ->setVirtual(true)
                            ->setPath($currentPath)
                            ->unVariable('menu')
                            ->setVariable('paginated', true);
                    }
                    // create "pagination" variable
                    $pagination = [
                        'totalpages' => $pagesTotal,
                        'pages'      => $pagesInPagination,
                    ];
                    // add links
                    $pagination['links'] = ['self' => $currentPath ?: 'index'];
                    $pagination['links'] += ['first' => $firstPath ?: 'index'];
                    if ($i == 1) {
                        $pagination['links'] += ['prev' => Page::slugify($path ?: 'index')];
                    }
                    if ($i > 1) {
                        $pagination['links'] += ['prev' => Page::slugify(sprintf(
                            '%s/%s/%s',
                            $path,
                            $paginationPath,
                            $i
                        ))];
                    }
                    if ($i < $paginationPagesCount - 1) {
                        $pagination['links'] += ['next' => Page::slugify(sprintf(
                            '%s/%s/%s',
                            $path,
                            $paginationPath,
                            $i + 2
                        ))];
                    }
                    $pagination['links'] += ['last' => Page::slugify(sprintf(
                        '%s/%s/%s',
                        $path,
                        $paginationPath,
                        $paginationPagesCount
                    ))];
                    $alteredPage->setVariable('pagination', $pagination);
                    // update date with the first element of the collection
                    $alteredPage->setVariable('date', $pagesInPagination->first()->getVariable('date'));
                    $this->generatedPages->add($alteredPage);
                }
            }
        }
    }
}
