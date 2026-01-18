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

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;

/**
 * Homepage generator class.
 *
 * This class generates the homepage for each language defined in the configuration.
 * It creates a new index page for each language, collects all pages of that language,
 * sorts them, and sets the necessary variables for the homepage.
 * It also handles the case where the homepage already exists by cloning it.
 * Additionally, it sets the default "main" menu and adds an alias redirection
 * from the root directory if the language prefix is enabled for the default language.
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
            if ($this->builder->getPages() && $this->builder->getPages()->has($pageId)) {
                $page = clone $this->builder->getPages()->get($pageId);
            }
            /** @var \Cecil\Collection\Page\Page $page */
            $page->setType(Type::HOMEPAGE->value);
            // collects all pages
            $pages = null;
            if ($this->builder->getPages()) {
                $pages = $this->builder->getPages()->filter(function (Page $page) use ($language) {
                    return $page->getType() == Type::PAGE->value
                        && $page->getVariable('published') === true
                        && ($page->getVariable('excluded') !== true && $page->getVariable('exclude') !== true)
                        && $page->isVirtual() === false
                        && $page->getVariable('language') == $language;
                });
            }
            // or collects pages from a specified section
            /** @var \Cecil\Collection\Page\Collection $pages */
            if ($page->hasVariable('pagesfrom') && $this->builder->getPages() && $this->builder->getPages()->has((string) $page->getVariable('pagesfrom'))) {
                $pages = $this->builder->getPages()->get((string) $page->getVariable('pagesfrom'))->getPages();
            }
            if ($pages instanceof \Cecil\Collection\Page\Collection) {
                // sorts pages
                $sortBy = $page->getVariable('sortby') ?? $this->config->get('pages.sortby');
                $pages = $pages->sortBy($sortBy);
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
            if ($language == $this->config->getLanguageDefault() && $this->config->isEnabled('language.prefix')) {
                $page->setVariable('alias', '../');
            }
            $this->generatedPages->add($page);
        }
    }
}
