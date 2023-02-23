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
        foreach ($this->config->getLanguages() as $lang) {
            $language = $lang['code'];
            $pageId = 'index';
            if ($language != $this->config->getLanguageDefault()) {
                $pageId = \sprintf('index.%s', $language);
            }
            // creates a new index page...
            $page = (new Page($pageId))->setPath('')->setVariable('title', 'Home');
            // ... clones it if already exists
            if ($this->builder->getPages()->has($pageId)) {
                $page = clone $this->builder->getPages()->get($pageId);
            }
            /** @var \Cecil\Collection\Page\Page $page */
            $page->setType(Type::HOMEPAGE);
            // collects all pages
            $subPages = $this->builder->getPages()->filter(function (Page $page) use ($language) {
                return $page->getType() == TYPE::PAGE
                    && $page->isVirtual() === false
                    && $page->getVariable('exclude') !== true
                    && $page->getVariable('language') == $language;
            });
            // collects pages of a section
            /** @var \Cecil\Collection\Page\Collection $subPages */
            if ($page->hasVariable('pagesfrom') && $this->builder->getPages()->has((string) $page->getVariable('pagesfrom'))) {
                $subPages = $this->builder->getPages()->get((string) $page->getVariable('pagesfrom'))->getPages();
            }
            if ($subPages instanceof \Cecil\Collection\Page\Collection) {
                // sorts
                $pages = $subPages->sortByDate();
                if ($page->hasVariable('sortby')) {
                    $sortMethod = \sprintf('sortBy%s', ucfirst((string) $page->getVariable('sortby')));
                    if (!method_exists($pages, $sortMethod)) {
                        throw new RuntimeException(\sprintf('In page "%s" "%s" is not a valid value for "sortby" variable.', $page->getId(), $page->getVariable('sortby')));
                    }
                    $pages = $pages->$sortMethod();
                }
                $page->setPages($pages);
                if ($pages->first()) {
                    $page->setVariable('date', $pages->first()->getVariable('date'));
                }
            }
            // set default "main" menu
            if (!$page->getVariable('menu')) {
                $page->setVariable('menu', ['main' => ['weight' => 0]]);
            }
            $this->generatedPages->add($page);
        }
    }
}
