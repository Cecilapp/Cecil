<!--
title: "Library"
description: "Use Cecil as a PHP library: install it with Composer, then build and diagnose programmatically."
date: 2023-12-13
updated: 2026-10-19
alias: documentation/library
-->
# Library

Cecil provides a simple PHP API to build your website.

You can read the [API documentation](/documentation/library/api/namespaces/cecil.html) for more details.

## Installation

```bash
composer require cecil/cecil
```

### libvips support

To process images with [libvips](https://www.libvips.org/) (optional), install the libvips driver in your project:

```bash
composer require intervention/image-driver-vips
```

:::important
This driver requires [libvips](https://www.libvips.org/install.html) installed on your system and the PHP [FFI](https://www.php.net/manual/book.ffi.php) extension enabled.

Cecil uses [Imagick](https://www.php.net/manual/book.imagick.php) when available, otherwise libvips (if installed), otherwise [GD](https://www.php.net/manual/book.image.php).
:::

## Usage

### Build

Build a new website with a custom configuration:

```php
<?php

require_once 'vendor/autoload.php';

use Cecil\Builder;

$config = [
    'title'   => "My website",
    'baseurl' => 'https://domain.tld/',
];

Builder::create($config)
    ->setSourceDir(__DIR__)      // directory of the website sources (current directory by default)
    ->setDestinationDir(__DIR__) // where the output directory (`_site`) is created (source directory by default)
    ->build([]);

exec('php -S localhost:8000 -t _site'); // preview locally
```

:::info
`Builder::create()` accepts a PHP `array` or a [`Cecil\Config`](https://github.com/Cecilapp/Cecil/blob/main/src/Config.php) instance, and optionally a [PSR-3](https://www.php-fig.org/psr/psr-3/) logger.
:::

The `build()` method accepts an array of options:

| Option          | Description                                              | Default |
| --------------- | -------------------------------------------------------- | ------- |
| `drafts`        | Includes drafts.                                         | `false` |
| `dry-run`       | Builds without saving files.                             | `false` |
| `page`          | Builds a specific page.                                  | `''`    |
| `render-subset` | Renders a named subset of pages.                         | `''`    |

These options match the [`build`](../commands/4-build.md) command options.

### Diagnostics

You can also run doctor checks through dedicated domain services, without using CLI commands:

- `Cecil\Doctor\SiteDoctor::diagnose()`: configuration and environment
- `Cecil\Doctor\FrontmatterDoctor::diagnose()`: front matter of pages
- `Cecil\Doctor\SeoDoctor::audit()`: SEO of rendered pages (runs a dry-run build)
- `Cecil\Doctor\LinksDoctor::audit()`: internal and external links

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
