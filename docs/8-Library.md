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

Build a new website with a custom configuration:

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
The main parameter of the `create` method should be a PHP `array` or a [`Cecil\Config`](https://github.com/Cecilapp/Cecil/blob/master/src/Config.php) instance.
:::

### Example

```php
require_once 'vendor/autoload.php';

use Cecil\Builder;

// Build with the website with the `config.php` configuration file
Cecil::create(require('config.php'))->build();

// Preview locally
exec('php -S localhost:8000 -t _site');
```
