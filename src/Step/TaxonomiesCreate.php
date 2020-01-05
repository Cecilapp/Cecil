<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Taxonomy\Collection as VocabulariesCollection;
use Cecil\Collection\Taxonomy\Term as Term;
use Cecil\Collection\Taxonomy\Vocabulary as Vocabulary;
use Cecil\Exception\Exception;

/**
 * Create taxonomies collection.
 */
class TaxonomiesCreate extends AbstractStep
{
    /**
     * @var VocabulariesCollection
     */
    protected $vocabCollection;

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        if ($this->config->get('taxonomies')) {
            $this->createVocabulariesCollection();
            $this->collectTermsFromPages();
        }

        $this->builder->setTaxonomies($this->vocabCollection);
    }

    /**
     * Create a collection from the vocabularies configuration.
     */
    protected function createVocabulariesCollection()
    {
        // create an empty a vocabularies collection
        $this->vocabCollection = new VocabulariesCollection('taxonomies');
        /*
         * Adds each vocabulary to the collection.
         * ie:
         *   taxonomies:
         *     - tags: tag
         *     - categories: category
         */
        foreach (array_keys($this->config->get('taxonomies')) as $vocabulary) {
            /*
             * Disabled vocabulary?
             * ie:
             *   taxonomies:
             *     tags: disabled
             */
            if ($this->config->get("taxonomies.$vocabulary") == 'disabled') {
                continue;
            }

            $this->vocabCollection->add(new Vocabulary($vocabulary));
        }
    }

    /**
     * Collects vocabularies/terms from pages frontmatter.
     */
    protected function collectTermsFromPages()
    {
        /* @var $page Page */
        $pages = $this->builder->getPages()->sortByDate();
        foreach ($pages as $page) {
            // ie: tags
            foreach ($this->vocabCollection as $vocabulary) {
                $plural = $vocabulary->getId();
                /*
                 * ie:
                 *   tags: Tag 1, Tag 2
                 */
                if ($page->hasVariable($plural)) {
                    // converts a string list to an array
                    if (!is_array($page->getVariable($plural))) {
                        $page->setVariable($plural, [$page->getVariable($plural)]);
                    }
                    // adds each term to the vocabulary collection...
                    foreach ($page->getVariable($plural) as $termName) {
                        if (NULL === $termName) {
                            throw new Exception(\sprintf(
                                'Taxonomy "%s" of "%s" can\'t be empty.',
                                $plural,
                                $page->getId()
                            ));
                        }
                        $termId = Page::slugify($termName);
                        $term = (new Term($termId))->setName($termName);
                        $this->vocabCollection
                            ->get($plural)
                            ->add($term);
                        // ... and adds page to the term collection
                        $this->vocabCollection
                            ->get($plural)
                            ->get($termId)
                            ->add($page);
                    }
                }
            }
        }
    }
}
