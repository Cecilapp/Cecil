# Cecil AI Coding Agent Instructions

## Project Overview

Cecil is a PHP-based static site generator (SSG) that converts Markdown content + Twig templates → static HTML websites. The current active branch `php-di` is migrating from manual instantiation to PHP-DI dependency injection.

**Core architecture**: Content → Builder (orchestrator) → Steps pipeline → Twig renderer → Static output

## Key Architecture Patterns

### Build Pipeline (Critical)

The `Builder` class orchestrates the build through a sequential pipeline defined in `Builder::STEPS`:

```text
1. Load (Pages/Data/StaticFiles) → 2. Create (Pages/Taxonomies/Menus) →
3. Process (Convert/Generate/Render) → 4. Save (StaticFiles/Pages/Assets) →
5. Optimize (Html/Css/Js/Images)
```

All steps implement `StepInterface` and extend `AbstractStep` (see [src/Step/](src/Step/)). Each step receives `Builder`, `Config`, and `LoggerInterface` via DI.

### Dependency Injection (NEW - Active Migration)

**Container initialization** happens in `Builder::__construct()` via `ContainerFactory::create()`:

```php
// Dependencies defined in config/dependencies.php
// Uses autowiring by default for most services
// Specific bindings for: Parsedown, GeneratorManager, Twig factory, Cache
```

**Adding new services**:

1. Use constructor injection with type hints (autowired automatically)
2. For complex setup, add explicit binding to `config/dependencies.php`
3. Steps, Generators, and Commands automatically get `Builder`, `Config`, `Logger`

See [docs/di/README.md](docs/di/README.md) for comprehensive DI guide.

### Collections System

- **Page collection** (`Collection\Page\Collection`): Core content container with filtering/sorting
- **Pages** (`Collection\Page\Page`): Individual content items with front matter + body + metadata
- **Taxonomies** (`Collection\Taxonomy\*`): Vocabulary/Term hierarchies (tags, categories)

Page properties: `id`, `slug`, `path`, `section`, `type`, `frontmatter`, `body`, `html`, `rendered[]`

## Content Processing Flow

1. **Load**: Parse Markdown files from `pages/` → create Page objects
2. **Convert**: Parsedown converts Markdown → HTML (`body` → `html`)
3. **Generate**: Apply generators (pagination, taxonomies, etc.) → create virtual pages
4. **Render**: Twig templates + page variables → final HTML
5. **Save**: Write to `_site/` (or configured output directory)

## Template System (Twig)

**Template lookup order** (see `Builder::STEPS` docs):

- User's `layouts/` dir → theme's `layouts/` → built-in `resources/layouts/`
- Naming: `<section>/<layout>.<format>.<language>.twig` (e.g., `blog/list.html.fr.twig`)

**Custom Twig extensions**: Place in `extensions/Cecil/Renderer/Extension/` and register in config:

```yaml
layouts:
  extensions:
    MyExtension: Cecil\Renderer\Extension\MyExtension
```

**Key filters/functions**: See [src/Renderer/Extension/Core.php](src/Renderer/Extension/Core.php)

- Filters: `filter_by`, `sort_by_*`, `markdown_to_html`, `to_css`, `minify`, `resize`
- Functions: `url()`, `asset()`, `image()`, `readtime()`, `getenv()`

## Development Workflows

### Running Tests

```bash
composer test              # Integration tests
composer test:cli          # CLI integration tests
composer test:coverage     # With coverage report
```

Test fixtures in `tests/fixtures/website/` - full site structure for testing.

### Code Quality

```bash
composer code             # Run all checks (analyse + md + fix + style)
composer code:analyse     # PHPStan static analysis (level 2)
composer code:fix         # PHP-CS-Fixer (PSR-12)
composer code:style       # PHP_CodeSniffer
```

Configuration files: `.phpmd-rules.xml`, `phpstan.neon`, `.php-cs-fixer.php`

### Building PHAR

```bash
composer build:phar       # Creates dist/cecil.phar via Box
composer build:package    # Creates standalone package with PHP binary via phpacker
composer test:phar        # Test the PHAR with demo site
```

Config: `box.json`, `phpacker.json`

### Local Development

```bash
php bin/cecil build [path] [--drafts] [--optimize]
php bin/cecil serve       # Dev server with livereload
php bin/cecil clear       # Clear all caches
```

Debug mode: Set `debug: true` in `cecil.yml` or `CECIL_DEBUG=true` env var

## Configuration System

Config cascade: `config/base.php` → `config/default.php` → `cecil.yml` → CLI options

- **base.php**: Core defaults (generators priority, page defaults, output formats)
- **default.php**: User-facing defaults (layouts, optimization, cache)
- **dependencies.php**: DI container bindings

Access: `$this->config->get('key.subkey')` or `$this->builder->getConfig()`

## Project-Specific Conventions

### Namespace Structure

```text
Cecil\
├─ Builder, Config, Cache, Url       # Core services
├─ Collection\                       # Content collections
│  ├─ Page\, Menu\, Taxonomy\
├─ Step\                            # Build pipeline steps
│  ├─ Pages\, Data\, StaticFiles\, Optimize\
├─ Generator\                       # Page generators (virtual pages)
├─ Renderer\                        # Twig + extensions
├─ Converter\                       # Markdown/content conversion
└─ Command\                         # Symfony Console commands
```

### File Naming

- Classes: PascalCase, match namespace structure
- Tests: `*Test.php` suffix
- Config: lowercase with `.php` extension
- Templates: `<type>.<format>.<lang?>.twig`

### Error Handling

- Use typed exceptions from `Exception/` namespace
- Steps should log errors via `$this->logger` before throwing
- Catch exceptions in `Builder` to show user-friendly messages

## External Integrations

- **Symfony Components**: Console, Finder, Filesystem, Yaml, Serializer, Translation
- **Twig**: Core template engine + extras (Intl, String, Cache)
- **Parsedown**: Markdown conversion (extended in `Converter/Parsedown.php`)
- **Intervention Image**: Image manipulation and optimization
- **SCSS/PHP**: CSS preprocessing via `scssphp/scssphp`

## Critical Files for Context

- [src/Builder.php](src/Builder.php): Main orchestrator, understand this first
- [config/dependencies.php](config/dependencies.php): DI container bindings
- [src/Step/AbstractStep.php](src/Step/AbstractStep.php): Base class for all build steps
- [src/Collection/Page/Page.php](src/Collection/Page/Page.php): Core content model
- [src/Renderer/Twig.php](src/Renderer/Twig.php): Template engine initialization
- [docs/di/README.md](docs/di/README.md): Complete DI migration guide

## Common Tasks

**Adding a new build step**:

1. Create class in `src/Step/<Category>/` extending `AbstractStep`
2. Implement `process()` method
3. Add to `Builder::STEPS` array (order matters!)
4. Register in `config/dependencies.php` with `autowire()`

**Adding a new generator**:

1. Create class in `src/Generator/` implementing `GeneratorInterface`
2. Add to `config/base.php` under `pages.generators` with priority
3. Register in `config/dependencies.php`

**Adding a Twig filter/function**:

1. Add method to `src/Renderer/Extension/Core.php`
2. Register in `getFilters()` or `getFunctions()`
3. Document in `docs/3-Templates.md`
