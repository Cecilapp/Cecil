<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Util;

/**
 * Pagination generator.
 *
 * This generator creates paginated pages for sections, terms, and the homepage.
 * It filters the pages based on their type, checks for pagination settings,
 * and generates paginated pages accordingly.
 * If pagination is disabled globally, a list page can still enable it with `pagination.enabled: true`.
 * The first page is the list page itself, updated in place (not cloned), so that references
 * held by other pages (e.g.: "parent", "sections", "prev" and "next") remain the instances
 * that will be rendered. Other pages are clones of it.
 */
class Pagination extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function generate(): void
    {
        // is pagination enabled globally?
        $enabledGlobally = $this->config->isEnabled('pages.pagination');

        // filters list pages (home, sections and terms)
        $listPages = $this->builder->getPages()->filter(function (Page $page) {
            return \in_array($page->getType(), [Type::HOMEPAGE->value, Type::SECTION->value, Type::TERM->value]);
        });
        /** @var Page $page */
        foreach ($listPages as $page) {
            // if no pages: continue
            if ($page->getPages() === null) {
                continue;
            }
            $pages = $page->getPages()->filter(function (Page $page) {
                return $page->getType() == Type::PAGE->value && $page->getVariable('published');
            });
            $path = $page->getPath();
            // site configuration
            $paginationPerPage = \intval($this->config->get('pages.pagination.max') ?? 5);
            $paginationPath = $this->config->get('pages.pagination.path') ?? 'page';
            // page configuration
            $pagePagination = $page->getVariable('pagination');
            if ($pagePagination === false || (isset($pagePagination['enabled']) && $pagePagination['enabled'] === false)) {
                continue;
            }
            // disabled globally: only list pages that explicitly enable it are paginated
            if (!$enabledGlobally && !(\is_array($pagePagination) && ($pagePagination['enabled'] ?? false) === true)) {
                continue;
            }
            if (isset($pagePagination['max'])) {
                $paginationPerPage = \intval($pagePagination['max']);
            }
            if (isset($pagePagination['path'])) {
                $paginationPath = (string) $pagePagination['path'];
            }
            // total pages
            $pagesTotal = \count($pages);
            // is pagination not necessary?
            if ($pagesTotal <= $paginationPerPage) {
                continue;
            }
            // sorts pages
            $sortBy = $page->getVariable('sortby') ?? $this->config->get('pages.sortby');
            $pages = $pages->sortBy($sortBy);
            // builds paginator
            $paginatorPagesCount = \intval(ceil($pagesTotal / $paginationPerPage));
            for ($i = 0; $i < $paginatorPagesCount; $i++) {
                $itPagesInPagination = new \LimitIterator($pages->getIterator(), $i * $paginationPerPage, $paginationPerPage);
                $pagesInPagination = new PagesCollection(
                    $page->getId() . '-page-' . ($i + 1),
                    iterator_to_array($itPagesInPagination)
                );
                // first page (ie: blog/page/1 -> blog) is the list page itself
                $alteredPage = $i > 0 ? clone $page : $page;
                $pageId = $page->getId();
                $alteredPage
                    ->setVariable('alias', [
                        \sprintf('%s/%s/%s', $path, $paginationPath, 1),
                    ]);
                // others pages (ie: blog/page/X)
                if ($i > 0) {
                    $pageId = Util\Slugifier::slugify(\sprintf('%s/%s/%s', $page->getId(), $paginationPath, $i + 1));
                    $alteredPage
                        ->setId($pageId)
                        ->setPath(Util\Slugifier::slugify(\sprintf('%s/%s/%s', $path, $paginationPath, $i + 1)))
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
                    $paginator['links'] += ['prev' => Util\Slugifier::slugify(\sprintf(
                        '%s/%s/%s',
                        $page->getId(),
                        $paginationPath,
                        $i
                    ))];
                }
                $paginator['links'] += ['self' => $pageId ?: 'index'];
                if ($i < $paginatorPagesCount - 1) {
                    $paginator['links'] += ['next' => Util\Slugifier::slugify(\sprintf(
                        '%s/%s/%s',
                        $page->getId(),
                        $paginationPath,
                        $i + 2
                    ))];
                }
                $paginator['links'] += ['last' => Util\Slugifier::slugify(\sprintf(
                    '%s/%s/%s',
                    $page->getId(),
                    $paginationPath,
                    $paginatorPagesCount
                ))];
                $paginator['links'] += ['path' => Util\Slugifier::slugify(\sprintf('%s/%s', $page->getId(), $paginationPath))];
                // set paginator to the page
                $alteredPage->setPaginator($paginator);
                $alteredPage->setVariable('pagination', $paginator); // backward compatibility
                // updates date with the first element of the collection
                $alteredPage->setVariable('date', $pagesInPagination->first()?->getVariable('date'));

                $this->generatedPages->add($alteredPage);
            }
        }
    }
}
