<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;

/**
 * Class Generator\Homepage.
 */
class Homepage extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        $subPages = $this->pagesCollection->filter(function (Page $page) {
            return $page->getType() == TYPE::PAGE
                && $page->getId() != 'index'; // exclude homepage
        });
        /** @var \Cecil\Collection\Page\Collection $subPages */
        $pages = $subPages->sortByDate();

        // create new index page...
        $page = (new Page('index'))->setPath('')->setVariable('title', 'Home');
        // ... clone it if already exists
        if ($this->pagesCollection->has('index')) {
            $page = clone $this->pagesCollection->get('index');
        }
        /** @var \Cecil\Collection\Page\Page $page */
        $page->setType(Type::HOMEPAGE)
            ->setVariable('pages', $pages);
        if ($pages->first()) {
            $page->setVariable('date', $pages->first()->getVariable('date'));
        }
        // default menu
        if (!$page->getVariable('menu')) {
            $page->setVariable('menu', [
                'main' => ['weight' => 0],
            ]);
        }
        $this->generatedPages->add($page);
    }
}
