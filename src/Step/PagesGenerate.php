<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Generator\GeneratorManager;

/**
 * Generates virtual pages.
 */
class PagesGenerate extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        if (count($this->builder->getConfig()->get('generators')) > 0) {
            $this->process = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        if ($this->process) {
            $generatorManager = new GeneratorManager();

            call_user_func_array($this->builder->getMessageCb(), ['GENERATE', 'Generating pages']);

            $generators = $this->builder->getConfig()->get('generators');
            array_walk($generators, function ($generator, $priority) use ($generatorManager) {
                if (!class_exists($generator)) {
                    $message = sprintf("Unable to load generator '%s'", $generator);
                    call_user_func_array($this->builder->getMessageCb(), ['GENERATE_ERROR', $message]);

                    return;
                }
                $generatorManager->addGenerator(new $generator($this->builder->getConfig()), $priority);
            });

            $pages = $generatorManager->process($this->builder->getPages(), $this->builder->getMessageCb());
            $this->builder->setPages($pages);
        }
    }
}
