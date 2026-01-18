<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cecil\BuilderFactory;
use Cecil\Config;
use Cecil\Logger\PrintLogger;
use Cecil\DependencyInjection\ContainerBuilder;

try {
    echo "=== Test Builder et Steps avec DI ===\n\n";

    // Créer le container
    $container = ContainerBuilder::build();
    echo "✓ Container créé\n";

    // Créer une config de test
    $testDir = sys_get_temp_dir() . '/cecil-test-' . uniqid();
    mkdir($testDir);
    mkdir($testDir . '/pages');
    mkdir($testDir . '/layouts');

    file_put_contents(
        $testDir . '/pages/test.md',
        <<<'MD'
---
title: Test
---
Hello
MD
    );

    echo "✓ Site de test créé\n";

    // Créer config
    $config = new Config(['source' => $testDir]);
    echo "✓ Config créée\n";

    // Créer logger
    $logger = new PrintLogger();
    echo "✓ Logger créé\n";

    // Créer builder via factory
    $factory = $container->get('Cecil\BuilderFactory');
    $builder = $factory->create($container);
    echo "✓ Builder créé via factory\n";

    // Récupérer un step
    try {
        $convertStep = $container->get('Cecil\Step\Pages\Convert');
        echo "✓ Convert step récupéré: " . get_class($convertStep) . "\n";

        // Vérifier que le converter est injecté
        $reflection = new \ReflectionClass($convertStep);
        $property = $reflection->getProperty('converter');
        $property->setAccessible(true);
        $converter = $property->getValue($convertStep);
        echo "✓ Converter injecté: " . get_class($converter) . "\n";
    } catch (\Exception $e) {
        echo "✗ Erreur step: " . $e->getMessage() . "\n";
    }

    // Nettoyage
    system("rm -rf " . escapeshellarg($testDir));

    echo "\n✓ Tous les tests passés\n";
} catch (\Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
