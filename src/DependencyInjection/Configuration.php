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

namespace Cecil\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration definition for Cecil.
 *
 * This class defines the configuration tree for Cecil's dependency injection container.
 * It provides validation and default values for configuration options.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cecil');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->integerNode('verbosity')
                    ->defaultValue(0)
                    ->info('Default verbosity level for loggers')
                ->end()
                ->booleanNode('debug')
                    ->defaultFalse()
                    ->info('Enable debug mode')
                ->end()
                ->scalarNode('source_dir')
                    ->defaultValue('%env(PWD)%')
                    ->info('Source directory for the site')
                ->end()
                ->scalarNode('destination_dir')
                    ->defaultValue('_site')
                    ->info('Destination directory for built files')
                ->end()
                ->scalarNode('cache_dir')
                    ->defaultValue('.cecil/cache')
                    ->info('Cache directory')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
