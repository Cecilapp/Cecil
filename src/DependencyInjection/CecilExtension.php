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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Cecil Dependency Injection Extension.
 *
 * This extension loads and manages the Cecil service configuration.
 * It can be used to provide additional configuration options and
 * customize the behavior of the dependency injection container.
 */
class CecilExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');

        // Fusion de la configuration
        // Cette méthode peut être étendue pour supporter une configuration personnalisée
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Définir les paramètres de configuration
        foreach ($config as $key => $value) {
            $container->setParameter('cecil.' . $key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'cecil';
    }
}
