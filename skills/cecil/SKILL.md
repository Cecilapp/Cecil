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
├── cecil.yml  # Main configuration file (or config.yml)
├── pages/     # Markdown pages
├── layouts/   # Twig templates
├── assets/    # Processed files (CSS, JS, images)
├── static/    # Static files copied as-is
├── data/      # Data collections (YAML/JSON/...)
└── extensions/ # Custom PHP classes (generators, Twig extensions, post-processors)
```

### Key Directories

- **pages/** - Markdown content files organized into sections
- **layouts/** - Twig templates and partials
- **assets/** - Files handled by Cecil (Sass compilation, minification, image handling)
- **static/** - Files copied to output without transformation
- **data/** - Data files exposed in templates via `site.data`
- **extensions/** - Custom PHP classes autoloaded by Cecil (file path must match the class namespace, e.g. `extensions/MyProject/Generator/CustomGenerator.php`)

## Cecil Fundamentals

### Architecture

Cecil follows a build pipeline:

```
Builder → Steps → Generators → Renderer → Output
```

- **Steps** (`Step/`): Sequential build phases, in this order
  - Load: pages, data files and static files
  - Pages Create / Convert: create pages collection, convert front matter and Markdown body
  - Taxonomies Create: build vocabularies and terms
  - Pages Generate: run generators (see below)
  - Menus Create: build navigation structures
  - StaticFiles Copy: copy static files
  - Pages Render / Save: render with Twig and write output files
  - Assets Save: save processed assets
  - Optimize: HTML, CSS, JS and images

- **Generators** (`Generator/`): Page generators executed via priority queue
  - Generators are ordered by numeric weight; lower numbers execute first (e.g., DefaultPages at weight 10 runs before Alias at weight 80).
  - DefaultPages (10) → VirtualPages (20) → ExternalBody (30) → Section (40) → Taxonomy (50) → Homepage (60) → Pagination (70) → Alias (80) → Redirect (90)

- **Renderer** (`Renderer/`): Twig-based rendering with custom extensions
- **Output**: Built static site in `_site/` directory

### Content Model

- **Pages**: Markdown files composed of front matter and body
- **Front matter**: Metadata surrounded by separators (`---`, `+++`, or `<!-- -->`)
- **Section**: Root folder in `pages/` (e.g. `pages/blog/post-1.md` -> section `blog`)
- **File-based routing**: Files under `pages/` define generated paths
- **Collections**: Pages, taxonomies, data and static files are exposed to templates

### Nested Sections (Sub-sections)

A nested folder that explicitly contains an `index.md` file becomes a _sub-section_ of its parent _Section_. A nested folder **without** an `index.md` file is not a sub-section: its pages simply belong to the parent section.

```plaintext
pages/
└─ blog                 # Section "blog"
   ├─ index.md
   ├─ post-1.md         # Page in "blog"
   └─ 2024              # Sub-section (contains an "index.md")
      ├─ index.md
      └─ post-2.md      # Page in "blog" AND "blog/2024"
```

A sub-section:

- Is a full _Section_ (same `type`, variables, and [layout](../../docs/templates/1-lookup-rules.md) resolution) available at its own URL (e.g. `/blog/2024/`)
- Falls back to the templates of its parent sections (e.g. `blog/list.html.twig` for `blog/2024` if `blog/2024/list.html.twig` doesn't exist)
- Can be nested at any depth (e.g. `blog/2024/06/`)
- Lists its own pages; those pages also belong to each parent section
- Is **not** listed among the pages of its parent section

Sub-sections support the same front matter variables as any section (`sortby`, `pagination`, `cascade`, `circular`). Use `cascade` on a parent `index.md` to propagate variables down to sub-sections and their pages.

In templates, use `page.parent`, `page.ancestors`, `page.sections` and `page.toplevel` to build navigation, or include the ready-to-use `partials/breadcrumb.html.twig` partial.

Previous/next navigation (`page.prev` / `page.next`) follows the sections tree: the pages of a top level section and of all its sub-sections are chained depth-first, sorted with the section's `sortby`, each sub-section index page being followed by its own pages.

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

### Step 5: Create Templates (Optional)

Cecil ships with [built-in templates](#built-in-templates) (`resources/layouts/`), so a site builds **without any template** in `layouts/`. Only create templates to customize the rendering, and prefer extending the built-in ones (see [Built-in Templates](#built-in-templates)).

Create Twig templates in `layouts/` (for example `layouts/page.html.twig`):

```twig
<!DOCTYPE html>
<html lang="{{ site.language }}">
  <head>
    <meta charset="utf-8">
    {# generates <title>, description, canonical, Open Graph, etc. #}
    {{ include('partials/metatags.html.twig') }}
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

| Command                              | Purpose                                                        |
|--------------------------------------|----------------------------------------------------------------|
| `php cecil.phar new:site`            | Create a new website                                           |
| `php cecil.phar new:page`            | Create a new page                                              |
| `php cecil.phar build`               | Build the static site                                          |
| `php cecil.phar serve`               | Start local server with live reload                            |
| `php cecil.phar serve --incremental` | Serve with incremental builds (rebuild only changed pages)     |
| `php cecil.phar serve:stop`          | Stop the local server                                          |
| `php cecil.phar edit`                | Open pages directory with the configured editor                |
| `php cecil.phar show:config`         | Display effective configuration                                |
| `php cecil.phar show:content`        | Display content tree (pages, data, static files)               |
| `php cecil.phar doctor`              | Diagnose site and environment (see also `doctor:frontmatter`, `doctor:seo`, `doctor:cache`) |
| `php cecil.phar cache:clear`         | Clear all cache files (or only `cache:clear:assets`, `cache:clear:templates`, `cache:clear:translations`) |
| `php cecil.phar clear`               | Remove generated files (or only `clear:output`, `clear:temporary`) |
| `php cecil.phar util:templates:extract` | Extract built-in templates into `layouts/`                  |
| `php cecil.phar util:translations:extract` | Extract translation strings from templates               |
| `php cecil.phar self-update`         | Update Cecil to the latest version                             |

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

Cecil uses the first existing template, in priority order, for each page type. `<layout>` is the front matter `layout` variable, and each entry resolves to `<name>.<format>.twig` (e.g. `blog/list.html.twig`):

| Page type  | Lookup order                                                                                                                         |
|------------|--------------------------------------------------------------------------------------------------------------------------------------|
| Homepage   | `<layout>` → `index` → `home` → `list` → `_default/<layout>` → `_default/index` → `_default/home` → `_default/list` → `_default/page` |
| Page       | `<section>/<layout>` → `<layout>` → `<section>/page` → `_default/<layout>` → `page` → `_default/page`                                |
| Section    | `<layout>` → `<section>/index` → `<section>/list` → `section/<section>` → `<parent>/index` → `<parent>/list` → `section/<parent>` → `_default/section` → `list` → `_default/list` |
| Vocabulary | `taxonomy/<plural>` → `vocabulary` → `_default/vocabulary`                                                                           |
| Term       | `taxonomy/<term>` → `taxonomy/<singular>` → `term` → `_default/term` → `_default/list`                                               |

For a sub-section, `<section>` is its full path (e.g. `blog/2024`) and the `<parent>` entries are repeated for each parent section, nearest first.

To render a section with the templates of another section, map it with `layouts.sections` (applies to the section and its pages):

```yaml
layouts:
  sections:
    news: blog # "news" uses blog/list.html.twig and blog/page.html.twig
```

Each candidate is searched in `layouts/` (site), then in `themes/<theme>/layouts/`, then in Cecil's built-in templates (`resources/layouts/`). Most `_default/*` templates exist built-in, which is why a site renders without any custom layout.

In practice, you usually need only:

- `layouts/page.html.twig`
- `layouts/list.html.twig`
- optional overrides per section

### Built-in Templates

Cecil embeds default templates in [`resources/layouts/`](https://github.com/Cecilapp/Cecil/tree/main/resources/layouts). They are always available to Twig (lowest priority, after site and theme layouts), so they can be rendered, included or extended **without being copied** into `layouts/`.

- `_default/` - fallback layouts: `page.html.twig`, `list.html.twig`, `home.html.twig`, `vocabulary.html.twig`, `term.html.twig`, `404.html.twig`, `404.json.twig`, `redirect.html.twig`, feeds (`list.atom.twig`, `list.rss.twig`, `list.jsonfeed.twig`), embeds (`page.embed.twig`, `page.oembed.twig`), JSON/Markdown/LLMs outputs, `sitemap.xml.twig`, `robots.txt.twig`, etc.
- `partials/` - reusable fragments (see [Built-in Partials](#built-in-partials-and-utilities))
- `extended/` - advanced/alternative variants
- `shortcodes.twig` - built-in shortcodes

Rules to follow:

1. **Don't recreate what already exists**: before writing a template, check whether a built-in one covers the need (feeds, sitemap, robots.txt, 404, redirects, JSON outputs are already provided).
2. **Extend rather than copy**: `_default/page.html.twig` exposes the blocks `head`, `head_metatags`, `head_css`, `header`, `content` and `footer`.

   ```twig
   {# layouts/page.html.twig #}
   {% extends '_default/page.html.twig' %}
   {% block content %}
     <article>{{ page.content }}</article>
   {% endblock %}
   ```

3. **Don't shadow a built-in template by accident**: a site file with the same path (e.g. `layouts/_default/page.html.twig` or `layouts/partials/metatags.html.twig`) fully replaces the built-in one for the whole site, and can't `extends` itself.
4. **Extract only as a last resort**: `php cecil.phar util:templates:extract` copies all built-in templates into `layouts/`; the copies then no longer receive Cecil updates.

### Metatags (`partials/metatags.html.twig`)

Always use the built-in `partials/metatags.html.twig` partial in the `<head>` of HTML layouts instead of hand-writing SEO/social tags. It is already included by `_default/page.html.twig` (block `head_metatags`).

```twig
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  {{ include('partials/metatags.html.twig') }}
</head>
```

It generates:

- `<title>` (page title + divider + site title; site title + baseline on the homepage; page number on paginated lists)
- `description`, `keywords` (from `tags`), `author`, `robots` (`noindex` on paginated pages)
- favicons (from `favicon.ico`, `favicon.svg`, `favicon.png` assets, resized)
- `prev`/`next`/`first`/`last` links, canonical and alternate formats, feeds, `hreflang` alternates
- `rel=me` links, Open Graph, Facebook, Twitter/X Card, Fediverse creator
- optional Dublin Core and JSON-LD structured data

Important:

- **Never add a separate `<title>`, `<meta name="description">`, canonical or `og:*` tags** next to this partial: they would be duplicated.
- Feed it through front matter (page) or configuration (site fallback): `title`, `description`, `tags`, `author`, `image`, `canonical.url`, `social.*`.
- Tune it with the `metatags` configuration (per page with front matter `metatags`):

  ```yaml
  metatags:
    title:
      divider: " &middot; "
      only: false        # page title only
    robots: "index,follow"
    favicon: true
    og: true
    twitter: true
    mastodon: true
    articles: "blog"     # section rendered as Open Graph "article"
    dc: false            # Dublin Core
    data: false          # JSON-LD structured data
  ```

- Override `title` or `image` for a specific template:

  ```twig
  {{ include('partials/metatags.html.twig', {title: 'Custom title', image: og_image}) }}
  ```

- Customize one part with `embed` and its blocks (`title`, `description`, `metatags_favicon`, `metatags_alternates`, `metatags_og`, `metatags_twitter`, `metatags_dc`, `metatags_structured_data`), instead of copying the whole file:

  ```twig
  {% embed 'partials/metatags.html.twig' %}
    {% block metatags_twitter %}{% endblock %}
  {% endembed %}
  ```

- Run `php cecil.phar doctor:seo` to check the generated metatags.

See the [metatags documentation](https://cecil.app/documentation/configuration/site/#metatags) for all options.

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
    name: Français
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {# no <title> here: metatags.html.twig generates it #}
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

Available in every site, without extraction (include them rather than rewriting them):

- `partials/metatags.html.twig` - all `<head>` SEO/social tags, including `<title>` (see [Metatags](#metatags-partialsmetatagshtmltwig))
- `partials/alternates.html.twig` - canonical and alternate formats links (included by metatags)
- `partials/alternates-languages.html.twig` - `hreflang` links (included by metatags)
- `partials/feeds-from-section.html.twig` - section feeds links (included by metatags)
- `partials/jsonld.js.twig` - JSON-LD structured data (included by metatags when `metatags.data` is enabled)
- `partials/navigation.html.twig` - main menu navigation
- `partials/page-navigation.html.twig` - previous/next page links (follows the sections tree)
- `partials/paginator.html.twig` - pagination links
- `partials/languages.html.twig` - language switcher
- `partials/breadcrumb.html.twig` - breadcrumb (nested sections aware)
- `partials/terms-list.html.twig` - taxonomy terms list
- `partials/theme-selector.html.twig` - light/dark theme toggle
- `partials/googleanalytics.js.twig` - Google Analytics snippet
- `partials/data.json.twig` - page data serialized as JSON (used by JSON outputs)
- `partials/pico.css.twig`, `partials/highlight.css.twig` - CSS used by the default layouts

If a built-in template really needs to be modified, extract them all into `layouts/` (last resort, see [Built-in Templates](#built-in-templates)):

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

### Image Variants (Dark and Mobile)

Cecil can serve alternative versions of an image in a `<picture>` element, when files with a configured suffix exist next to the original image (e.g. `photo.dark.jpg`, `photo.mobile.jpg`, `photo.mobile.dark.jpg`):

```yaml
layouts:
  images:                          # images rendered with the `html` Twig function
    dark_suffix: .dark             # adds <source media="(prefers-color-scheme: dark)">
    mobile_suffix: .mobile         # adds <source> with the mobile media query
    mobile_media_query: "(max-width: 767px)"
pages:
  body:
    images:                        # same options for images in Markdown content
      dark_suffix: .dark
      mobile_suffix: .mobile
```

Both are disabled by default (`null`).

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

Save it as `extensions/MyProject/Generator/CustomGenerator.php`, then register it in configuration with `pages.generators`.

```yaml
pages:
  generators:
    100: MyProject\Generator\CustomGenerator
```

> Note: use single backslashes in YAML. Double backslashes (`\\`) are only needed inside JSON or PHP strings.

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
    MyExtension: MyProject\Twig\MyExtension
```

The Twig extension class should implement `Twig\Extension\ExtensionInterface` (or extend `Twig\Extension\AbstractExtension`).

```yaml
output:
  postprocessors:
    MyProcessor: MyProject\Renderer\PostProcessor\MyProcessor
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
4. Rely on built-in `_default/list.html.twig` and `_default/page.html.twig`, or extend them in `layouts/blog/`
5. Build with `php cecil.phar build`

### Add Custom Pages

1. Create markdown files in `pages/` directory
2. Add front matter with `title` and, if needed, `layout`
3. If needed, create a template in `layouts/` (preferably extending a built-in one, with `partials/metatags.html.twig` in `<head>`)
4. Let lookup rules pick the template, or set `layout: <name>` in front matter
5. Build to generate output

### Use Dynamic Content (Twig in Page Body)

By default, Twig syntax in a Markdown body is output as is. To evaluate it, create a dedicated layout and use it only for pages that need it:

```twig
{# layouts/dynamic.html.twig #}
{% extends '_default/page.html.twig' %}

{% block content %}
{{ include(template_from_string(page.content, "dynamic content for page " ~ page.id)) }}
{% endblock content %}
```

Then set `layout: dynamic` in the page front matter. The body can use `page.*`, `site.*`, functions, filters and Twig tags.

Caveats (Markdown is converted to HTML **before** Twig rendering):

- `<`, `>` and `=>` are escaped: comparisons and arrow functions fail (`Unexpected character "&"`)
- Twig expressions inside HTML attributes (e.g. `href="{{ url(post) }}"`) are URL-encoded and not evaluated
- An expression alone on its line is wrapped in `<p>`: wrap it in an HTML element separated by line breaks
- Twig in code spans/blocks **is** evaluated: use `{% verbatim %}` to display it as is

Keep the body simple and move complex logic into a partial template or a macro. See the [dynamic content documentation](https://cecil.app/documentation/content/dynamic-content/).

### Implement Search

1. Create `pages/search.md` with front matter `layout: search` and `output: json`
2. Use JavaScript library (e.g., Lunr.js) on frontend
3. Create `layouts/search.json.twig` that iterates `site.pages.showable` and emits a JSON array of `{title, url, content}` objects
4. Add search functionality to templates

## Troubleshooting

When a user reports unexpected behavior or asks about a specific feature, ask them to run `php cecil.phar doctor` and include the output. If you are uncertain whether a feature is available in the user's Cecil version, say so explicitly and direct them to the official documentation at https://cecil.app/documentation/ rather than guessing version ranges.

### Common Issues

- **Site not generating**: Check `cecil.yml` syntax and configuration
- **Missing pages**: Ensure content files are in `pages/` directory
- **Template not loading**: Verify the `layout` front matter variable, template naming and lookup rules
- **Build errors**: Run `php cecil.phar build -vv` for verbose output
- **Cache issues**: Clear cache with `php cecil.phar cache:clear`

### Debug Output

Get detailed build information:

```bash
php cecil.phar build -v    # Verbose
php cecil.phar build -vv   # Very verbose
php cecil.phar build -vvv  # Debug
```
