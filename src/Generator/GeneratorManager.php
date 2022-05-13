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

use Cecil\Builder;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Util;

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
    public function compare($priority1, $priority2)
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
        $max = $this->count();

        if ($max > 0) {
            $this->top();
            while ($this->valid()) {
                $count = $max - $this->key();
                /** @var AbstractGenerator $generator */
                $generator = $this->current();
                /** @var PagesCollection $generatedPages */
                $generatedPages = $generator->runGenerate();
                foreach ($generatedPages as $page) {
                    /** @var \Cecil\Collection\Page\Page $page */
                    try {
                        $pagesCollection->add($page);
                    } catch (\DomainException $e) {
                        $pagesCollection->replace($page->getId(), $page);
                    }
                }
                $message = \sprintf('%s "%s" pages generated', count($generatedPages), Util::formatClassName($generator));
                $this->builder->getLogger()->info($message, ['progress' => [$count, $max]]);

                $this->next();
            }
        }

        return $pagesCollection;
    }
}
