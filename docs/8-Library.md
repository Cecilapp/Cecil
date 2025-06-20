<!--
description: "Use Cecil as a PHP library."
date: 2023-12-13
updated: 2025-06-20
-->
# Library

You can use the Cecil as a [PHP](https://www.php.net) library.

## Installation

```bash
composer require cecil/cecil
```

## API

Cecil provides a simple PHP API to build your website.

You can read the [API documentation](https://cecil.app/documentation/library/api/namespaces/cecil.html) for more details.

## Usage

### Build

Build with the default configuration.

```php
use Cecil\Builder;

Builder::create()->build();
```

Build with custom configuration:

```php
$config = [
    'title'   => "My website",
    'baseurl' => 'http://localhost:8000/',
];

Builder::create($config)->build();
```

> The main parameter of the `create` method should be an array or a [`Cecil\Config`](https://github.com/Cecilapp/Cecil/blob/master/src/Config.php) instance.

:::info
The full code of _Builder_ is available on [GitHub](https://github.com/Cecilapp/Cecil/blob/master/src/Builder.php).
:::

### Example

```php
require_once 'vendor/autoload.php';

use Cecil\Builder;

// Build with the website with the `config.php` configuration file
Cecil::create(require('config.php'))->build();

// Run a local server
exec('php -S localhost:8000 -t _site');
```
