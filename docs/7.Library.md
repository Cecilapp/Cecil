<!--
description: "Use Cecil as a PHP library."
date: 2020-12-19
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
Cecil::create()->build();
```

Build with custom configuration :

```php
Cecil::create(
    [
        'title'   => "My website",
        'baseurl' => 'http://localhost:8000/',
    ]
)->build();
```

The main parameter of the `create` method should be a PHP array or a [`Cecil\Config`](https://github.com/Cecilapp/Cecil/blob/master/src/Config.php) instance.

### Change _source_ directory

```php
Cecil::create()
    ->setSourceDir(__DIR__.'/source')
    ->build();
```

### Change _destination_ directory

```php
Cecil::create()
    ->setDestinationDir(__DIR__.'/destination')
    ->build();
```

## Example

```php
<?php
date_default_timezone_set('Europe/Paris');
require_once 'vendor/autoload.php';

use Cecil\Builder;

// Run the builder
Cecil::create(
    [
        'title'   => "My website",
        'baseurl' => 'http://localhost:8000/',
    ]
)->build();

// Run a local server
exec('php -S localhost:8000 -t _site');
```

