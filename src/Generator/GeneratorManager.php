<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

use PHPoole\Collection\Collection as PageCollection;

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
     * @param PageCollection $pageCollection
     * @param \Closure       $messageCallback
     *
     * @return PageCollection
     */
    public function process(PageCollection $pageCollection, \Closure $messageCallback)
    {
        $max = $this->count();

        if ($max > 0) {
            $this->top();
            while ($this->valid()) {
                /* @var GeneratorInterface $generator */
                $generator = $this->current();
                /* @var $generatedPages PageCollection */
                $generatedPages = $generator->generate($pageCollection, $messageCallback);
                foreach ($generatedPages as $page) {
                    /* @var $page \PHPoole\Collection\Page\Page */
                    if ($pageCollection->has($page->getId())) {
                        $pageCollection->replace($page->getId(), $page);
                    } else {
                        $pageCollection->add($page);
                    }
                }
                $message = substr(strrchr(get_class($generator), '\\'), 1).': '.count($generatedPages);
                $count = ($max - $this->key());
                call_user_func_array($messageCallback, ['GENERATE_PROGRESS', $message, $count, $max]);
                $this->next();
            }
        }

        return $pageCollection;
    }
}
