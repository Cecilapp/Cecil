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
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generate pages step.
 *
 * This step is responsible for generating pages based on the configured generators.
 * It initializes the generator manager with the generators defined in the configuration
 * and processes them to create the pages. The generated pages are then set in the builder.
 */
class Generate extends AbstractStep
{
    /** @var GeneratorManager */
    protected $generatorManager;

    /** @var ContainerInterface */
    protected $container;

    public function __construct(GeneratorManager $generatorManager, ContainerInterface $container)
    {
        $this->generatorManager = $generatorManager;
        $this->container = $container;
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
        array_walk($generators, function ($generatorClass, $priority) {
            if (!class_exists($generatorClass)) {
                $message = \sprintf('Unable to load generator "%s" (priority: %s).', $generatorClass, $priority);
                $this->builder->getLogger()->error($message);

                return;
            }
            // Try to get generator from container, otherwise instantiate manually
            try {
                $generator = $this->container->get($generatorClass);
                // Set builder for generators (needed for context)
                if (method_exists($generator, 'setBuilder')) {
                    $generator->setBuilder($this->builder);
                }
            } catch (\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException $e) {
                // Generator not in container or inlined, instantiate manually
                // Special case for ExternalBody which needs Converter
                if ($generatorClass === 'Cecil\\Generator\\ExternalBody') {
                    try {
                        $converter = $this->container->get('Cecil\\Converter\\Converter');
                    } catch (\Exception $converterException) {
                        // Fallback: create converter manually
                        $parsedown = new \Cecil\Converter\Parsedown($this->builder);
                        $converter = new \Cecil\Converter\Converter($this->builder, $parsedown);
                    }
                    $generator = new $generatorClass($this->builder, $converter);
                } else {
                    $generator = new $generatorClass($this->builder);
                    // Verify builder is set
                    if (property_exists($generator, 'builder')) {
                        $reflection = new \ReflectionProperty($generator, 'builder');
                        $reflection->setAccessible(true);
                        $builderValue = $reflection->getValue($generator);
                        if ($builderValue === null) {
                            throw new \RuntimeException("Builder not set for generator $generatorClass");
                        }
                    }
                }
            }
            $this->generatorManager->addGenerator($generator, $priority);
        });
        $pages = $this->generatorManager->process();
        $this->builder->setPages($pages);
    }
}
