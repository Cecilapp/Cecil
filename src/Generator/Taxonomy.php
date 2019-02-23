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
use Cecil\Collection\Page\Type;

/**
 * Class Taxonomy.
 */
class Taxonomy extends AbstractGenerator implements GeneratorInterface
{
    /* @var TaxonomiesCollection */
    protected $taxonomiesCollection;
    /* @var PagesCollection */
    protected $pagesCollection;
    /* @var PagesCollection */
    protected $generatedPages;

    /**
     * {@inheritdoc}
     */
    public function generate(PagesCollection $pagesCollection, \Closure $messageCallback)
    {
        $this->pagesCollection = $pagesCollection;
        $this->generatedPages = new PagesCollection('generator-taxonomy');

        if ($this->config->get('site.taxonomies')
            && false !== $this->config->get('site.taxonomies.enabled')
        ) {
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
        $this->taxonomiesCollection = new TaxonomiesCollection('taxonomies');

        // adds each vocabularies collection to the "taxonomies" collection
        foreach ($this->config->get('site.taxonomies') as $vocabulary) {
            if ($vocabulary != 'disable') {
                $this->taxonomiesCollection->add(new VocabulariesCollection($vocabulary));
            }
        }
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
                    foreach ($page->getVariable($plural) as $term) {
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
        /* @var $terms VocabulariesCollection */
        foreach ($this->taxonomiesCollection as $plural => $terms) {
            if (count($terms) > 0) {
                /*
                 * Creates $plural/$term pages (list of pages)
                 * ex: /tags/tag-1/
                 */
                /* @var $pages PagesCollection */
                foreach ($terms as $term => $pages) {
                    $pages = $pages->sortByDate()->toArray();
                    $pageId = Page::slugify(sprintf('%s/%s', $plural, $term));
                    if ($this->pagesCollection->has($pageId)) {
                        $page = clone $this->pagesCollection->get($pageId);
                    } else {
                        $page = (new Page())
                            ->setVariable('title', ucfirst($term));
                    }
                    $page->setId($pageId)
                        ->setPath($pageId)
                        ->setType(Type::TAXONOMY)
                        ->setVariable('pages', $pages)
                        ->setVariable('date', $date = reset($pages)->getVariable('date'))
                        ->setVariable('url', $pageId.'/')
                        ->setVariable('singular', $this->config->get('site.taxonomies')[$plural])
                        ->setVariable('pagination', ['pages' => $pages]);
                    $this->generatedPages->add($page);
                }
                /*
                 * Creates $plural pages (list of terms)
                 * ex: /tags/
                 */
                $page = (new Page())
                    ->setId(Page::slugify($plural))
                    ->setPath(strtolower($plural))
                    ->setVariable('title', $plural)
                    ->setType(Type::TERMS)
                    ->setVariable('plural', $plural)
                    ->setVariable('singular', $this->config->get('site.taxonomies')[$plural])
                    ->setVariable('terms', $terms)
                    ->setVariable('date', $date)
                    ->setVariable('url', strtolower($plural).'/');
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
