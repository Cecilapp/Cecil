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

namespace Cecil\Step\Taxonomies;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Taxonomy\Collection as VocabulariesCollection;
use Cecil\Collection\Taxonomy\Term;
use Cecil\Collection\Taxonomy\Vocabulary;
use Cecil\Exception\RuntimeException;
use Cecil\Step\AbstractStep;

/**
 * Creates taxonomies collection.
 */
class Create extends AbstractStep
{
    /** @var array */
    protected $vocabCollection;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Creating taxonomies';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        if (empty($this->config->getVocabularies()) || !is_iterable($this->builder->getPages()) || \count($this->builder->getPages()) == 0) {
            $this->canProcess = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        if ($this->config->get('taxonomies')) {
            $this->createVocabulariesCollection();
            //$this->builder->getLogger()->info('Vocabularies collection created', ['progress' => [1, 2]]);
            $this->collectTermsFromPages();
            $this->builder->getLogger()->info('Terms collection created', ['progress' => [2, 2]]);
        }

        $this->builder->setTaxonomies($this->vocabCollection);
    }

    /**
     * Creates vocabularies collections from the taxonomies configuration.
     *
     * ```yaml
     * taxonomies:
     *   tags: tag
     *   categories: category
     * ```
     */
    protected function createVocabulariesCollection(): void
    {
        $total = \count($this->config->getVocabularies(), COUNT_RECURSIVE) - count($this->config->getLanguages());
        $count = 0;

        // creates a vocabularies collection for each language
        foreach ($this->config->getVocabularies() as $language => $vocabularies) {
            $this->vocabCollection[$language] = new VocabulariesCollection('taxonomies');
            /*
             * Adds each vocabulary to the collection.
             * e.g.:
             *
             * -> tags, categories
             */
            foreach ($vocabularies as $vocabulary) {
                $count++;
                $this->vocabCollection[$language]->add(new Vocabulary($vocabulary));
                $this->builder->getLogger()->info(\sprintf('Vocabulary collection "%s/%s" created', $language, $vocabulary), ['progress' => [$count, $total]]);
            }
        }
    }

    /**
     * Collects vocabularies/terms from pages front matter.
     */
    protected function collectTermsFromPages(): void
    {
        // filters pages by published statu, language and sorts them by date
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return $page->getVariable('published')
                && \in_array($page->getVariable('language', $this->config->getLanguageDefault()), array_column($this->config->getLanguages(), 'code'));
        })->sortByDate();
        // scan each page
        foreach ($filteredPages as $page) {
            $language = (string) $page->getVariable('language', $this->config->getLanguageDefault());
            foreach ($this->vocabCollection[$language] as $vocabulary) {
                $plural = $vocabulary->getId();
                // if page has a vocabulary
                if ($page->hasVariable($plural)) {
                    // converts a terms string list to an array...
                    if (!\is_array($page->getVariable($plural))) {
                        $page->setVariable($plural, [$page->getVariable($plural)]);
                    }
                    // ... and removes duplicate terms
                    $page->setVariable($plural, array_unique($page->getVariable($plural)));
                    // for each term
                    foreach ($page->getVariable($plural) as $termName) {
                        if ($termName === null) {
                            throw new RuntimeException(\sprintf('Taxonomy "%s" of "%s" can\'t be empty.', $plural, $page->getId()));
                        }





                        // adds term to the vocabulary collection...
                        $termId = Page::slugify($plural . '/' . (string) $termName);
                        $term = (new Term($termId))->setName((string) $termName);
                        $this->vocabCollection[$language]
                            ->get($plural)
                            ->add($term);
                        // ... and adds page to the term collection
                        $this->vocabCollection[$language]
                            ->get($plural)
                            ->get($termId)
                            ->add($page);
                    }
                }
            }
        }
        // adds each page
        foreach ($filteredPages as $page) {
            //
        }
    }

    /**
     * Checks if there is enabled taxonomies in config.
     */
    private function hasTaxonomies(): bool
    {
        $taxonomiesCount = 0;
        foreach ($this->config->getLanguages() as $language) {
            foreach (array_keys((array) $this->config->get('taxonomies')) as $vocabulary) {
                if ($this->config->get("taxonomies.$vocabulary", $language['code'], false) != 'disabled') {
                    $taxonomiesCount++;
                }
            }
        }

        return $taxonomiesCount > 0;
    }
}
