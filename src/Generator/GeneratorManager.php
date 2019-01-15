<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PagesCollection;

class GeneratorManager extends \SplPriorityQueue
{
    /**
     * Adds a generator.
     *
     * @param GeneratorInterface $generator
     * @param int                $priority
     *
     * @return self
     */
    public function addGenerator(GeneratorInterface $generator, $priority = 1)
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
     * Process each generators.
     *
     * @param PagesCollection $PagesCollection
     * @param \Closure       $messageCallback
     *
     * @return PagesCollection
     */
    public function process(PagesCollection $PagesCollection, \Closure $messageCallback)
    {
        $max = $this->count();

        if ($max > 0) {
            $this->top();
            while ($this->valid()) {
                /* @var GeneratorInterface $generator */
                $generator = $this->current();
                /* @var $generatedPages PagesCollection */
                $generatedPages = $generator->generate($PagesCollection, $messageCallback);
                foreach ($generatedPages as $page) {
                    /* @var $page \Cecil\Collection\Page\Page */
                    if ($PagesCollection->has($page->getId())) {
                        $PagesCollection->replace($page->getId(), $page);
                    } else {
                        $PagesCollection->add($page);
                    }
                }
                $message = substr(strrchr(get_class($generator), '\\'), 1).': '.count($generatedPages);
                $count = ($max - $this->key());
                call_user_func_array($messageCallback, ['GENERATE_PROGRESS', $message, $count, $max]);
                $this->next();
            }
        }

        return $PagesCollection;
    }
}
