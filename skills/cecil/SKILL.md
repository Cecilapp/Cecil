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

## Reference Files

This file covers what most tasks need. Read the matching reference **before** working on a topic it covers:

| Read                                                   | Before                                                                                                       |
|--------------------------------------------------------|--------------------------------------------------------------------------------------------------------------|
| [references/content.md](references/content.md)         | Writing page content: Markdown syntax (links, images, embeds, notes, table of contents, excerpt), menus and taxonomies in front matter, permalinks (`pages.paths`) |
| [references/configuration.md](references/configuration.md) | Editing `cecil.yml`: menus, taxonomies, dates, environment variables and `.env`, default pages, data and static files, output formats, cache, server headers, optimization |
| [references/templates.md](references/templates.md)     | Creating or editing a Twig template: lookup rules, built-in templates and partials, metatags, variables (page, site, taxonomies, pagination), functions, filters, components, fragments cache |
| [references/multilingual.md](references/multilingual.md) | Adding languages, translating pages, translating templates UI strings (`trans`, `translations/` files) or localizing dates |
| [references/assets.md](references/assets.md)           | Handling assets in templates (`asset()`, `html()`, images, resize, responsive, CDN), Sass, code highlighting, or optimizing the build |
| [references/ai.md](references/ai.md)                   | Making the site AI-friendly: `llms.txt`, Markdown output of pages, HTTP headers, blocking AI crawlers (`robots.txt`) |
| [references/extending.md](references/extending.md)     | Writing PHP extensions (custom generators, Twig extensions, post-processors, build events subscribers, plugins) or using Cecil as a PHP library |
| [references/deployment.md](references/deployment.md)   | Deploying the generated site (GitHub Pages, GitLab Pages, Netlify, Vercel, Cloudflare Pages, etc.)           |
| [references/workflows.md](references/workflows.md)     | Creating a blog, custom pages, dynamic content (Twig in a page body), shortcodes or a search page            |

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
├── translations/ # UI strings translations (messages.<locale>.yaml)
├── extensions/ # Custom PHP classes (generators, Twig extensions, post-processors, event subscribers)
├── .cache/    # Build cache (generated, `cache.dir`)
└── _site/     # Generated site (`output.dir` or `--output`)
```

Add `.cache/` and `_site/` to the site `.gitignore`.

### Key Directories

- **pages/** - Markdown content files organized into sections
- **layouts/** - Twig templates and partials
- **assets/** - Files handled by Cecil (Sass compilation, minification, image handling), published **only when used** with `asset()` in a template (or as an image in Markdown): a file linked directly (`url()` or a raw path) must be in `static/`
- **static/** - Files copied to output without transformation (`static.mounts` copies specific files or directories of `static/` to a chosen output path)
- **data/** - Data files (YAML, JSON, XML, CSV) exposed in templates via `site.data` (`data/authors.yml` → `site.data.authors`)
- **translations/** - Translation files of the templates UI strings (`messages.<locale>.<ext>`, used by the `trans` tag/filter)
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
  - Generators are indexed by name and ordered by priority; lower numbers execute first (e.g., `DefaultPages` at priority 10 runs before `Alias` at priority 80).
  - DefaultPages (10) → ExternalBody (30) → Section (40) → Taxonomy (50) → Homepage (60) → Pagination (70) → Alias (80) → Redirect (90)

- **Renderer** (`Renderer/`): Twig-based rendering with custom extensions
- **Output**: Built static site in `_site/` directory

### Content Model

- **Pages**: Markdown files (`.md`, also `.markdown`, `.mdown`, `.mkdn`, `.mkd`, `.text`, `.txt`: `pages.ext`) composed of front matter and body (see [references/content.md](references/content.md) for the Markdown syntax)
- **Front matter**: Metadata surrounded by separators (`---`, `+++`, or `<!-- -->`)
- **Section**: Root folder in `pages/` (e.g. `pages/blog/post-1.md` -> section `blog`); its `index.md` (or `README.md`, handled as `index.md`) holds the section front matter
- **Homepage**: `pages/index.md`; it accepts `sortby` and `pagination` like a section, and `pagesfrom: blog` to list the pages of the `blog` section instead of all pages
- **File-based routing**: Files under `pages/` define generated paths, slugified (`pages/My Projects/Project A.md` → `/my-projects/project-a/`, rendered as `my-projects/project-a/index.html`); permalinks per section are set with `pages.paths` (see [references/content.md](references/content.md#permalinks))
- **Page ID**: the slugified path without extension and language (e.g. `blog/post-1`), used by `url()`, `site.page()`, `[link](page:blog/post-1)` and menus
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

- Is a full _Section_ (same `type`, variables, and [layout](references/templates.md#lookup-rules-how-cecil-chooses-a-template) resolution) available at its own URL (e.g. `/blog/2024/`)
- Falls back to the templates of its parent sections (e.g. `blog/list.html.twig` for `blog/2024` if `blog/2024/list.html.twig` doesn't exist)
- Can be nested at any depth (e.g. `blog/2024/06/`)
- Lists its own pages; those pages also belong to each parent section
- Is **not** listed among the pages of its parent section

Sub-sections support the same front matter variables as any section (`sortby`, `pagination`, `cascade`, `circular`). Use `cascade` on a parent `index.md` to propagate variables down to sub-sections and their pages.

In templates, use `page.parent`, `page.ancestors`, `page.sections` and `page.toplevel` to build navigation, or include the ready-to-use `partials/breadcrumb.html.twig` partial.

Previous/next navigation (`page.prev` / `page.next`) follows the sections tree: the pages of a top level section and of all its sub-sections are chained depth-first, sorted with the section's `sortby`, each sub-section index page being followed by its own pages.

### Front Matter

Front matter is YAML by default (`pages.frontmatter` also accepts `ini`, `toml` and `json`). Common variables:

| Variable                     | Purpose                                                                                                   |
|------------------------------|-----------------------------------------------------------------------------------------------------------|
| `title`, `description`       | Title (default: file name without prefix and language suffix) and description                            |
| `date`, `updated`            | Creation date (default: file modification date) and update date (no default; `lastmod` is an alias)       |
| `layout`                     | Template to use (see [Lookup Rules](references/templates.md#lookup-rules-how-cecil-chooses-a-template))                         |
| `slug`, `path`               | Override the URL (values must already be slugified)                                                       |
| `weight`                     | Sort order                                                                                                |
| `published` / `draft`        | `draft: true` is the same as `published: false` (preview with `build --drafts` / `serve --drafts`)        |
| `schedule`                   | `{publish: <date>, expiry: <date>}`: published if `publish` is reached **and** `expiry` is not passed     |
| `menu`                       | Add the page to a menu (e.g. `menu: main`, `menu: [main, footer]` or `menu: {main: {weight: 10}, footer: {name: "Short"}}`; entry name defaults to `title`) |
| `<vocabulary>`               | Taxonomy terms, with the plural key declared in `taxonomies` (e.g. `tags: [PHP, Twig]` → `/tags/` and `/tags/php/` pages) |
| `redirect`, `alias`          | Redirect the page to a URL; generate redirections from old paths to the page (e.g. `alias: [contact]`)    |
| `external`                   | URL of a remote file used as the page body (e.g. a GitHub `README.md`)                                    |
| `output`                     | Output format(s) of the page (e.g. `[html, json]`)                                                        |
| `uglyurl`                    | `true` to output `page.html` instead of `page/index.html`                                                 |
| `excluded`                   | `true` to hide the page from lists, sitemap and feeds (formerly `exclude`)                                |
| `language`, `langref`        | Language code (default: file suffix) and translations reference                                          |

Every predefined variable can be overridden, except `section` (derived from the folder). Any other variable is a custom one, available in templates as `page.<variable>`.

A file name prefix sets the `date` (`2019-04-23_My post.md`) or the `weight` (`1_First project.md`) of the page; separators are `-` and `_` by default (`pages.prefix.separator`).

A section (`<section>/index.md`) also accepts:

- `sortby`: `date` (default), `updated`, `title` or `weight`, or a map `{variable: date, desc_title: false, reverse: false}` (`desc_title` sorts pages with the same date by descending title); the site default is `pages.sortby`
- `pagination`: `{max: 5, path: page}` to override `pages.pagination`, `false` to disable it, or `{enabled: true}` to enable it when it is disabled globally (`pages.pagination: false`)
- `cascade` (variables propagated to the section pages, without overriding the variables they already set) and `circular` (circular prev/next navigation; with sub-sections, only the top-level section value applies)

### Configuration

Configuration is defined in `cecil.yml` or `config.yml` at project root (read [references/configuration.md](references/configuration.md) for the options structure):

- Core options are top-level keys such as `title`, `baseline`, `baseurl` (must end with `/`), `description`, `author`, `social`, `taxonomies`, `menus`, `date`, `theme`
- Dot notation in templates applies to `site` variable access (for example `site.title`)
- Defaults (including generators, default pages and output formats) are defined in `config/default.php`; priority is defaults < themes `config.yml` (the first theme wins) < site config (plus `--config` files) < env vars (`CECIL_*`, also read from a `.env` file) and CLI options (`--baseurl`, `--output`, `--drafts`…)
- Maps are merged key by key with the defaults; a boolean applied to a map toggles its `enabled` key (e.g. `cache: false`); in templates, check it with the `enabled` test (`{% if config.cache is enabled %}`)
- A list of values (`pages.ext`, `output.pagetypeformats.<type>`, `assets.images.responsive.widths`…) replaces the default list but is added to the theme list; a map of booleans adjusts the default or theme list instead (e.g. `homepage: {rss: true, atom: false}`)
- `output.formats` entries are merged by `name`: override a default format with only its `name` and the properties to change
- Deprecated options (renamed, moved or removed, listed in `config/deprecated.php`) make the build fail, with a message giving the source (site, theme or env vars) and the new YAML snippet to use
- Pages without content file (front matter only) are declared in `pages.default`, indexed by ID (`pages.virtual` is removed); like any page they are listed (`showable`, sitemap, homepage) unless they set `excluded: true`. The built-in default pages (`index`, `404`, `robots`, `sitemap`, `xsl/*`) are declared there too: disable one with `pages: {default: {sitemap: {published: false}}}` rather than creating a duplicate
- A page is "generated" (`page.generated`, formerly `page.virtual`) when it has no content file (section, term, page declared in configuration, etc.) or is a paginated page

## Building a Cecil Site

### Step 1: Download Cecil

Download Cecil using curl:

```bash
curl -LO https://cecil.app/cecil.phar
chmod +x cecil.phar
```

Requirements: PHP 8.3+ with the `fileinfo`, `gd` and `mbstring` extensions (optional: `intl` to localize dates in a locale other than `en`, `curl` for `doctor:links` external checks, `imagick` for better image processing). If the CLI reports a PHP version or extension error, check `php -v` and `php --ini` (the CLI may use another `php.ini`).

If PHP is not available, use the standalone binary for the platform instead (`cecil-linux-x64`, `cecil-linux-arm64`, `cecil-macos-x64`, `cecil-macos-arm64`, `cecil-windows-x64.exe`), and replace `php cecil.phar` with `./cecil` in the commands below. The binary has no `self-update` command, only supports GD for image processing, and its `serve` command requires a local PHP executable (for the PHP built-in web server).

```bash
curl -Lo cecil https://github.com/Cecilapp/Cecil/releases/latest/download/cecil-linux-x64 && chmod +x cecil
xattr -d com.apple.quarantine cecil # macOS only
```

If Cecil is already installed globally (Homebrew `brew install cecilapp/tap/cecil`, Scoop, PHIVE or `composer global require cecil/cecil`), run `cecil <command>` instead of `php cecil.phar <command>` (check with `cecil --version`); it is updated with the same tool, not with `self-update`.

### Step 2: Create a New Site

Use the `new:site` command to scaffold a new website. It asks questions (title, baseline, base URL, author, language, GitHub Pages workflow, etc.) and creates `cecil.yml`, `pages/index.md`, a `README.md` and a `.gitignore`: in a non-interactive shell, add `-n` to use the defaults, then edit `cecil.yml`:

```bash
php cecil.phar new:site -n          # `--demo` adds demo content, `--force` overrides an existing site
php cecil.phar new:site my-site -n  # in the `my-site` directory
```

Starter kits are an alternative for a full site (`Cecilapp/the-butler` blog, `Cecilapp/statidocs` documentation, etc.): see https://cecil.app/starter-kits/.

### Step 3: Configure the Site

Edit **cecil.yml**:

```yaml
title: My Site
baseline: A short tagline   # shown after the title in the homepage <title>
baseurl: https://example.com/ # must end with /
description: My awesome static site
taxonomies:
  categories: category
  tags: tag
```

### Step 4: Create Content

Create a page with (`-n` and `--name` in a non-interactive shell):

```bash
php cecil.phar new:page --name=blog/my-post.md -n  # `--prefix` adds a date prefix, `--slugify` slugifies the name
```

`new:page` uses a page model when it exists: `models/<section>.md` (e.g. `models/blog.md` for pages in `blog/`) or `models/default.md`, with the `%title%` and `%date%` placeholders. Pages can also be created directly as files in `pages/`.

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

| Command                                    | Purpose                                                                                                     |
|--------------------------------------------|-------------------------------------------------------------------------------------------------------------|
| `php cecil.phar new:site`                  | Create a new website (`-n` non-interactive, `--demo` to add demo content, `--force`)                        |
| `php cecil.phar new:page`                  | Create a new page (`--name`, `--prefix`, `--slugify`, `--force`, `--open`, `--editor`)                      |
| `php cecil.phar new:extension`             | Create an extension skeleton (`--type`: generator, twig, postprocessor, subscriber or plugin; `--name`, `--namespace`, `--force`) |
| `php cecil.phar build`                     | Build the static site (`--drafts`, `--optimize` / `--no-optimize`, `--baseurl`, `--output`, `--config`, `--page`, `--dry-run`, `--clear-cache[=<regex>]`, `--metrics`, `--show-pages`, `--render-subset`, `--notify`) |
| `php cecil.phar serve`                     | Start local server on `http://localhost:8000` with live reload (`--drafts`, `--optimize`, `--config`, `--page`, `--clear-cache`, `--metrics`, `--open`/`-o`, `--host`, `--port`, `--no-watch`, `--timeout`, `--notify`, `--no-ignore-vcs`); no `--baseurl`, `--output` or `--dry-run` |
| `php cecil.phar serve --incremental`       | Serve with incremental builds (rebuild only changed pages)                                                  |
| `php cecil.phar serve --background`        | Run the server in the background, **without** watching changes (`serve:log -l 100` shows `.cecil/server.log` and `.cecil/errors.log`) |
| `php cecil.phar serve:stop`                | Stop the local server                                                                                       |
| `php cecil.phar edit`                      | Open pages directory with the configured editor (`--editor` or `editor` config key)                         |
| `php cecil.phar show:config`               | Display effective configuration (`--key=pages.dir` to show a single key)                                    |
| `php cecil.phar show:content`              | Display content tree (pages, data, static files)                                                            |
| `php cecil.phar doctor`                    | Diagnose site and environment (see also `doctor:frontmatter`, `doctor:seo`, `doctor:links`, `doctor:cache`) |
| `php cecil.phar cache:clear`               | Clear all cache files (or only `cache:clear:assets`, `cache:clear:templates` (`--fragments`), `cache:clear:translations`) |
| `php cecil.phar clear`                     | Remove generated files (or only `clear:output`, `clear:temporary` / `clear:tmp`)                            |
| `php cecil.phar util:templates:extract`    | Extract built-in templates into `layouts/` (Phar and standalone binary only)                                |
| `php cecil.phar util:translations:extract` | Extract translation strings from templates                                                                  |
| `php cecil.phar about`                     | Display Cecil version and environment information                                                           |
| `php cecil.phar self-update`               | Update Cecil to the latest version (Phar only; `--stable`, `--preview`, `--rollback`)                       |

- Every command accepts the site directory as argument (e.g. `php cecil.phar build path/to/site`); `php cecil.phar help <command>` lists its options.
- `--config=config/dev.yml[,other.yml]` **adds** configuration files, merged over `cecil.yml` from left to right: it does not replace it.
- `build --metrics` shows the duration and memory of each step (to profile a slow build); `--clear-cache=<regex>` only clears the matching cache keys.
- `serve` is for local preview only: never use it to host the site, deploy the `_site/` output.
- `doctor:links` and `doctor:seo` return a failure exit code according to `--fail-on`: `bad` (default), `ok` (redirections, missing trailing slash), `feedback` or `never`. `doctor:links` also accepts `--page`, `--exclude`, `--format=json`, `--include-generated`, `--timeout`, `--concurrency`, `--no-cache` and `--feedback`; its defaults can be set under the `doctor.links` configuration key. External links checks need the `curl` PHP extension, and successful results are cached for 24 hours.
- `doctor:seo` runs a dry-run build and checks the generated pages; thresholds and checks are set under `doctor.seo` (e.g. `{title: {min: 30, max: 60}, description: {min: 120, max: 160}, content: {min_words: 300}}`, and `checks` booleans: `title`, `description`, `canonical`, `h1`, `og_tags`, `img_alt`, `content_length`, `lang_attribute`).

## Templates

Twig templates live in `layouts/` and are named `layouts/(<section>/)<type>|<layout>.<format>(.<language>).twig` (e.g. `layouts/page.html.twig`, `layouts/blog/list.html.twig`). For each page, Cecil picks the first existing template in a lookup order, searching the site `layouts/`, then the theme, then the built-in templates. Read [references/templates.md](references/templates.md) for the full lookup rules and helpers.

### Built-in Templates

Cecil embeds default templates in [`resources/layouts/`](https://github.com/Cecilapp/Cecil/tree/main/resources/layouts) (`_default/` layouts, `partials/`, feeds, sitemap, robots.txt, 404, redirects, JSON outputs, etc.). They are always available to Twig, so they can be rendered, included or extended **without being copied** into `layouts/`.

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
5. **Always use `partials/metatags.html.twig`** in the `<head>` of HTML layouts (already included by `_default/page.html.twig`): it generates `<title>`, description, canonical, Open Graph, Twitter/X Card, favicons, feeds and `hreflang` links, etc. **Never add a separate `<title>`, `<meta name="description">`, canonical or `og:*` tags** next to it: they would be duplicated.
6. **Make UI strings translatable** on multilingual sites: wrap them with `{% trans %}…{% endtrans %}` or `|trans` and provide `translations/messages.<locale>.yaml` files (see [references/multilingual.md](references/multilingual.md#ui-strings-translation)).
7. **Build URLs with `url()`** (pages, page IDs, menu entries, assets), never by concatenating `site.baseurl`: `<a href="{{ url(page) }}">`, `{{ url('blog/post-1') }}`.
8. **Escape text in attributes yourself**: Twig autoescape is **disabled** by default (`layouts.autoescape: false`), so `{{ page.content }}` needs no `|raw`, but a value printed in an attribute must be escaped (`title="{{ page.title|e('html_attr') }}"`).

## Useful Resources

- **Official website**: https://cecil.app
- **GitHub Repository**: https://github.com/Cecilapp/Cecil
- **Issue Tracker**: https://github.com/Cecilapp/Cecil/issues
- **Documentation**: https://cecil.app/documentation/

## Troubleshooting

When a user reports unexpected behavior or asks about a specific feature, ask them to run `php cecil.phar doctor` and include the output. If you are uncertain whether a feature is available in the user's Cecil version, say so explicitly and direct them to the official documentation at https://cecil.app/documentation/ rather than guessing version ranges.

### Common Issues

- **Site not generating**: Check `cecil.yml` syntax and configuration
- **`Deprecated configuration option(s)`**: move or remove the listed options as shown in the error message
- **Missing pages**: Ensure content files are in `pages/` directory
- **Template not loading**: Verify the `layout` front matter variable, template naming and lookup rules
- **Build errors**: Run `php cecil.phar build -vv` for verbose output
- **Custom extension ignored**: a Twig extension or post-processor class that can't be loaded only logs an error (`Unable to add Twig extension`, `Unable to load output post processor`), the build doesn't fail: check the `-v` output
- **Cache issues**: Clear cache with `php cecil.phar cache:clear`; a **new** translation file is not detected (`cache:clear:translations`), and remote assets are cached for 7 days (`cache.assets.remote.ttl`); while developing templates, `cache: {templates: false}` disables the templates cache
- **Broken links or anchors**: Run `php cecil.phar doctor:links` (internal links) or `php cecil.phar doctor:links --internal --external`

### Debug Output

Get detailed build information:

```bash
php cecil.phar build -v    # Verbose
php cecil.phar build -vv   # Very verbose
php cecil.phar build -vvv  # Debug
```

`debug: true` in the configuration (or `CECIL_DEBUG=true`) enables the Twig `dump()` / `d()` functions and the Sass source maps.
