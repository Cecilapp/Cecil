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

namespace Cecil;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builder Factory for creating Builder instances.
 *
 * This factory provides a centralized way to create Builder instances
 * from the dependency injection container.
 */
class BuilderFactory
{
    /**
     * Creates a Builder instance from the dependency injection container.
     *
     * @param ContainerInterface $container The DI container
     * @return Builder The configured Builder instance
     */
    public static function create(ContainerInterface $container): Builder
    {
        return $container->get(Builder::class);
    }
}
