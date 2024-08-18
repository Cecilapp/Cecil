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
        if (is_dir($this->config->getPagesPath()) && $this->hasTaxonomies()) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        if ($this->config->get('taxonomies')) {
            $this->createVocabulariesCollection();
            $this->builder->getLogger()->info('Vocabularies collection created', ['progress' => [1, 2]]);
            $this->collectTermsFromPages();
            $this->builder->getLogger()->info('Terms collection created', ['progress' => [2, 2]]);
        }

        $this->builder->setTaxonomies($this->vocabCollection);
    }

    /**
     * Creates a collection from the vocabularies configuration.
     */
    protected function createVocabulariesCollection(): void
    {
        // creates a vocabularies collection for each language
        foreach ($this->config->getLanguages() as $language) {
            $this->vocabCollection[$language['code']] = new VocabulariesCollection('taxonomies');
            /*
             * Adds each vocabulary to the collection.
             * e.g.:
             * taxonomies:
             *   tags: tag
             *   categories: category
             * -> tags, categories
             */
            foreach (array_keys((array) $this->config->get('taxonomies', $language['code'], false)) as $vocabulary) {
                if ($this->config->get("taxonomies.$vocabulary", $language['code'], false) == 'disabled') {
                    continue;
                }
                $this->vocabCollection[$language['code']]->add(new Vocabulary($vocabulary));
            }
        }
    }

    /**
     * Collects vocabularies/terms from pages front matter.
     */
    protected function collectTermsFromPages(): void
    {
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return $page->getVariable('published')
                && \in_array($page->getVariable('language', $this->config->getLanguageDefault()), array_column($this->config->getLanguages(), 'code'));
        })->sortByDate();
        foreach ($filteredPages as $page) {
            $language = (string) $page->getVariable('language', $this->config->getLanguageDefault());
            // e.g.:tags
            foreach ($this->vocabCollection[$language] as $vocabulary) {
                $plural = $vocabulary->getId();
                /*
                 * e.g.:
                 * tags: Tag 1, Tag 2
                 */
                if ($page->hasVariable($plural)) {
                    // converts a string list to an array...
                    if (!\is_array($page->getVariable($plural))) {
                        $page->setVariable($plural, [$page->getVariable($plural)]);
                    }
                    // ... and removes duplicate terms
                    $page->setVariable($plural, array_unique($page->getVariable($plural)));
                    // adds each term to the vocabulary collection...
                    foreach ($page->getVariable($plural) as $termName) {
                        if ($termName === null) {
                            throw new RuntimeException(\sprintf(
                                'Taxonomy "%s" of "%s" can\'t be empty.',
                                $plural,
                                $page->getId()
                            ));
                        }
                        // e.g.: "Tag 1" -> "tags/tag-1"
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
