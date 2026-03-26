<!--
description: "Utilisez Cecil comme bibliothèque PHP."
date: 2023-12-13
updated: 2025-06-20
-->
# Bibliothèque

Vous pouvez utiliser Cecil comme bibliothèque [PHP](https://www.php.net).

## Installation

```bash
composer require cecil/cecil
```

## API

Cecil propose une API PHP simple pour générer votre site web.

Vous pouvez consulter la [documentation de l'API](https://cecil.app/documentation/library/api/namespaces/cecil.html) pour plus de détails.

## Utilisation

### Construction

Construisez un nouveau site web avec une configuration personnalisée :

```php
use Cecil\Builder;

// Create a configuration array
$config = [
    'title'   => "My website",
    'baseurl' => 'https://domain.tld/',
];

// Build with the custom configuration
Builder::create($config)->build();
```

:::info
Le paramètre principal de la méthode `create` doit être un `array` PHP ou une instance de [`Cecil\Config`](https://github.com/Cecilapp/Cecil/blob/master/src/Config.php).
:::

### Exemple

```php
require_once 'vendor/autoload.php';

use Cecil\Builder;

// Build with the website with the `config.php` configuration file
Cecil::create(require('config.php'))->build();

// Preview locally
exec('php -S localhost:8000 -t _site');
```
