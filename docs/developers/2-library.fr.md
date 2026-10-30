<!--
title: "Bibliothèque"
description: "Utilisez Cecil comme bibliothèque PHP : installez-le avec Composer, puis générez, convertissez du Markdown et diagnostiquez par programmation."
date: 2026-03-27
updated: 2026-10-30
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

### Markdown

Convertissez du Markdown en HTML comme le fait Cecil (analyseur Markdown du site, configuration [`pages.body`](../configuration/4-pages.fr.md#pages-body), notes, coloration du code), sans générer le site : par exemple pour prévisualiser une page pendant sa rédaction.

```php
<?php

require_once 'vendor/autoload.php';

use Cecil\Builder;
use Cecil\Converter\ParserFactory;

$builder = Builder::create(require 'config.php')->setSourceDir(__DIR__);

$parser = ParserFactory::create($builder, [
    'language'  => 'fr',
    'assets'    => false,
    'asset_url' => fn (string $path): string => 'http://localhost:8000' . $path,
]);

echo $parser->text(file_get_contents('pages/index.md'));
```

Les options de l’analyseur Markdown :

| Option      | Description                                                                                                                                                         | Par défaut       |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------- |
| `language`  | Langue du contenu.                                                                                                                                                  | `null`           |
| `selectors` | Titres de la table des matières.                                                                                                                                    | `pages.body.toc` |
| `assets`    | Traite les fichiers du corps (images, vidéo et audio) comme des assets (localisés, redimensionnés, convertis, copiés) : avec `false`, ils sont gardés tels quels.   | `true`           |
| `asset_url` | Avec `assets` à `false`, un callable qui renvoie l’URL d’un fichier local à partir de son chemin dans le corps.                                                     | `null`           |

:::tip
Sans traiter les fichiers comme des assets, une image manquante ne lève pas d’exception, rien n’est écrit dans le cache et les images distantes ne sont pas téléchargées.
:::

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
