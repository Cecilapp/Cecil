<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Collection as PageCollection;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Taxonomy\Collection as TaxonomyCollection;
use Cecil\Collection\Taxonomy\Term as Term;
use Cecil\Collection\Taxonomy\Vocabulary as Vocabulary;
use Cecil\Exception\Exception;
use Cecil\Page\NodeType;

/**
 * Class Taxonomy.
 */
class Taxonomy extends AbstractGenerator implements GeneratorInterface
{
    /* @var TaxonomyCollection */
    protected $taxonomies;
    /* @var PageCollection */
    protected $pageCollection;
    /* @var PageCollection */
    protected $generatedPages;

    /**
     * {@inheritdoc}
     */
    public function generate(PageCollection $pageCollection, \Closure $messageCallback)
    {
        $this->pageCollection = $pageCollection;
        $this->generatedPages = new PageCollection('generator-taxonomy');

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
        $this->taxonomies = new TaxonomyCollection('taxonomies');

        // adds each vocabulary collection to the "taxonomies" collection
        foreach ($this->config->get('site.taxonomies') as $vocabulary) {
            if ($vocabulary != 'disable') {
                $this->taxonomies->add(new Vocabulary($vocabulary));
            }
        }
    }

    /**
     * Collects taxonomies's terms from pages frontmatter.
     */
    protected function collectTermsFromPages()
    {
        /* @var $page Page */
        foreach ($this->pageCollection as $page) {
            foreach (array_keys($this->config->get('site.taxonomies')) as $plural) {
                if ($page->hasVariable($plural)) {
                    // converts a list to an array if necessary
                    if (!is_array($page->getVariable($plural))) {
                        $page->setVariable($plural, [$page->getVariable($plural)]);
                    }
                    // adds each term to the vocabulary collection
                    foreach ($page->getVariable($plural) as $term) {
                        $term = mb_strtolower($term);
                        $this->taxonomies->get($plural)
                            ->add(new Term($term));
                        // adds page to the term collection
                        $this->taxonomies
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
        /* @var $terms Vocabulary */
        foreach ($this->taxonomies as $plural => $terms) {
            if (count($terms) > 0) {
                /*
                 * Creates $plural/$term pages (list of pages)
                 * ex: /tags/tag-1/
                 */
                /* @var $pages PageCollection */
                foreach ($terms as $term => $pages) {
                    if (!$this->pageCollection->has($term)) {
                        $pages = $pages->sortByDate()->toArray();
                        $page = (new Page())
                            ->setId(Page::urlize(sprintf('%s/%s/', $plural, $term)))
                            ->setPathname(Page::urlize(sprintf('%s/%s', $plural, $term)))
                            ->setTitle(ucfirst($term))
                            ->setNodeType(NodeType::TAXONOMY)
                            ->setVariable('pages', $pages)
                            ->setVariable('date', $date = reset($pages)->getDate())
                            ->setVariable('singular', $this->config->get('site.taxonomies')[$plural])
                            ->setVariable('pagination', ['pages' => $pages]);
                        $this->generatedPages->add($page);
                    }
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
                    echo $e->getMessage()."\n";
                    // do not add page
                    unset($page);
                }
            }
        }
    }
}
