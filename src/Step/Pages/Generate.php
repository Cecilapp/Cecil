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

use Cecil\Generator\GeneratorManager;
use Cecil\Step\AbstractStep;
use Cecil\Util;

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
        $generators = (array) $this->config->get('pages.generators');
        array_walk($generators, function ($generator, $priority) use ($generatorManager) {
            if (!class_exists($generator)) {
                $message = \sprintf('Unable to load generator "%s" (priority: %s).', $generator, $priority);
                $this->builder->getLogger()->error($message);

                return;
            }
            $generatorManager->addGenerator(new $generator($this->builder), $priority);
        });
        $pages = $generatorManager->process();
        $this->builder->setPages($pages);
    }
}
