<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Generator\GeneratorManager;
use Cecil\Util;

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
        /** @var \Cecil\Builder $builder */
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
            $generatorManager = new GeneratorManager($this->builder);

            $this->builder->getLogger()->notice('Generating pages');

            // loads local generators
            spl_autoload_register(function ($className) {
                $generatorFile = Util::joinFile($this->config->getDestinationDir(), 'generators', "$className.php");
                if (file_exists($generatorFile)) {
                    require $generatorFile;
                }
            });

            $generators = (array) $this->builder->getConfig()->get('generators');
            array_walk($generators, function ($generator, $priority) use ($generatorManager) {
                if (!class_exists($generator)) {
                    $message = sprintf('Unable to load generator "%s"', $generator);
                    $this->builder->getLogger()->error($message);

                    return;
                }
                $generatorManager->addGenerator(new $generator($this->builder), $priority);
            });

            $pages = $generatorManager->process($this->builder);
            $this->builder->setPages($pages);
        }
    }
}
