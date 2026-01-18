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
 * Compiler pass for automatic registration of Twig extensions.
 *
 * This compiler pass finds all services tagged with 'cecil.twig.extension'
 * and registers them with the Twig renderer service.
 */
class TwigExtensionPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        // Si le Renderer Twig n'existe pas, on ne fait rien
        if (!$container->has('Cecil\Renderer\Twig')) {
            return;
        }

        $definition = $container->findDefinition('Cecil\Renderer\Twig');
        $taggedServices = $container->findTaggedServiceIds('cecil.twig.extension');

        // Enregistrement des extensions Twig
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addExtension', [
                new Reference($id)
            ]);
        }
    }
}
