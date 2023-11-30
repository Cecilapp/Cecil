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
use Cecil\Collection\Taxonomy\Vocabulary;

/**
 * Class Generator\Taxonomy.
 */
class Taxonomy extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        foreach ($this->config->getLanguages() as $lang) {
            $language = $lang['code'];
            if ($this->config->get('taxonomies', $language, false) && $this->builder->getTaxonomies($language) !== null) {
                /** @var Vocabulary $vocabulary */
                foreach ($this->builder->getTaxonomies($language) as $vocabulary) {
                    $plural = $vocabulary->getId();
                    $singular = $this->config->get("taxonomies.$plural", $language, false);
                    if (\count($vocabulary) > 0) {
                        $date = date('Y-m-d');
                        /*
                        * Creates $plural/$term pages (list of pages)
                        * e.g.: /tags/tag-1/
                        */
                        foreach ($vocabulary as $term) {
                            $pageId = $path = Page::slugify($term->getId());
                            if ($language != $this->config->getLanguageDefault()) {
                                $pageId = "$language/$pageId";
                            }
                            $pages = $term->sortByDate();
                            $date = $pages->first()->getVariable('date');
                            // creates page for each term
                            $page = (new Page($pageId))
                                ->setPath($path)
                                ->setVariable('title', $term->getName())
                                ->setVariable('date', $date)
                                ->setVariable('language', $language);
                            if ($this->builder->getPages()->has($pageId)) {
                                $page = clone $this->builder->getPages()->get($pageId);
                            }
                            $page->setType(Type::TERM)
                                ->setPages($pages)
                                ->setVariable('term', $term->getId())
                                ->setVariable('plural', $plural)
                                ->setVariable('singular', $singular);
                            $this->generatedPages->add($page);
                        }
                        /*
                        * Creates $plural pages (list of terms)
                        * e.g.: /tags/
                        */
                        $pageId = $path = Page::slugify($plural);
                        if ($language != $this->config->getLanguageDefault()) {
                            $pageId = "$language/$pageId";
                        }
                        $page = (new Page($pageId))->setVariable('title', ucfirst($plural))
                            ->setPath($path);
                        if ($this->builder->getPages()->has($pageId)) {
                            $page = clone $this->builder->getPages()->get($pageId);
                            $page->unSection();
                        }
                        // creates page for each plural
                        $page->setType(Type::VOCABULARY)
                            ->setPath($path)
                            ->setTerms($vocabulary)
                            ->setVariable('date', $date)
                            ->setVariable('language', $language)
                            ->setVariable('plural', $plural)
                            ->setVariable('singular', $singular);
                        // human readable title
                        if ($page->getVariable('title') == 'index') {
                            $page->setVariable('title', $plural);
                        }
                        // adds page only if a template exist
                        try {
                            $this->generatedPages->add($page);
                        } catch (\Exception $e) {
                            printf("%s\n", $e->getMessage());
                            unset($page); // do not adds page
                        }
                    }
                }
            }
        }
    }
}
