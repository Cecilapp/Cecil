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
use Cecil\Collection\Taxonomy\Vocabulary as VocabulariesCollection;
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
        if ($this->config->get('site.taxonomies')
            && false !== $this->config->get('site.taxonomies.enabled')
        ) {
            $this->initTaxonomiesCollection();
            $this->collectTermsFromPages();
            $this->createNodePages();
        }
    }

    /**
     * Create a collection from the vocabularies configuration.
     */
    protected function initTaxonomiesCollection()
    {
        // create an empty "taxonomies" collection
        $this->taxonomiesCollection = new TaxonomiesCollection('taxonomies');

        /*
        // adds each vocabularies collection to the "taxonomies" collection
        foreach ($this->config->get('site.taxonomies') as $vocabulary) {
            if ($vocabulary != 'disabled') {
                $this->taxonomiesCollection->add(new VocabulariesCollection($vocabulary));
            }
        }
        */
    }

    /**
     * Collects taxonomies's terms from pages frontmatter.
     */
    protected function collectTermsFromPages()
    {
        /* @var $page Page */
        foreach ($this->pagesCollection as $page) {
            foreach (array_keys($this->config->get('site.taxonomies')) as $plural) {
                if ($page->hasVariable($plural)) {
                    // converts a list to an array if necessary
                    if (!is_array($page->getVariable($plural))) {
                        $page->setVariable($plural, [$page->getVariable($plural)]);
                    }
                    // adds each term to the vocabulary collection
                    foreach ($page->getVariable($plural) as $key => $term) {
                        $term = mb_strtolower($term);
                        $this->taxonomiesCollection->get($plural)
                            ->add(new Term($term));
                        // adds page to the term collection
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
     * Creates node pages.
     */
    protected function createNodePages()
    {
        // debug
        //print_r($this->taxonomiesCollection);
        //die();

        /* @var $terms VocabulariesCollection */
        //foreach ($this->taxonomiesCollection as $plural => $terms) {
        foreach ($this->taxonomiesCollection as $position => $taxonomy) {
            /* @var $taxonomy TaxonomiesCollection */
            $plural = $taxonomy->getId();
            //$terms = $taxonomy->toArray();
            $terms = $taxonomy;
            if (count($terms) > 0) {
                /*
                 * Creates $plural/$term pages (list of pages)
                 * ex: /tags/tag-1/
                 */
                /* @var $pages PagesCollection */
                //foreach ($terms as $term => $pages) {
                foreach ($terms as $position => $vocabulary) {
                    $term = $vocabulary->getId();
                    $pages = $vocabulary;
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
                        //->setVariable('url', $path.'/')
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
                    ->setVariable('terms', $terms)
                    ->setVariable('date', $date)
                    //->setVariable('url', strtolower($plural).'/')
;
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
