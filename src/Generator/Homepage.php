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
 * Class Homepage.
 */
class Homepage extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PagesCollection $pagesCollection, \Closure $messageCallback)
    {
        $generatedPages = new PagesCollection();

        if (!$pagesCollection->has('index')) {
            $filteredPages = $pagesCollection->filter(function (Page $page) {
                return ($page->getType() === null || $page->getType() === TYPE::PAGE)
                && $page->getSection() == $this->config->get('site.paginate.homepage.section')
                && !empty($page->getBody());
            });
            $pages = $filteredPages->sortByDate()->toArray();

            /* @var $page Page */
            $page = (new Page())
                ->setId('index')
                ->setType(Type::HOMEPAGE)
                ->setPathname(Page::slugify(''))
                ->setVariable('title', 'Home')
                ->setVariable('pages', $pages)
                ->setVariable('menu', [
                    'main' => ['weight' => 1],
                ]);
            $generatedPages->add($page);
        }

        return $generatedPages;
    }
}
