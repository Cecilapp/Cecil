<!--
title: "Bibliothèque"
description: "Utilisez Cecil comme bibliothèque PHP : installez-le avec Composer, puis générez et diagnostiquez par programmation."
date: 2026-03-27
updated: 2026-10-19
alias: documentation/bibliotheque
path: documentation/developpeurs/bibliotheque
-->
# Bibliothèque

Cecil propose une API PHP simple pour générer votre site web.

Vous pouvez consulter la [documentation de l’API](/documentation/library/api/namespaces/cecil.html) pour plus de détails.

## Installation

```bash
composer require cecil/cecil
```

### Prise en charge de libvips

Pour traiter les images avec [libvips](https://www.libvips.org/) (optionnel), installez le pilote libvips dans votre projet :

```bash
composer require intervention/image-driver-vips
```

:::important
Ce pilote nécessite [libvips](https://www.libvips.org/install.html) installé sur le système et l’extension PHP [FFI](https://www.php.net/manual/fr/book.ffi.php) activée.

Cecil utilise [Imagick](https://www.php.net/manual/fr/book.imagick.php) s’il est disponible, sinon libvips (s’il est installé), sinon [GD](https://www.php.net/manual/fr/book.image.php).
:::

## Utilisation

### Génération

Générez un nouveau site web avec une configuration personnalisée :

```php
<?php

require_once 'vendor/autoload.php';

use Cecil\Builder;

$config = [
    'title'   => "My website",
    'baseurl' => 'https://domain.tld/',
];

Builder::create($config)
    ->setSourceDir(__DIR__)      // répertoire des sources du site (répertoire courant par défaut)
    ->setDestinationDir(__DIR__) // où le répertoire de sortie (`_site`) est créé (répertoire source par défaut)
    ->build([]);

exec('php -S localhost:8000 -t _site'); // prévisualisation locale
```

:::info
`Builder::create()` accepte un `array` PHP ou une instance de [`Cecil\Config`](https://github.com/Cecilapp/Cecil/blob/main/src/Config.php), et optionnellement un logger [PSR-3](https://www.php-fig.org/psr/psr-3/).
:::

La méthode `build()` accepte un tableau d’options :

| Option          | Description                                              | Par défaut |
| --------------- | -------------------------------------------------------- | ---------- |
| `drafts`        | Inclut les brouillons.                                   | `false`    |
| `dry-run`       | Génère sans enregistrer les fichiers.                    | `false`    |
| `page`          | Génère une page spécifique.                              | `''`       |
| `render-subset` | Effectue le rendu d’un sous-ensemble nommé de pages.     | `''`       |

Ces options correspondent à celles de la commande [`build`](../commands/4-build.fr.md).

### Diagnostics

Vous pouvez aussi exécuter les vérifications de doctor via des services dédiés, sans utiliser les commandes CLI :

- `Cecil\Doctor\SiteDoctor::diagnose()` : configuration et environnement
- `Cecil\Doctor\FrontmatterDoctor::diagnose()` : front matter des pages
- `Cecil\Doctor\SeoDoctor::audit()` : SEO des pages rendues (lance une génération à blanc, en dry-run)
- `Cecil\Doctor\LinksDoctor::audit()` : liens internes et externes

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
    'include_generated' => false,
]);

var_dump($diagnosis['errors'], $seoAudit['summary']);
```
