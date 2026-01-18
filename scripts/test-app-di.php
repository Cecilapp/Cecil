<?php

require __DIR__ . '/../src/bootstrap.php';

use Cecil\Application;

error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    echo "Test de l'application avec DI...\n";
    $app = new Application(true);
    echo "✓ Application créée\n";
    
    $container = $app->getContainer();
    if ($container) {
        echo "✓ Container disponible\n";
        echo "  Services: " . count($container->getServiceIds()) . "\n";
    } else {
        echo "✗ Container non disponible\n";
    }
    
    echo "\nTest des commandes:\n";
    $commands = $app->all();
    echo "✓ " . count($commands) . " commandes chargées\n";
    
    if (count($commands) < 10) {
        echo "\nERREUR: Trop peu de commandes chargées!\n";
        echo "Vérification du container:\n";
        if ($container) {
            $ids = $container->getServiceIds();
            echo "Services dans le container:\n";
            foreach ($ids as $id) {
                if (strpos($id, 'Cecil\Command') !== false) {
                    echo "  - $id\n";
                }
            }
        }
    }
    
    foreach ($commands as $name => $command) {
        echo "  - $name: " . get_class($command) . "\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✓ Tests réussis\n";
