---
name: cecil
description: Build and configure Cecil static sites, with focused guidance for content, templates, and site generation.
license: EUPL-1.2
---

# Cecil Site Builder

You are an expert Cecil developer capable of creating and generating static websites with Cecil, a PHP-based static site generator powered by Symfony components and Twig.

## When to Use This Skill

Use this skill when:

- Creating or scaffolding a new Cecil site
- Building and generating static websites with Cecil
- Configuring site settings, taxonomies, and content organization
- Creating or updating Twig templates and layouts
- Managing assets, including images and stylesheets
- Deploying Cecil-generated static sites
- Troubleshooting build issues or optimizing the performance of the generated site and build process
- Working with Cecil's plugin/extension system

## Project Structure

### Directory Layout

```
my-site/
├── cecil.yml              # Main configuration file (or config.yml)
├── pages/                 # Markdown pages
├── layouts/               # Twig templates
├── assets/                # Processed files (CSS, JS, images)
├── static/                # Static files copied as-is
└── data/                  # Data collections (YAML/JSON/...)
```

### Key Directories

- **pages/** - Markdown content files organized into sections
- **layouts/** - Twig templates and partials
- **assets/** - Files handled by Cecil (Sass compilation, minification, image handling)
- **static/** - Files copied to output without transformation
- **data/** - Data files exposed in templates via `site.data`

## Cecil Fundamentals

### Architecture

Cecil follows a build pipeline:

```
Builder → Steps → Generators → Renderer → Output
```

- **Steps** (`Step/`): Sequential build phases
  - Pages: Parse markdown content
  - Data: Load data files
  - Assets: Process assets
  - Taxonomies: Generate taxonomy pages
  - Menus: Build navigation structures
  - Optimize: Optimize output
  - StaticFiles: Copy static files

- **Generators** (`Generator/`): Page generators executed via priority queue
  - Lower numeric priority executes first
  - DefaultPages (10) → VirtualPages (20) → ExternalBody (30) → Section (40) → Taxonomy (50) → Homepage (60) → Pagination (70) → Alias (80) → Redirect (90)

- **Renderer** (`Renderer/`): Twig-based rendering with custom extensions
- **Output**: Built static site in `_site/` directory

### Content Model

- **Pages**: Markdown files composed of front matter and body
- **Front matter**: Metadata surrounded by separators (`---`, `+++`, or `<!-- -->`)
- **Section**: Root folder in `pages/` (e.g. `pages/blog/post-1.md` -> section `blog`)
- **File-based routing**: Files under `pages/` define generated paths
- **Collections**: Pages, taxonomies, data and static files are exposed to templates

### Configuration

Configuration is defined in `cecil.yml` or `config.yml` at project root:

- Core options are top-level keys such as `title`, `baseurl`, `description`, `taxonomies`, `menus`
- Dot notation in templates applies to `site` variable access (for example `site.title`)
- Defaults are defined in `config/default.php` and base pipeline in `config/base.php`

## Building a Cecil Site

### Step 1: Download Cecil

Download Cecil using curl:

```bash
curl -LO https://cecil.app/cecil.phar
chmod +x cecil.phar
```

### Step 2: Create a New Site

Use the `new:site` command to scaffold a new website:

```bash
php cecil.phar new:site
```

### Step 3: Configure the Site

Edit **cecil.yml**:

```yaml
title: My Site
baseurl: https://example.com/
description: My awesome static site
taxonomies:
  categories: category
  tags: tag
```

### Step 4: Create Content

Create a page with:

```bash
php cecil.phar new:page
```

Then edit the generated file in `pages/`:

```markdown
---
title: My First Post
description: Welcome to my blog
date: 2024-05-14
tags: [Welcome, "First post"]
---
# My First Post

This is my first post content.
```

### Step 5: Create Templates

Create Twig templates in `layouts/` (for example `layouts/page.html.twig`):

```twig
<!DOCTYPE html>
<html>
  <head>
    <title>{{ page.title }} - {{ site.title }}</title>
  </head>
  <body>
    <header>
      <h1>{{ site.title }}</h1>
    </header>
    <main>
      {{ page.content }}
    </main>
    <footer>
      <p>&copy; {{ site.title }}</p>
    </footer>
  </body>
</html>
```

### Step 6: Build the Site

```bash
php cecil.phar build
```

Output is generated in `_site/` directory.

## CLI Commands

| Command                           | Purpose                                         |
|-----------------------------------|-------------------------------------------------|
| `php cecil.phar new:site`         | Create a new website                            |
| `php cecil.phar new:page`         | Create a new page                               |
| `php cecil.phar build`            | Build the static site                           |
| `php cecil.phar serve`            | Start local server with live reload             |
| `php cecil.phar show:config`      | Display effective configuration                 |
| `php cecil.phar cache:clear`      | Clear all cache files                           |
| `php cecil.phar clear`            | Remove generated files                          |

## Template Development

Twig templates live in `layouts/` and follow Cecil naming conventions.

### Naming Convention

Use this pattern:

```plaintext
layouts/(<section>/)<type>|<layout>.<format>(.<language>).twig
```

Examples:

- `layouts/page.html.twig` - default page template
- `layouts/list.html.twig` - section/home/term listing template
- `layouts/blog/list.rss.twig` - RSS template for `blog` section
- `layouts/page.html.fr.twig` - French page template
- `layouts/_default/page.html.twig` - fallback template

### Lookup Rules (How Cecil Chooses a Template)

1. Identify the page kind and check section-specific or explicit `layout` templates first.
2. Apply the matching fallback chain for that page kind:

| Page Kind | Step 1 | Step 2 | Step 3 | Step 4 |
|-----------|--------|--------|--------|--------|
| Homepage | `index.*` | `home.*` | `list.*` | `_default/*` |
| Standard page | `page.*` | `_default/page.*` | - | - |
| Section page | section-specific `list.*` or explicit `layout.*` | `list.*` | `_default/*` | - |
| Taxonomy page | taxonomy template or explicit `layout.*` | `list.*` | `_default/*` | - |

In practice, you usually need only:

- `layouts/page.html.twig`
- `layouts/list.html.twig`
- optional overrides in `layouts/_default/` or per section

### Template Variables

Most useful variables in Twig:

- `site.title`, `site.baseurl`, `site.description`
- `site.pages` - pages collection (current language)
- `site.allpages` - pages in all languages
- `site.taxonomies` - vocabularies and terms
- `site.menus.<name>` - menu entries
- `page.title`, `page.date`, `page.content`, `page.path`, `page.type`, `page.section`

### Multilingual Sites

Configure languages in `cecil.yml`:

```yaml
language: en
languages:
  - code: en
    name: English
    locale: en_US
  - code: fr
    name: Francais
    locale: fr_FR
```

Use suffixed filenames for translations:

```plaintext
pages/about.md
pages/about.fr.md
```

You can render a language switcher in templates with:

```twig
{% include 'partials/languages.html.twig' %}
```

Useful collection helpers:

- `site.pages.showable` to skip draft/virtual/excluded pages
- `sort_by_weight` filter for menu entries

### Example Template

```twig
{# layouts/page.html.twig #}
<!DOCTYPE html>
<html lang="{{ site.language }}">
  <head>
    <meta charset="utf-8">
    <title>{{ page.title }} - {{ site.title }}</title>
    {{ include('partials/metatags.html.twig') }}
  </head>
  <body>
    <header>
      <h1><a href="{{ url('/') }}">{{ site.title }}</a></h1>
      {% if site.menus.main is defined %}
      <nav>
        <ul>
        {% for entry in site.menus.main|sort_by_weight %}
          <li><a href="{{ url(entry.url) }}">{{ entry.name }}</a></li>
        {% endfor %}
        </ul>
      </nav>
      {% endif %}
    </header>

    <main>
      <article>
        <h2>{{ page.title }}</h2>
        {% if page.date %}
          <time datetime="{{ page.date|date('c') }}">{{ page.date|date('Y-m-d') }}</time>
        {% endif %}
        {{ page.content }}
      </article>
    </main>
  </body>
</html>
```

### Built-in Partials and Utilities

- `partials/metatags.html.twig` - SEO/social tags
- `partials/navigation.html.twig` - navigation helper
- `partials/paginator.html.twig` - pagination links
- `partials/languages.html.twig` - language switcher

If needed, extract built-in templates to customize them:

```bash
php cecil.phar util:templates:extract
```

### Pagination

Pagination is configured globally under `pages.pagination`, and can be overridden in section front matter.

```yaml
pages:
  pagination:
    max: 5
    path: page
```

In list templates, include paginator links with:

```twig
{% include 'partials/paginator.html.twig' %}
```

### Custom Filters and Functions

Core Twig helpers commonly used in Cecil templates:

- `url()` - generate internal/absolute URLs depending on config
- `asset()` - reference and process assets
- `include()` - compose templates with partials/components

## Build Optimization

### Asset Processing

Configure asset optimization:

```yaml
assets:
  minify: true
  fingerprint: true
  compile:
    style: compressed
  images:
    optimize: true
```

### Performance Tips

1. Use `draft: true` to exclude non-published content from builds
2. Enable asset minification and fingerprinting in production
3. Use output and format settings adapted to your pages types
4. Use responsive image options and image optimization when needed

## Extension & Plugins

### Custom Generators

Extend Cecil by creating custom generators:

```php
<?php

namespace MyProject\Generator;

use Cecil\Generator\AbstractGenerator;

class CustomGenerator extends AbstractGenerator
{
  public function generate(): void
  {
        // Custom generation logic
    }
}
```

Then register it in configuration with `pages.generators`.

```yaml
pages:
  generators:
    100: MyProject\\Generator\\CustomGenerator
```

### Custom Commands

Create CLI commands by extending `AbstractCommand`:

```php
<?php

namespace MyProject\Command;

use Cecil\Command\AbstractCommand;

class MyCommand extends AbstractCommand
{
    // Implementation
}
```

You can also extend Twig (via `layouts.extensions`) and post-process output (via `output.postprocessors`).

```yaml
layouts:
  extensions:
    MyExtension: MyProject\\Twig\\MyExtension
```

The Twig extension class should implement `Twig\Extension\ExtensionInterface` (or extend `Twig\Extension\AbstractExtension`).

```yaml
output:
  postprocessors:
    MyProcessor: MyProject\\Renderer\\PostProcessor\\MyProcessor
```

Post-processors should implement `Cecil\Renderer\PostProcessor\PostProcessorInterface`.

## Deployment

### Static Site Hosting

Cecil generates pure static HTML, compatible with:

- GitHub Pages
- Netlify
- Vercel
- AWS S3
- Any web server

### Build & Deploy Workflow

```bash
# Build
php cecil.phar build

# Deploy output directory (_site/)
# to your hosting platform
```

### GitHub Pages Example

```bash
php cecil.phar build
# Commit _site/ directory and push to gh-pages branch
```

## Code Quality Standards

When extending or contributing to Cecil:

- Follow PSR-12 coding standards
- Use `declare(strict_types=1);` in all PHP files
- Prefix native function calls with `\` (e.g., `\count()`)
- Include proper PHPDoc blocks for all classes and methods
- Use 4-space indentation for PHP, 2-space for YAML/Twig

## Useful Resources

- **Official website**: https://cecil.app
- **GitHub Repository**: https://github.com/Cecilapp/Cecil
- **Issue Tracker**: https://github.com/Cecilapp/Cecil/issues
- **Documentation**: https://cecil.app/documentation/

## Common Workflows

### Create a Blog

1. Create `pages/blog/index.md` for blog section
2. Add individual posts in `pages/blog/post-*.md`
3. Configure taxonomy for tags/categories
4. Create templates for listing and individual posts
5. Build with `php cecil.phar build`

### Add Custom Pages

1. Create markdown files in `pages/` directory
2. Add frontmatter with title and template
3. Create corresponding template in `layouts/`
4. Reference template in page frontmatter
5. Build to generate output

### Implement Search

1. Create `pages/search.json.md` with front matter `output: json`
2. Use JavaScript library (e.g., Lunr.js) on frontend
3. Create `layouts/search.json.twig` that iterates `site.pages.showable` and emits a JSON array of `{title, url, content}` objects
4. Add search functionality to templates

## Troubleshooting

When a user reports unexpected behavior or asks about a specific feature, ask them to run `php cecil.phar doctor` and include the output. If you are uncertain whether a feature is available in the user's Cecil version, say so explicitly and direct them to the official documentation at https://cecil.app/documentation/ rather than guessing version ranges.

### Common Issues

- **Site not generating**: Check `cecil.yml` syntax and configuration
- **Missing pages**: Ensure content files are in `pages/` directory
- **Template not loading**: Verify template path in frontmatter and layouts directory
- **Build errors**: Run `php cecil.phar build -vv` for verbose output
- **Cache issues**: Clear cache with `php cecil.phar cache:clear`

### Debug Output

Get detailed build information:

```bash
php cecil.phar build -v      # Verbose
php cecil.phar build -vv     # Very verbose
php cecil.phar build -vvv    # Debug
```
