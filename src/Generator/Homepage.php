<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
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
        $subPages = $this->builder->getPages()->filter(function (Page $page) {
            return $page->getType() == TYPE::PAGE
                && $page->getId() != 'index' // excludes homepage
                && $page->isVirtual() === false // excludes virtual pages
;
        });
        /** @var \Cecil\Collection\Page\Collection $subPages */
        $pages = $subPages->sortByDate();

        // creates a new index page...
        $page = (new Page('index'))->setPath('')->setVariable('title', 'Home');
        // ... clones it if already exists
        if ($this->builder->getPages()->has('index')) {
            $page = clone $this->builder->getPages()->get('index');
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
