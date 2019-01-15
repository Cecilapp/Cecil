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
use Cecil\Collection\Taxonomy\Collection as TaxonomiesCollection;
use Cecil\Collection\Taxonomy\Term as Term;
use Cecil\Collection\Taxonomy\Vocabulary as VocabulariesCollection;
use Cecil\Exception\Exception;
use Cecil\Page\NodeType;

/**
 * Class Taxonomy.
 */
class Taxonomy extends AbstractGenerator implements GeneratorInterface
{
    /* @var TaxonomiesCollection */
    protected $TaxonomiesCollection;
    /* @var PagesCollection */
    protected $PagesCollection;
    /* @var PagesCollection */
    protected $generatedPages;

    /**
     * {@inheritdoc}
     */
    public function generate(PagesCollection $PagesCollection, \Closure $messageCallback)
    {
        $this->PagesCollection = $PagesCollection;
        $this->generatedPages = new PagesCollection('generator-taxonomy');

        if ($this->config->get('site.taxonomies') && !$this->config->get('site.taxonomies.disabled')) {
            $this->initTaxonomiesCollection();
            $this->collectTermsFromPages();
            $this->createNodePages();
        }

        return $this->generatedPages;
    }

    /**
     * Create a collection from the vocabularies configuration.
     */
    protected function initTaxonomiesCollection()
    {
        // create an empty "taxonomies" collection
        $this->TaxonomiesCollection = new TaxonomiesCollection('taxonomies');

        // adds each vocabularies collection to the "taxonomies" collection
        foreach ($this->config->get('site.taxonomies') as $vocabulary) {
            if ($vocabulary != 'disable') {
                $this->TaxonomiesCollection->add(new VocabulariesCollection($vocabulary));
            }
        }
    }

    /**
     * Collects taxonomies's terms from pages frontmatter.
     */
    protected function collectTermsFromPages()
    {
        /* @var $page Page */
        foreach ($this->PagesCollection as $page) {
            foreach (array_keys($this->config->get('site.taxonomies')) as $plural) {
                if ($page->hasVariable($plural)) {
                    // converts a list to an array if necessary
                    if (!is_array($page->getVariable($plural))) {
                        $page->setVariable($plural, [$page->getVariable($plural)]);
                    }
                    // adds each term to the vocabulary collection
                    foreach ($page->getVariable($plural) as $term) {
                        $term = mb_strtolower($term);
                        $this->TaxonomiesCollection->get($plural)
                            ->add(new Term($term));
                        // adds page to the term collection
                        $this->TaxonomiesCollection
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
        /* @var $terms VocabulariesCollection */
        foreach ($this->TaxonomiesCollection as $plural => $terms) {
            if (count($terms) > 0) {
                /*
                 * Creates $plural/$term pages (list of pages)
                 * ex: /tags/tag-1/
                 */
                /* @var $pages PagesCollection */
                foreach ($terms as $term => $pages) {
                    $pages = $pages->sortByDate()->toArray();
                    $pageId = Page::urlize(sprintf('%s/%s', $plural, $term));
                    if ($this->PagesCollection->has($pageId)) {
                        $page = clone $this->PagesCollection->get($pageId);
                    } else {
                        $page = (new Page())
                            ->setTitle(ucfirst($term));
                    }
                    $page->setId($pageId)
                        ->setPathname($pageId)
                        ->setNodeType(NodeType::TAXONOMY)
                        ->setVariable('pages', $pages)
                        ->setVariable('date', $date = reset($pages)->getDate())
                        ->setVariable('singular', $this->config->get('site.taxonomies')[$plural])
                        ->setVariable('pagination', ['pages' => $pages]);
                    $this->generatedPages->add($page);
                }
                /*
                 * Creates $plural pages (list of terms)
                 * ex: /tags/
                 */
                $page = (new Page())
                    ->setId(Page::urlize($plural))
                    ->setPathname(strtolower($plural))
                    ->setTitle($plural)
                    ->setNodeType(NodeType::TERMS)
                    ->setVariable('plural', $plural)
                    ->setVariable('singular', $this->config->get('site.taxonomies')[$plural])
                    ->setVariable('terms', $terms)
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
