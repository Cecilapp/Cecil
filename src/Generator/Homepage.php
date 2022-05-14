<?php declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Exception\RuntimeException;

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
        // creates a new index page...
        $page = (new Page('index'))->setPath('')->setVariable('title', 'Home');
        // ... clones it if already exists
        if ($this->builder->getPages()->has('index')) {
            $page = clone $this->builder->getPages()->get('index');
        }
        // collects all pages
        $subPages = $this->builder->getPages()->filter(function (Page $page) {
            return $page->getType() == TYPE::PAGE
                && $page->isVirtual() === false // excludes virtual pages
                && $page->getVariable('exclude') !== true;
        });
        /** @var \Cecil\Collection\Page\Page $page */
        if ($page->hasVariable('pagesfrom') && $this->builder->getPages()->has($page->getVariable('pagesfrom'))) {
            $subPages = $this->builder->getPages()->get($page->getVariable('pagesfrom'))->getVariable('pages');
        }
        // sorts
        /** @var \Cecil\Collection\Page\Collection $subPages */
        /** @var \Cecil\Collection\Page\Page $page */
        $pages = $subPages->sortByDate();
        if ($page->hasVariable('sortby')) {
            $sortMethod = \sprintf('sortBy%s', ucfirst((string) $page->getVariable('sortby')));
            if (!method_exists($pages, $sortMethod)) {
                throw new RuntimeException(\sprintf('In "%s" section "%s" is not a valid value for "sortby" variable.', $page->getId(), $page->getVariable('sortby')));
            }
            $pages = $pages->$sortMethod();
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
