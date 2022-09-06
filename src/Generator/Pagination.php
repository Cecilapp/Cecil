<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;

/**
 * Class Generator\Pagination.
 */
class Pagination extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        if (false === $this->config->get('pagination.enabled')) {
            return;
        }

        // filters pages: home, sections and terms
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return in_array($page->getType(), [Type::HOMEPAGE, Type::SECTION, Type::TERM]);
        });
        /** @var Page $page */
        foreach ($filteredPages as $page) {
            // page config
            $path = $page->getPath();
            $pages = $page->getPages();
            $sortby = $page->getVariable('sortby');
            // no sub-pages?
            if ($pages === null) {
                continue;
            }
            // global pagination config
            $paginationPerPage = intval($this->config->get('pagination.max'));
            $paginationPath = $this->config->get('pagination.path');
            // page pagination config
            $pagePagination = $page->getVariable('pagination');
            if ($pagePagination) {
                if (isset($pagePagination['enabled']) && !$pagePagination['enabled']) {
                    continue;
                }
                if (isset($pagePagination['max'])) {
                    $paginationPerPage = intval($pagePagination['max']);
                }
                if (isset($pagePagination['path'])) {
                    $paginationPath = $pagePagination['path'];
                }
            }
            $pages = $pages->filter(function (Page $page) {
                return $page->getVariable('published');
            });
            $pagesTotal = count($pages);
            // abords pagination?
            if ($pagesTotal <= $paginationPerPage) {
                continue;
            }
            // sorts pages
            $pages = $pages->sortByDate();
            if ($sortby) {
                $sortMethod = \sprintf('sortBy%s', ucfirst($sortby));
                if (method_exists($pages, $sortMethod)) {
                    $pages = $pages->$sortMethod();
                }
            }

            // builds pagination
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
                $firstPath = Page::slugify(\sprintf('%s', $path));
                if ($i == 0) {
                    // ie: blog + blog/page/1 (alias)
                    $pageId = Page::slugify(\sprintf('%s', $path));
                    // homepage special case
                    if ($path == '') {
                        $pageId = 'index';
                    }
                    $currentPath = $firstPath;
                    $alteredPage
                        ->setId($pageId)
                        ->setPath($firstPath)
                        ->setVariable('alias', [
                            \sprintf('%s/%s/%s', $path, $paginationPath, 1),
                        ]);
                } else {
                    // ie: blog/page/2
                    $pageId = Page::slugify(\sprintf('%s/%s/%s', $path, $paginationPath, $i + 1));
                    $currentPath = $pageId;
                    $alteredPage
                        ->setId($pageId)
                        ->setVirtual(true)
                        ->setPath($currentPath)
                        ->unVariable('menu')
                        ->unVariable('alias')
                        ->unVariable('aliases') // backward compatibility
                        ->unVariable('langref')
                        ->setVariable('paginated', true);
                }
                // updates 'pagination' variable
                $pagination = [
                    'totalpages' => $pagesTotal,
                    'pages'      => $pagesInPagination,
                    'current'    => $i + 1,
                    'count'      => $paginationPagesCount,
                ];
                // adds links
                $pagination['links'] = ['self' => $currentPath ?: 'index'];
                $pagination['links'] += ['first' => $firstPath ?: 'index'];
                if ($i == 1) {
                    $pagination['links'] += ['prev' => Page::slugify($path ?: 'index')];
                }
                if ($i > 1) {
                    $pagination['links'] += ['prev' => Page::slugify(\sprintf(
                        '%s/%s/%s',
                        $path,
                        $paginationPath,
                        $i
                    ))];
                }
                if ($i < $paginationPagesCount - 1) {
                    $pagination['links'] += ['next' => Page::slugify(\sprintf(
                        '%s/%s/%s',
                        $path,
                        $paginationPath,
                        $i + 2
                    ))];
                }
                $pagination['links'] += ['last' => Page::slugify(\sprintf(
                    '%s/%s/%s',
                    $path,
                    $paginationPath,
                    $paginationPagesCount
                ))];
                $alteredPage->setPagination($pagination);
                // updates date with the first element of the collection
                $alteredPage->setVariable('date', $pagesInPagination->first()->getVariable('date'));

                $this->generatedPages->add($alteredPage);
            }
        }
    }
}
