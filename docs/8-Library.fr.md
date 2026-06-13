<!--
title: "Bibliothèque"
description: "Utilisez Cecil comme bibliothèque PHP."
date: 2026-03-27
updated: 2026-06-13
slug: bibliotheque
-->
# Bibliothèque

Cecil propose une API PHP simple pour générer votre site web.

Vous pouvez consulter la [documentation de l'API](https://cecil.app/documentation/library/api/namespaces/cecil.html) pour plus de détails.

## Installation

```bash
composer require cecil/cecil
```

## Utilisation

### Construction

Construisez un nouveau site web avec une configuration personnalisée :

```php
use Cecil\Builder;

// Crée un tableau de configuration
$config = [
    'title'   => "My website",
    'baseurl' => 'https://domain.tld/',
];

// Construit avec la configuration personnalisée
Builder::create($config)->build();
```

:::info
Le paramètre principal de la méthode `create` doit être un `array` PHP ou une instance de [`Cecil\Config`](https://github.com/Cecilapp/Cecil/blob/master/src/Config.php).
:::

### Exemple

```php
require_once 'vendor/autoload.php';

use Cecil\Builder;

// Construit le site web avec le fichier de configuration `config.php`
Builder::create(require('config.php'))->build();

// Prévisualise localement
exec('php -S localhost:8000 -t _site');
```

### Services de domaine Doctor

Vous pouvez aussi exécuter les diagnostics doctor via des services de domaine dédiés, sans utiliser les commandes CLI.

```php
<?php

require_once 'vendor/autoload.php';

use Cecil\Builder;
use Cecil\Doctor\SeoDoctor;
use Cecil\Doctor\SiteDoctor;

$builder = Builder::create(require 'config.php')
    ->setSourceDir(__DIR__)
    ->setDestinationDir(__DIR__);

$siteDoctor = new SiteDoctor();
$diagnosis = $siteDoctor->diagnose($builder, __DIR__, ['cecil.yml']);

$seoDoctor = new SeoDoctor();
$seoAudit = $seoDoctor->audit($builder, [
    'page' => '',
    'include_virtual' => false,
]);

var_dump($diagnosis['errors'], $seoAudit['summary']);
```
