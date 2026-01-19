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

use Cecil\Builder;
use Cecil\Config;
use Cecil\Generator\GeneratorManager;
use Cecil\Step\AbstractStep;
use Psr\Log\LoggerInterface;

/**
 * Generate pages step.
 *
 * This step is responsible for generating pages based on the configured generators.
 * It initializes the generator manager with the generators defined in the configuration
 * and processes them to create the pages. The generated pages are then set in the builder.
 */
class Generate extends AbstractStep
{
    private GeneratorManager $generatorManager;

    public function __construct(
        Builder $builder,
        Config $config,
        LoggerInterface $logger,
        GeneratorManager $generatorManager
    ) {
        parent::__construct($builder, $config, $logger);
        $this->generatorManager = $generatorManager;
    }
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
        $generators = (array) $this->config->get('pages.generators');
        array_walk($generators, function ($generator, $priority) {
            if (!class_exists($generator)) {
                $message = \sprintf('Unable to load generator "%s" (priority: %s).', $generator, $priority);
                $this->logger->error($message);

                return;
            }
            // Use DI container to create the generator if possible
            try {
                $generatorInstance = $this->builder->get($generator);
            } catch (\Exception $e) {
                // Fallback: create manually if not in container
                $this->logger->debug(\sprintf('Using fallback for generator "%s": %s', $generator, $e->getMessage()));
                $generatorInstance = new $generator($this->builder);
            }
            $this->generatorManager->addGenerator($generatorInstance, $priority);
        });
        $pages = $this->generatorManager->process();
        $this->builder->setPages($pages);
    }
}
