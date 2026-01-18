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

use Cecil\DependencyInjection\CompilerPass\GeneratorPass;
use Cecil\DependencyInjection\CompilerPass\StepPass;
use Cecil\DependencyInjection\CompilerPass\TwigExtensionPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Container Builder for Cecil.
 *
 * This class is responsible for building and configuring the dependency injection container.
 * It loads service definitions from YAML files and registers compiler passes for automatic
 * service discovery and configuration.
 */
class ContainerBuilder
{
    /**
     * Builds and configures the dependency injection container.
     *
     * @param array<string, mixed> $parameters Additional parameters to set in the container
     * @return SymfonyContainerBuilder The configured container
     */
    public static function build(array $parameters = []): SymfonyContainerBuilder
    {
        $container = new SymfonyContainerBuilder();

        // Paramètres par défaut
        $defaultParams = [
            'cecil.verbosity' => 0,
            'cecil.debug' => false,
        ];

        // Merge et définition des paramètres
        foreach (array_merge($defaultParams, $parameters) as $key => $value) {
            $container->setParameter($key, $value);
        }

        // Déterminer le chemin de configuration
        $configPath = self::getConfigPath();

        // Chargement des services depuis le fichier YAML
        $loader = new YamlFileLoader(
            $container,
            new FileLocator($configPath)
        );

        try {
            $loader->load('services.yaml');
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('Unable to load service configuration: %s', $e->getMessage()),
                0,
                $e
            );
        }

        // Enregistrement des compiler passes
        $container->addCompilerPass(new GeneratorPass());
        $container->addCompilerPass(new StepPass());
        $container->addCompilerPass(new TwigExtensionPass());

        // Compilation du container
        $container->compile();

        return $container;
    }

    /**
     * Determines the configuration path based on the execution context.
     *
     * @return string The path to the configuration directory
     */
    private static function getConfigPath(): string
    {
        // Si exécuté depuis un PHAR
        if (defined('PHAR_DIR')) {
            return PHAR_DIR . '/config';
        }

        // Sinon, chemin relatif depuis le répertoire src
        return __DIR__ . '/../../config';
    }

    /**
     * Creates a cached container for production use.
     *
     * @param string $cacheDir Directory where the cached container should be stored
     * @param array<string, mixed> $parameters Additional parameters
     * @return SymfonyContainerBuilder The container
     */
    public static function buildCached(string $cacheDir, array $parameters = []): SymfonyContainerBuilder
    {
        $cacheFile = $cacheDir . '/container.php';

        // Si le cache existe et est valide, on l'utilise
        if (file_exists($cacheFile) && !($parameters['cecil.debug'] ?? false)) {
            require_once $cacheFile;
            $containerClass = '\\Cecil\\DependencyInjection\\Cached\\CachedContainer';
            if (class_exists($containerClass)) {
                return new $containerClass();
            }
        }

        // Sinon, on construit et on met en cache
        $container = self::build($parameters);

        // Sauvegarde du container compilé
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        // Cette fonctionnalité nécessiterait l'ajout de symfony/config pour le dumping
        // Pour l'instant, on retourne simplement le container
        return $container;
    }
}
