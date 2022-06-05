<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\Pages;

use Cecil\Generator\GeneratorManager;
use Cecil\Step\AbstractStep;
use Cecil\Util;

/**
 * Generates virtual pages.
 */
class Generate extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Generating pages';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        if (count((array) $this->builder->getConfig()->get('generators')) > 0) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        $generatorManager = new GeneratorManager($this->builder);

        // loads local generators
        spl_autoload_register(function ($className) {
            $generatorFile = Util::joinFile($this->config->getSourceDir(), 'generators', "$className.php");
            if (file_exists($generatorFile)) {
                require $generatorFile;
            }
        });

        $generators = (array) $this->builder->getConfig()->get('generators');
        array_walk($generators, function ($generator, $priority) use ($generatorManager) {
            if (!class_exists($generator)) {
                $message = \sprintf('Unable to load generator "%s".', $generator);
                $this->builder->getLogger()->error($message);

                return;
            }
            $generatorManager->addGenerator(new $generator($this->builder), $priority);
        });

        $pages = $generatorManager->process();
        $this->builder->setPages($pages);
    }
}
