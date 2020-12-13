<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Collection\Taxonomy\Vocabulary as Vocabulary;
use Cecil\Exception\Exception;

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
        if ($this->config->get('taxonomies') && $this->builder->getTaxonomies() !== null) {
            /** @var Vocabulary $vocabulary */
            foreach ($this->builder->getTaxonomies() as $vocabulary) {
                $plural = $vocabulary->getId();
                $singular = $this->config->get("taxonomies.$plural");
                if (count($vocabulary) > 0) {
                    $date = date('Y-m-d');
                    /*
                     * Creates $plural/$term pages (list of pages)
                     * ie: /tags/tag-1/
                     */
                    /** @var PagesCollection $pages */
                    foreach ($vocabulary as $term) {
                        $pageId = $path = Page::slugify(sprintf('%s/%s', $plural, $term->getId()));
                        $pages = $term->sortByDate();
                        $date = $pages->first()->getVariable('date');
                        $page = (new Page($pageId))
                            ->setVariable('title', $term->getName());
                        if ($this->builder->getPages()->has($pageId)) {
                            $page = clone $this->builder->getPages()->get($pageId);
                        }
                        /** @var Page $page */
                        $page
                            ->setType(Type::TERM)
                            ->setPath($path)
                            ->setVariable('date', $date)
                            ->setVariable('term', $term->getId())
                            ->setVariable('plural', $plural)
                            ->setVariable('singular', $singular)
                            ->setVariable('pages', $pages);
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
                    // adds page only if a template exist
                    try {
                        $this->generatedPages->add($page);
                    } catch (Exception $e) {
                        printf("%s\n", $e->getMessage());
                        unset($page); // do not adds page
                    }
                }
            }
        }
    }
}
