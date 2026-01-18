<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cecil\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

try {
    echo "=== Test Build complet avec DI ===\n\n";
    
    $app = new Application();
    $app->setAutoExit(false);
    // $app->setCatchExceptions(false);
    
    // Créer un site de test temporaire
    $testDir = sys_get_temp_dir() . '/cecil-test-' . uniqid();
    mkdir($testDir);
    mkdir($testDir . '/pages');
    mkdir($testDir . '/layouts');
    
    // Créer une page de test
    file_put_contents($testDir . '/pages/index.md', <<<'MD'
---
title: Test Page
---
# Hello World

This is a test page.
MD
    );
    
    // Créer un layout de test
    file_put_contents($testDir . '/layouts/index.html.twig', <<<'TWIG'
<!DOCTYPE html>
<html>
<head>
    <title>{{ page.title }}</title>
</head>
<body>
    {{ page.content }}
</body>
</html>
TWIG
    );
    
    echo "✓ Site de test créé dans: $testDir\n\n";
    
    // Lancer le build
    echo "=== Lancement du build ===\n";
    $input = new ArrayInput([
        'command' => 'build',
        '--path' => $testDir,
        '--quiet' => true,
    ]);
    $output = new BufferedOutput();
    
    $status = $app->run($input, $output);
    
    echo "Build output:\n";
    echo $output->fetch() ?: "(pas de sortie)\n";
    echo "\nExit status: $status\n";
    
    // Vérifier le résultat
    if (file_exists($testDir . '/_site/index.html')) {
        echo "\n✓ Fichier index.html généré\n";
        $content = file_get_contents($testDir . '/_site/index.html');
        if (strpos($content, 'Hello World') !== false) {
            echo "✓ Contenu correct trouvé\n";
        } else {
            echo "✗ Contenu incorrect\n";
        }
    } else {
        echo "\n✗ Fichier index.html NON généré\n";
    }
    
    // Nettoyage
    echo "\n=== Nettoyage ===\n";
    system("rm -rf " . escapeshellarg($testDir));
    echo "✓ Test complet\n";
    
} catch (\Exception $e) {
    echo "\n✗ Erreur: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
