<!--
description: "Use Cecil as a PHP library."
date: 2023-12-13
updated: 2026-06-13
-->
# Library

Cecil provides a simple PHP API to build your website.

You can read the [API documentation](https://cecil.app/documentation/library/api/namespaces/cecil.html) for more details.

## Installation

```bash
composer require cecil/cecil
```

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
Builder::create(require('config.php'))->build();

// Preview locally
exec('php -S localhost:8000 -t _site');
```

### Doctor services

You can also run doctor checks through dedicated domain services, without using CLI commands.

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
