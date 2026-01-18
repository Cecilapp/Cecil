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

/**
 * Compiler pass for automatic registration of build steps.
 *
 * This compiler pass finds all services tagged with 'cecil.step'
 * and makes them available for the build process.
 * Steps are executed in the order defined in Builder::STEPS constant.
 */
class StepPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        // Si le Builder n'existe pas, on ne fait rien
        if (!$container->has('Cecil\Builder')) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds('cecil.step');

        // Pour l'instant, on s'assure simplement que les steps sont bien enregistrés
        // L'ordre d'exécution est géré par Builder::STEPS
        // Cette passe pourrait être étendue pour permettre une configuration plus dynamique

        foreach ($taggedServices as $id => $tags) {
            $definition = $container->findDefinition($id);
            
            // S'assurer que les steps sont publics pour être accessibles par le Builder
            $definition->setPublic(true);
        }
    }
}
