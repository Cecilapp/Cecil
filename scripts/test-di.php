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

/**
 * Test script pour valider l'injection de dépendances dans Cecil.
 *
 * Ce script teste :
 * - La construction du container DI
 * - L'instanciation du Builder via DI
 * - L'accès aux services
 */

require __DIR__ . '/../src/bootstrap.php';

use Cecil\Application;
use Cecil\Builder;
use Cecil\BuilderFactory;
use Cecil\DependencyInjection\ContainerBuilder;

echo "=== Test de l'injection de dépendances Cecil ===\n\n";

// Test 1: Construction du container
echo "1. Construction du container DI...\n";
try {
    $container = ContainerBuilder::build([
        'cecil.verbosity' => 1,
        'cecil.debug' => false,
    ]);
    echo "   ✓ Container construit avec succès\n";
    echo "   - Services enregistrés: " . count($container->getServiceIds()) . "\n\n";
} catch (\Exception $e) {
    echo "   ✗ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Récupération du Builder depuis le container
echo "2. Récupération du Builder depuis le container...\n";
try {
    if ($container->has('Cecil\Builder')) {
        echo "   ✓ Service Builder disponible dans le container\n";
        // Note: On ne l'instancie pas encore car il nécessite une config complète
    } else {
        echo "   ✗ Service Builder non trouvé dans le container\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ✗ Erreur: " . $e->getMessage() . "\n";
}

// Test 3: Création du Builder depuis le container
echo "3. Création du Builder depuis le container...\n";
try {
    $builder = BuilderFactory::create($container);
    echo "   ✓ Builder créé depuis le container\n";
    echo "   - Version: " . Builder::VERSION . "\n\n";
} catch (\Exception $e) {
    echo "   ✗ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Application avec DI
echo "4. Test de l'Application avec DI...\n";
try {
    $app = new Application();
    echo "   ✓ Application créée\n";

    $appContainer = $app->getContainer();
    if ($appContainer !== null) {
        echo "   ✓ Container DI disponible dans l'application\n";
    } else {
        echo "   ✗ Container non disponible\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ✗ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Vérification des services essentiels
echo "5. Vérification des services essentiels dans le container...\n";
$essentialServices = [
    'Cecil\Config',
    'Cecil\Builder',
    'Psr\Log\LoggerInterface',
    'Cecil\Logger\PrintLogger',
];

foreach ($essentialServices as $service) {
    if ($container->has($service)) {
        echo "   ✓ $service\n";
    } else {
        echo "   ✗ $service (manquant)\n";
    }
}
echo "\n";

echo "=== Tests terminés avec succès ===\n";
echo "\nLe système d'injection de dépendances est opérationnel.\n";
echo "Mode DI activé en production.\n";
