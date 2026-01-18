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

namespace Cecil\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass for automatic registration of generators.
 *
 * This compiler pass finds all services tagged with 'cecil.generator'
 * and registers them with the GeneratorManager service.
 * It supports priority-based ordering of generators.
 */
class GeneratorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        // Si le GeneratorManager n'existe pas, on ne fait rien
        if (!$container->has('Cecil\Generator\GeneratorManager')) {
            return;
        }

        $definition = $container->findDefinition('Cecil\Generator\GeneratorManager');
        $taggedServices = $container->findTaggedServiceIds('cecil.generator');

        // Tri des générateurs par priorité
        $generators = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $priority = $attributes['priority'] ?? 0;
                $generators[$priority][] = $id;
            }
        }

        // Tri par priorité décroissante
        krsort($generators);

        // Enregistrement des générateurs dans le manager
        foreach ($generators as $priority => $ids) {
            foreach ($ids as $id) {
                $definition->addMethodCall('addGenerator', [
                    new Reference($id),
                    $priority
                ]);
            }
        }
    }
}
