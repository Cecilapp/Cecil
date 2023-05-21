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
        if ($this->config->get('pagination.enabled') === false) {
            return;
        }

        // filters list pages (home, sections and terms)
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return \in_array($page->getType(), [Type::HOMEPAGE, Type::SECTION, Type::TERM]);
        });
        /** @var Page $page */
        foreach ($filteredPages as $page) {
            $pages = $page->getPages()->filter(function (Page $page) {
                return $page->getVariable('published');
            });
            // if no sub-pages: by-pass
            if ($pages === null) {
                continue;
            }
            $path = $page->getPath();
            $sortby = $page->getVariable('sortby');
            // site pagination configuration
            $paginationPerPage = intval($this->config->get('pagination.max') ?? 5);
            $paginationPath = (string) $this->config->get('pagination.path') ?? 'page';
            // page pagination configuration
            $pagePagination = $page->getVariable('pagination');
            if ($pagePagination) {
                if (isset($pagePagination['enabled']) && $pagePagination['enabled'] == false) {
                    continue;
                }
                if (isset($pagePagination['max'])) {
                    $paginationPerPage = intval($pagePagination['max']);
                }
                if (isset($pagePagination['path'])) {
                    $paginationPath = (string) $pagePagination['path'];
                }
            }
            $pagesTotal = count($pages);
            // is pagination not necessary?
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
            // builds paginator
            $paginatorPagesCount = \intval(ceil($pagesTotal / $paginationPerPage));
            for ($i = 0; $i < $paginatorPagesCount; $i++) {
                $itPagesInPagination = new \LimitIterator($pages->getIterator(), ($i * $paginationPerPage), $paginationPerPage);
                $pagesInPagination = new PagesCollection(
                    $page->getId() . '-page-' . ($i + 1),
                    iterator_to_array($itPagesInPagination)
                );
                $alteredPage = clone $page;
                // first page (ie: blog/page/1 -> blog)
                if ($i == 0) {
                    $pageId = $page->getId();
                    $alteredPage
                        ->setVariable('alias', [
                            \sprintf('%s/%s/%s', $path, $paginationPath, 1),
                        ]);
                // others pages (ie: blog/page/X)
                } else {
                    $pageId = Page::slugify(\sprintf('%s/%s/%s', $page->getId(), $paginationPath, $i + 1));
                    $alteredPage
                        ->setId($pageId)
                        ->setVirtual(true)
                        ->setPath(Page::slugify(\sprintf('%s/%s/%s', $path, $paginationPath, $i + 1)))
                        ->unVariable('menu')
                        ->unVariable('alias')
                        ->unVariable('aliases') // backward compatibility
                        ->unVariable('langref')
                        ->setVariable('paginated', true);
                }
                // set paginator values
                $paginator = [
                    'pages'       => $pagesInPagination,
                    'pages_total' => $pagesTotal,
                    'totalpages'  => $pagesTotal, // backward compatibility
                    'count'       => $paginatorPagesCount,
                    'current'     => $i + 1,
                ];
                // adds links
                $paginator['links'] = ['first' => $page->getId() ?: 'index'];
                if ($i == 1) {
                    $paginator['links'] += ['prev' => $page->getId() ?: 'index'];
                }
                if ($i > 1) {
                    $paginator['links'] += ['prev' => Page::slugify(\sprintf(
                        '%s/%s/%s',
                        $page->getId(),
                        $paginationPath,
                        $i
                    ))];
                }
                $paginator['links'] += ['self' => $pageId ?: 'index'];
                if ($i < $paginatorPagesCount - 1) {
                    $paginator['links'] += ['next' => Page::slugify(\sprintf(
                        '%s/%s/%s',
                        $page->getId(),
                        $paginationPath,
                        $i + 2
                    ))];
                }
                $paginator['links'] += ['last' => Page::slugify(\sprintf(
                    '%s/%s/%s',
                    $page->getId(),
                    $paginationPath,
                    $paginatorPagesCount
                ))];
                $paginator['links'] += ['path' => Page::slugify(\sprintf('%s/%s', $page->getId(), $paginationPath))];
                // set paginator to cloned page
                $alteredPage->setPaginator($paginator);
                $alteredPage->setVariable('pagination', $paginator); // backward compatibility
                // updates date with the first element of the collection
                $alteredPage->setVariable('date', $pagesInPagination->first()->getVariable('date'));

                $this->generatedPages->add($alteredPage);
            }
        }
    }
}
