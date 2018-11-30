<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Generator\GeneratorManager;

/**
 * Generates virtual pages.
 */
class GeneratePages extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        if (count($this->phpoole->getConfig()->get('generators')) > 0) {
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
            $generators = $this->phpoole->getConfig()->get('generators');
            array_walk($generators, function ($generator, $priority) use ($generatorManager) {
                if (!class_exists($generator)) {
                    $message = sprintf("Unable to load generator '%s'", $generator);
                    call_user_func_array($this->phpoole->getMessageCb(), ['GENERATE_ERROR', $message]);

                    return;
                }
                $generatorManager->addGenerator(new $generator($this->phpoole->getConfig()), $priority);
            });
            call_user_func_array($this->phpoole->getMessageCb(), ['GENERATE', 'Generating pages']);
            $pages = $generatorManager->process($this->phpoole->getPages(), $this->phpoole->getMessageCb());
            $this->phpoole->setPages($pages);
        }
    }
}
