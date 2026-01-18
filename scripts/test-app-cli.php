<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cecil\Application;

try {
    echo "=== Test Application avec DI ===\n";

    $app = new Application();
    echo "✓ Application créée\n";

    // Test version
    echo "\n=== Test version ===\n";
    $app->setAutoExit(false);
    $app->setCatchExceptions(false);

    $input = new \Symfony\Component\Console\Input\ArrayInput(['--version' => true]);
    $output = new \Symfony\Component\Console\Output\BufferedOutput();

    $status = $app->run($input, $output);
    echo "Version output:\n";
    echo $output->fetch();
    echo "Exit status: $status\n";

    echo "\n✓ Test complet\n";
} catch (\Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
