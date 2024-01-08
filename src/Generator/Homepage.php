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
                $pageId = "$language/$pageId";
            }
            // creates a new index page...
            $page = (new Page($pageId))->setPath('')->setVariable('title', 'Home');
            // ... clones it if already exists
            if ($this->builder->getPages()->has($pageId)) {
                $page = clone $this->builder->getPages()->get($pageId);
            }
            /** @var \Cecil\Collection\Page\Page $page */
            $page->setType(Type::HOMEPAGE->value);
            // collects all pages
            $subPages = $this->builder->getPages()->filter(function (Page $page) use ($language) {
                return $page->getType() == Type::PAGE->value
                    && $page->getVariable('published') === true
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
                // sorts pages
                $pages = $subPages->sortBy($this->config->get('pages.sortby'));
                if ($page->hasVariable('sortby')) {
                    try {
                        $pages = $pages->sortBy($page->getVariable('sortby'));
                    } catch (RuntimeException $e) {
                        throw new RuntimeException(sprintf('In page "%s", %s', $page->getId(), $e->getMessage()));
                    }
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
            // add an alias redirection from the root directory if language path prefix is enabled for the default language
            if ($language == $this->config->getLanguageDefault() && (bool) $this->config->get('language.prefix') === true) {
                $page->setVariable('alias', '../');
            }
            $this->generatedPages->add($page);
        }
    }
}
