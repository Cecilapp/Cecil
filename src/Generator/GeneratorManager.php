<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Generator;

use Cecil\Builder;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Util;

/**
 * GeneratorManager class.
 *
 * This class manages the generators used in the Cecil build process.
 * It extends \SplPriorityQueue to allow generators to be processed in order of priority.
 * Generators can be added with a specific priority, and the process method will run each generator
 * in order, collecting the pages they generate.
 */
class GeneratorManager extends \SplPriorityQueue
{
    /** @var Builder */
    protected $builder;

    /**
     * @param Builder $builder
     *
     * @return void
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Adds a generator.
     */
    public function addGenerator(GeneratorInterface $generator, int $priority = 1): self
    {
        $this->insert($generator, $priority);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compare($priority1, $priority2): int
    {
        if ($priority1 === $priority2) {
            return 0;
        }

        return $priority1 > $priority2 ? -1 : 1;
    }

    /**
     * Process each generator.
     */
    public function process(): PagesCollection
    {
        /** @var PagesCollection $pagesCollection */
        $pagesCollection = $this->builder->getPages();
        if ($pagesCollection === null) {
            $pagesCollection = new PagesCollection('pages');
        }
        $total = $this->count();

        if ($total > 0) {
            $this->top();
            while ($this->valid()) {
                $count = $total - $this->key();
                $countPagesAdded = $countPagesUpdated = 0;
                /** @var AbstractGenerator $generator */
                $generator = $this->current();
                /** @var PagesCollection $generatedPages */
                $generatedPages = $generator->runGenerate();
                foreach ($generatedPages as $page) {
                    /** @var \Cecil\Collection\Page\Page $page */
                    try {
                        $pagesCollection->add($page);
                        $countPagesAdded++;
                    } catch (\DomainException) {
                        $pagesCollection->replace($page->getId(), $page);
                        $countPagesUpdated++;
                    }
                }
                $message = \sprintf('%s "%s" pages generated and %s pages updated', $countPagesAdded, Util::formatClassName($generator), $countPagesUpdated);
                $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);

                $this->next();
            }
        }

        return $pagesCollection;
    }
}
