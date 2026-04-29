# Cecil - Copilot Instructions

## Project Overview

Cecil is a PHP static site generator (SSG) built on Symfony components and Twig templating. It converts Markdown content into static websites.

- **Namespace:** `Cecil\`
- **PHP version:** 8.2+
- **Entry point:** `Builder::create($config)->build()`
- **CLI:** Symfony Console (`src/Application.php`)

## Documentation

- **README.md:** project overview, installation, usage, contribution guidelines
- **docs/**: detailed documentation on configuration, content model, architecture, and development guidelines
- **Code comments:** DocBlocks for all classes, methods, and properties

## Architecture

### Build Pipeline

Builder → Steps → Generators → Renderer → Output

- **Steps** (`src/Step/`): sequential build phases (Pages, Data, Assets, Taxonomies, Menus, Optimize, StaticFiles)
- **Generators** (`src/Generator/`): page generators run via `SplPriorityQueue` — lower numeric priority is processed first (DefaultPages=10, VirtualPages=20, ExternalBody=30, Section=40, Taxonomy=50, Homepage=60, Pagination=70, Alias=80, Redirect=90)
- **Renderer** (`src/Renderer/`): Twig-based rendering with custom extensions

### Content Model

- **Pages** (`src/Collection/Page/`): content with frontmatter, organized into sections and taxonomies
- **Page type** is stored as `Type` enum internally; `setType()` accepts strings, `getType()` returns `enum->value` string
- **Section assignment** uses original `filepath` variable (not the transformed page path)
- **Sub-sections** are created when a folder within the `pages/` directory contains an `index.md` file

### Configuration

Hierarchical PHP/YAML config with dot notation access (`src/Config.php`):
- `config/base.php` — generator pipeline and default pages
- `config/default.php` — site defaults (title, taxonomies, pages, assets, etc.)

## Coding Standards

| Category                      | Rule                                               |
| ----------------------------- | -------------------------------------------------- |
| **PHP — style**               | PSR-12 (enforced by php-cs-fixer and phpcs)        |
| **PHP — indentation**         | 4 spaces                                           |
| **PHP — strict types**        | `declare(strict_types=1);` in every file           |
| **PHP — native functions**    | Prefix with `\` (e.g., `\count()`, `\array_map()`) |
| **PHP — nullable params**     | Use `?Type` for parameters with default `null`     |
| **Other files — indentation** | 2 spaces (YAML, Twig, JS, etc.)                    |
| **Other files — encoding**    | LF line endings, UTF-8                             |
| **Twig**                      | No final newline                                   |
| **Markdown**                  | Preserve trailing whitespace                       |

### Required PHP File Header

All PHP files must start with:

```php
<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
```

## Commands

| Task                              | Command                  |
| --------------------------------- | ------------------------ |
| Run all quality checks            | `composer code`          |
| Static analysis (PHPStan level 2) | `composer code:analyse`  |
| Mess detector                     | `composer code:md`       |
| Code style fix (php-cs-fixer)     | `composer code:fix`      |
| Code sniffer (PSR-12)             | `composer code:style`    |
| Integration tests                 | `composer test`          |
| CLI tests                         | `composer test:cli`      |
| Build PHAR                        | `composer build`         |

## Key Conventions

- Extend `AbstractCommand` for new CLI commands, `AbstractGenerator` for new generators, `AbstractStep` for new steps
- Use PSR-3 `LoggerInterface` for logging
- Use PSR-16 `SimpleCacheInterface` for caching
- Collections implement `CollectionInterface` with `Item` objects
- Exceptions extend `Cecil\Exception\ExceptionInterface`
- Templates are Twig files in `resources/layouts/`
- Translations use Symfony Translation component (`resources/translations/`)
- Update documentation in `docs/` when adding features or changing architecture
