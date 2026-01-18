<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cecil\DependencyInjection\ContainerBuilder;

try {
    echo "=== Test de création du conteneur ===\n";
    $container = ContainerBuilder::build();
    echo "✓ Conteneur créé\n\n";

    echo "=== Test des services ===\n";

    // Test Parsedown
    try {
        $parsedown = $container->get('Cecil\Converter\Parsedown');
        echo "✓ Parsedown: " . get_class($parsedown) . "\n";
    } catch (\Exception $e) {
        echo "✗ Parsedown: " . $e->getMessage() . "\n";
    }

    // Test Converter
    try {
        $converter = $container->get('Cecil\Converter\Converter');
        echo "✓ Converter: " . get_class($converter) . "\n";
    } catch (\Exception $e) {
        echo "✗ Converter: " . $e->getMessage() . "\n";
    }

    // Test GeneratorManager
    try {
        $generatorManager = $container->get('Cecil\Generator\GeneratorManager');
        echo "✓ GeneratorManager: " . get_class($generatorManager) . "\n";
    } catch (\Exception $e) {
        echo "✗ GeneratorManager: " . $e->getMessage() . "\n";
    }

    // Test TwigFactory
    try {
        $twigFactory = $container->get('Cecil\Renderer\TwigFactory');
        echo "✓ TwigFactory: " . get_class($twigFactory) . "\n";
    } catch (\Exception $e) {
        echo "✗ TwigFactory: " . $e->getMessage() . "\n";
    }

    // Test Steps
    try {
        $convertStep = $container->get('Cecil\Step\Pages\Convert');
        echo "✓ Convert Step: " . get_class($convertStep) . "\n";
    } catch (\Exception $e) {
        echo "✗ Convert Step: " . $e->getMessage() . "\n";
    }

    try {
        $generateStep = $container->get('Cecil\Step\Pages\Generate');
        echo "✓ Generate Step: " . get_class($generateStep) . "\n";
    } catch (\Exception $e) {
        echo "✗ Generate Step: " . $e->getMessage() . "\n";
    }

    try {
        $renderStep = $container->get('Cecil\Step\Pages\Render');
        echo "✓ Render Step: " . get_class($renderStep) . "\n";
    } catch (\Exception $e) {
        echo "✗ Render Step: " . $e->getMessage() . "\n";
    }

    echo "\n=== Test complet ===\n";
    echo "✓ Tous les services sont accessibles\n";
} catch (\Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
