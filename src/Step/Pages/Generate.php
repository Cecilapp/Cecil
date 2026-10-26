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

namespace Cecil\Step\Pages;

use Cecil\Extension\Loader;
use Cecil\Generator\GeneratorInterface;
use Cecil\Generator\GeneratorManager;
use Cecil\Step\AbstractStep;

/**
 * Generate pages step.
 *
 * This step is responsible for generating pages based on the configured generators.
 * It initializes the generator manager with the generators defined in the configuration
 * and processes them to create the pages. The generated pages are then set in the builder.
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
        if (\count((array) $this->config->get('pages.generators')) > 0) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        $generatorManager = new GeneratorManager($this->builder);
        $generators = (new Loader($this->builder))->load('pages.generators', GeneratorInterface::class);
        // generators are already sorted by priority: their position is used to keep the declaration order of equal priorities
        $position = 0;
        foreach ($generators as $generator) {
            $generatorManager->addGenerator($generator, $position++);
        }
        $pages = $generatorManager->process();
        $this->builder->setPages($pages);
    }
}
