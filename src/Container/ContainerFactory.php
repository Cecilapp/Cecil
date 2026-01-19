<?php

declare(strict_types=1);

namespace Cecil\Container;

use Cecil\Config;
use DI\Container;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;

/**
 * Factory to create and configure the dependency injection container.
 *
 * Uses PHP-DI for automatic autowiring and simple configuration.
 *
 * @see https://php-di.org/
 */
class ContainerFactory
{
    /**
     * Creates and configures the DI container with Cecil dependencies.
     *
     * @param Config          $config Application configuration
     * @param LoggerInterface $logger Application logger
     *
     * @return Container The configured and ready-to-use container
     */
    public static function create(
        Config $config,
        LoggerInterface $logger
    ): Container {
        $builder = new ContainerBuilder();

        // Load dependencies configuration
        $definitionsFile = __DIR__ . '/../../config/dependencies.php';
        if (file_exists($definitionsFile)) {
            $builder->addDefinitions($definitionsFile);
        }

        // Enable compilation cache in production
        if (!$config->get('debug')) {
            $cacheDir = $config->getCachePath() . '/di';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            $builder->enableCompilation($cacheDir);
        }

        // Build the container
        $container = $builder->build();

        // Inject Config and Logger instances from Builder
        // These objects are already instantiated and configured
        $container->set(Config::class, $config);
        $container->set(LoggerInterface::class, $logger);

        // Note: Builder cannot be injected here because it creates the container itself
        // Services that need Builder receive it as a constructor parameter

        return $container;
    }
}
