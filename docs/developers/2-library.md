<!--
title: "Library"
description: "Use Cecil as a PHP library: install it with Composer, then build, convert Markdown and diagnose programmatically."
date: 2023-12-13
updated: 2026-10-30
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

### Markdown

Convert Markdown to HTML like Cecil does (Markdown parser of the site, [`pages.body`](../configuration/4-pages.md#pages-body) configuration, notes, code highlighting), without building the website: e.g. to preview a page while it is edited.

```php
<?php

require_once 'vendor/autoload.php';

use Cecil\Builder;
use Cecil\Converter\ParserFactory;

$builder = Builder::create(require 'config.php')->setSourceDir(__DIR__);

$parser = ParserFactory::create($builder, [
    'language'  => 'en',
    'assets'    => false,
    'asset_url' => fn (string $path): string => 'http://localhost:8000' . $path,
]);

echo $parser->text(file_get_contents('pages/index.md'));
```

The Markdown parser options:

| Option      | Description                                                                                                                                          | Default             |
| ----------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------- |
| `language`  | Language of the content.                                                                                                                             | `null`              |
| `selectors` | Headings of the table of contents.                                                                                                                   | `pages.body.toc`    |
| `assets`    | Processes the files of the body (images, video and audio) as assets (located, resized, converted, copied): with `false`, they are kept as they are.  | `true`              |
| `asset_url` | With `assets` set to `false`, a callable returning the URL of a local file from its path in the body.                                                | `null`              |

:::tip
Without processing the files as assets, a missing image does not throw an exception, nothing is written to the cache, and remote images are not downloaded.
:::

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
