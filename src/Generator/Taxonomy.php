<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Collection\Taxonomy\Collection as VocabulariesCollection;
use Cecil\Collection\Taxonomy\Term as Term;
use Cecil\Collection\Taxonomy\Vocabulary as Vocabulary;
use Cecil\Exception\Exception;

/**
 * Class Taxonomy.
 */
class Taxonomy extends AbstractGenerator implements GeneratorInterface
{
    /* @var VocabulariesCollection */
    protected $vocabulariesCollection;

    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        if ($this->config->get('site.taxonomies')) {
            $this->createVocabulariesCollection();
            $this->collectTermsFromPages();
            $this->generateTaxonomiesPages();
        }
    }

    /**
     * Create a collection from the vocabularies configuration.
     */
    protected function createVocabulariesCollection()
    {
        // create an empty a vocabularies collection
        $this->vocabulariesCollection = new VocabulariesCollection('taxonomies');

        // adds each vocabulary to the collection
        foreach (array_keys($this->config->get('site.taxonomies')) as $vocabulary) {
            /*
             * ie:
             *   taxonomies:
             *     tags: disabled
             */
            if ($this->config->get("site.taxonomies.$vocabulary") == 'disabled') {
                continue;
            }

            $this->vocabulariesCollection->add(new Vocabulary($vocabulary));
        }
    }

    /**
     * Collects vocabularies/terms from pages frontmatter.
     */
    protected function collectTermsFromPages()
    {
        /* @var $page Page */
        $pages = $this->pagesCollection->sortByDate();
        foreach ($pages as $page) {
            foreach ($this->vocabulariesCollection as $vocabulary) {
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
                        $termId = mb_strtolower($termName);
                        $term = new Term($termId);
                        $this->vocabulariesCollection
                            ->get($plural)
                            ->add($term);
                        // ... and adds page to the term collection
                        $this->vocabulariesCollection
                            ->get($plural)
                            ->get($termId)
                            ->add($page);
                    }
                }
            }
        }
    }

    /**
     * Generate taxonomies pages.
     */
    protected function generateTaxonomiesPages()
    {
        /* @var $vocabulary Vocabulary */
        foreach ($this->vocabulariesCollection as $position => $vocabulary) {
            $plural = $vocabulary->getId();
            $singular = $this->config->get("site.taxonomies.$plural");
            if (count($vocabulary) > 0) {
                /*
                 * Creates $plural/$term pages (list of pages)
                 * ie: /tags/tag-1/
                 */
                /* @var $pages PagesCollection */
                foreach ($vocabulary as $position => $term) {
                    $pageId = $path = Page::slugify(sprintf('%s/%s', $plural, $term->getId()));
                    $pages = $term->sortByDate();
                    $date = $pages->first()->getVariable('date');
                    $page = (new Page($pageId))
                        ->setVariable('title', ucfirst($term->getId()));
                    if ($this->pagesCollection->has($pageId)) {
                        $page = clone $this->pagesCollection->get($pageId);
                    }
                    $page
                        ->setType(Type::TERM)
                        ->setPath($path)
                        ->setVariable('date', $date)
                        ->setVariable('term', $term->getId())
                        ->setVariable('plural', $plural)
                        ->setVariable('singular', $singular)
                        ->setVariable('pages', $pages)
                        ->setVariable('pagination', ['pages' => $pages]);
                    $this->generatedPages->add($page);
                }
                /*
                 * Creates $plural pages (list of terms)
                 * ex: /tags/
                 */
                $pageId = $path = Page::slugify($plural);
                $page = (new Page($pageId))
                    ->setType(Type::VOCABULARY)
                    ->setPath($path)
                    ->setVariable('title', ucfirst($plural))
                    ->setVariable('date', $date)
                    ->setVariable('plural', $plural)
                    ->setVariable('singular', $singular)
                    ->setVariable('terms', $vocabulary);
                // add page only if a template exist
                try {
                    $this->generatedPages->add($page);
                } catch (Exception $e) {
                    printf("%s\n", $e->getMessage());
                    unset($page); // do not add page
                }
            }
        }
    }
}
