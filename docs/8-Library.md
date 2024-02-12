<!--
description: "Use Cecil as a PHP library."
date: 2023-12-13
-->
# Library

You can use the Cecil as a [PHP](https://www.php.net) library.

## Installation

```bash
composer require cecil/cecil
```

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
Full code of _Builder_ available on [GitHub](https://github.com/Cecilapp/Cecil/blob/master/src/Builder.php).
:::

### Example

```php
<?php

date_default_timezone_set('Europe/Paris');
require_once 'vendor/autoload.php';

use Cecil\Builder;

// Run the builder
Cecil::create(require('config.php'))->build();

// Run a local server
exec('php -S localhost:8000 -t _site');
```
