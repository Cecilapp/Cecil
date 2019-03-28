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
use Cecil\Collection\Taxonomy\Collection as TaxonomiesCollection;
use Cecil\Collection\Taxonomy\Term as Term;
use Cecil\Collection\Taxonomy\Vocabulary as VocabularyCollection;
use Cecil\Exception\Exception;

/**
 * Class Taxonomy.
 */
class Taxonomy extends AbstractGenerator implements GeneratorInterface
{
    /* @var TaxonomiesCollection */
    protected $taxonomiesCollection;

    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        if ($this->config->get('site.taxonomies')) {
            $this->createTaxonomiesCollection();
            $this->collectTermsFromPages();
            $this->createTaxonomiesPages();
        }
    }

    /**
     * Create a collection from the vocabularies configuration.
     */
    protected function createTaxonomiesCollection()
    {
        // create an empty "taxonomies" collection
        $this->taxonomiesCollection = new TaxonomiesCollection('taxonomies');

        // adds vocabularies collections
        foreach (array_keys($this->config->get('site.taxonomies')) as $vocabulary) {
            /*
             * ie:
             *   taxonomies:
             *     tags: disabled
             */
            if ($this->config->get("site.taxonomies.$vocabulary") == 'disabled') {
                continue;
            }

            $this->taxonomiesCollection->add(new VocabularyCollection($vocabulary));
        }
    }

    /**
     * Collects taxonomies's terms from pages frontmatter.
     */
    protected function collectTermsFromPages()
    {
        /* @var $page Page */
        foreach ($this->pagesCollection as $page) {
            //foreach (array_keys($this->config->get('site.taxonomies')) as $plural) {
            foreach ($this->taxonomiesCollection as $vocabularyCollection) {
                $plural = $vocabularyCollection->getId();
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
                    foreach ($page->getVariable($plural) as $term) {
                        $term = mb_strtolower($term);
                        $this->taxonomiesCollection
                            ->get($plural)
                            ->add(new Term($term));
                        // ... and adds page to the term collection
                        $this->taxonomiesCollection
                            ->get($plural)
                            ->get($term)
                            ->add($page);
                    }
                }
            }
        }
    }

    /**
     * Creates taxonomies pages.
     */
    protected function createTaxonomiesPages()
    {
        /* @var $vocabulary VocabularyCollection */
        foreach ($this->taxonomiesCollection as $position => $vocabulary) {
            $plural = $vocabulary->getId();
            if (count($vocabulary) > 0) {
                /*
                 * Creates $plural/$term pages (list of pages)
                 * ie: /tags/tag-1/
                 */
                /* @var $pages PagesCollection */
                foreach ($vocabulary as $position => $term) {
                    $pages = $term;
                    $term = $term->getId();
                    $pages = $pages->sortByDate();
                    $pageId = $path = Page::slugify(sprintf('%s/%s', $plural, $term));
                    $page = (new Page($pageId))->setVariable('title', ucfirst($term));
                    if ($this->pagesCollection->has($pageId)) {
                        $page = clone $this->pagesCollection->get($pageId);
                    }
                    $date = $pages->first()->getVariable('date');
                    $page->setPath($path)
                        ->setType(Type::TAXONOMY)
                        ->setVariable('pages', $pages)
                        ->setVariable('date', $date)
                        ->setVariable('singular', $this->config->get('site.taxonomies')[$plural])
                        ->setVariable('pagination', ['pages' => $pages]);
                    $this->generatedPages->add($page);
                }
                /*
                 * Creates $plural pages (list of terms)
                 * ex: /tags/
                 */
                $page = (new Page(Page::slugify($plural)))
                    ->setPath(strtolower($plural))
                    ->setVariable('title', $plural)
                    ->setType(Type::TERMS)
                    ->setVariable('plural', $plural)
                    ->setVariable('singular', $this->config->get('site.taxonomies')[$plural])
                    ->setVariable('terms', $vocabulary)
                    ->setVariable('date', $date);
                // add page only if a template exist
                try {
                    $this->generatedPages->add($page);
                } catch (Exception $e) {
                    printf("%s\n", $e->getMessage());
                    // do not add page
                    unset($page);
                }
            }
        }
    }
}
