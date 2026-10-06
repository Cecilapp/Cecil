<!--
title: "Bibliothèque"
description: "Utilisez Cecil comme bibliothèque PHP : installez-le avec Composer, puis générez et diagnostiquez par programmation."
date: 2026-03-27
updated: 2026-10-03
alias: documentation/bibliotheque
path: documentation/developpeurs/bibliotheque
-->
# Bibliothèque

Cecil propose une API PHP simple pour générer votre site web.

Vous pouvez consulter la [documentation de l'API](https://cecil.app/documentation/library/api/namespaces/cecil.html) pour plus de détails.

## Installation

```bash
composer require cecil/cecil
```

### Support de libvips

Pour traiter les images avec [libvips](https://www.libvips.org/) (optionnel), installez le driver libvips dans votre projet :

```bash
composer require intervention/image-driver-vips
```

:::important
Ce driver nécessite [libvips](https://www.libvips.org/install.html) installé sur le système et l’extension PHP [FFI](https://www.php.net/manual/book.ffi.php) activée.  
Sans lui, Cecil utilise [Imagick](https://www.php.net/manual/book.imagick.php) ou [GD](https://www.php.net/manual/book.image.php) à la place.
:::

## Utilisation

### Construction

Construisez un nouveau site web avec une configuration personnalisée :

```php
require_once 'vendor/autoload.php';

use Cecil\Builder;

$config = [
    'title'   => "My website",
    'baseurl' => 'https://domain.tld/',
];

Builder::create($config)->build();

exec('php -S localhost:8000 -t _site'); // prévisualisation locale
```

:::info
Le paramètre principal de la méthode `create` doit être un `array` PHP ou une instance de [`Cecil\Config`](https://github.com/Cecilapp/Cecil/blob/main/src/Config.php).
:::

### Diagnostic

Vous pouvez aussi exécuter les vérifications doctor via des services de domaine dédiés, sans utiliser les commandes CLI.

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
