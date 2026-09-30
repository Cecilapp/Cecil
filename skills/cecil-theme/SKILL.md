---
name: cecil-theme
description: Create, structure and package themes for Cecil (visual themes and component themes), following the conventions of the official Cecilapp/theme-* repositories.
license: EUPL-1.2
---

# Cecil Theme Builder

You are an expert Cecil theme developer. You create reusable themes for [Cecil](https://cecil.app): full **visual themes** (layouts, styles, assets) and small **component themes** (a feature plugged into any site or theme), packaged for Composer like the official [Cecilapp themes](https://github.com/orgs/Cecilapp/repositories?q=theme).

For general Cecil usage (content, configuration, CLI), see the `cecil` skill.

## When to Use This Skill

Use this skill when:

- Creating a new Cecil theme from scratch, or turning a site's `layouts/` into a reusable theme
- Creating a component theme (PWA, icons, redirects, headers, analytics, etc.)
- Adding theme options, translations or assets to a theme
- Packaging and publishing a theme with Composer (`cecil/theme-<name>`)
- Debugging theme resolution (layouts, assets, config or translations not picked up)

## How Cecil Loads Themes

A theme is **a website without pages**, stored in `themes/<name>/` and enabled in the site configuration:

```yaml
theme: hyde
# or, for several themes (the first one overrides the others)
theme:
  - pwa
  - hyde
```

Resolution rules (from `src/`):

| Resource       | Lookup order                                                                                                                          |
|----------------|----------------------------------------------------------------------------------------------------------------------------------------|
| Layouts        | `layouts/` → `themes/<theme>/layouts/` (in `theme` list order) → Cecil internal `resources/layouts/`                                 |
| Assets         | `assets/` → `themes/<theme>/assets/` → `static/` → `themes/<theme>/static/` (Sass import paths also include the theme's dirs) |
| Static files   | Themes' `static/` are copied first (reverse order), then site `static/`, so the site wins                                            |
| Translations   | Cecil internal → `themes/<theme>/translations/` → site `translations/` (site wins)                                                     |
| Configuration  | `themes/<theme>/config.yml` is imported with `IMPORT_PRESERVE`: it **only adds missing keys**, site config always wins               |

Consequences:

- The config file **must** be named `config.yml` (not `config.yaml`); it's the only one loaded.
- A theme is considered installed when `themes/<name>/layouts/` or `themes/<name>/config.yml` exists; otherwise the build fails with `Theme "<name>" not found. Did you forgot to install it?`.
- Any site file with the same relative path overrides the theme file (layout, partial, asset, translation key, static file).
- Theme dependencies are **not** resolved transitively: a `theme:` key inside a theme's `config.yml` is ignored as soon as the site defines `theme`. Document the full list the user must declare (e.g. `theme: [links, fontawesome]`).

## Theme Structure

### Visual theme

```plaintext
theme-<name>/
├─ .editorconfig
├─ .gitattributes          # * text=auto
├─ .gitignore              # vendor/
├─ composer.json
├─ config.yml              # default config + namespaced theme options
├─ theme.yml               # optional metadata
├─ LICENSE
├─ README.md
├─ docs/
│  └─ screenshot.png
├─ assets/                 # processed by asset(): css/, sass/, js/, images
├─ static/                 # copied as-is: favicon.ico, fonts/
├─ data/                   # optional data files (site.data.*)
├─ layouts/
│  ├─ _default/
│  │  ├─ page.html.twig    # base layout (defines blocks)
│  │  ├─ list.html.twig    # sections, homepage fallback, terms
│  │  ├─ home.html.twig    # optional
│  │  ├─ term.html.twig    # optional
│  │  └─ vocabulary.html.twig
│  ├─ <section>/           # optional per-section layouts (e.g. blog/page.html.twig)
│  ├─ partials/            # header, footer, nav, pagination, post-item...
│  └─ macros/              # optional reusable macros
└─ translations/
   └─ messages.fr.yml
```

Put layouts under `_default/`: this way a site can still provide its own top-level `page.html.twig` or `list.html.twig`, which take priority in the lookup.

### Component theme

A component theme only ships what it needs, for example:

```plaintext
theme-<name>/
├─ composer.json
├─ config.yml              # new virtual pages, output formats, options
├─ theme.yml
├─ README.md
├─ layouts/
│  ├─ _default/<layout>.<format>.twig
│  ├─ partials/<name>.html.twig   # snippet to include in <head> or <body>
│  └─ macros/<name>.twig          # macros to import
└─ translations/
```

## Step-by-Step: Create a Theme

### Step 1: Scaffold a test site

```bash
curl -LO https://cecil.app/cecil.phar
php cecil.phar new:site -n --demo
rm -rf layouts            # make sure the theme's layouts are used
mkdir -p themes/<name>
```

Develop the theme directly in `themes/<name>/` and enable it in the site `config.yml`:

```yaml
theme:
  - <name>
```

### Step 2: Write `config.yml`

Provide sensible Cecil defaults the theme relies on, plus the theme options under a **namespace named after the theme**:

```yaml
# Cecil config required by the theme
taxonomies:
  tags: tag
pages:
  pagination:
    max: 5
    path: page
static:
  exclude:
    - '*.scss'
# Theme options
<name>:
  sidebar:
    sticky: true
  color: ''       # red, orange, green, blue...
  reverse: false
```

Rules:

- Never rely on a key being set: users may override or remove it. In templates, always guard with `|default()` or `??` (e.g. `site.<name>.reverse|default(false)`).
- Since Cecil 8.37.0 there are no default vocabularies: declare `taxonomies` if the theme displays tags or categories.
- Keep generic site keys (`title`, `baseurl`, `description`…) out of the theme config.

### Step 3: Write `theme.yml` (optional metadata)

```yaml
name: <Name>
github: https://github.com/<vendor>/theme-<name>/
author:
  - name: <Author>
    url: <https://author.tld>
```

### Step 4: Create the base layout

`layouts/_default/page.html.twig` defines the HTML skeleton and **blocks** that other layouts (and users) can override:

```twig
<!DOCTYPE html>
<html lang="{{ site.language }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{- include('partials/metatags.html.twig', {page, site}, with_context = false) }}
    {%- block head_css %}
    {{ html(asset(['css/base.css', 'css/<name>.css']), {title: '<Name>'}, {preload: true}) }}
    {%- endblock head_css %}
    {{- include('partials/pwa.html.twig', {site}, with_context = false, ignore_missing = true) }}
  </head>
  <body class="{{ site.<name>.reverse|default(false) ? 'reverse' }}">
    {%- block header %}
    {{ include('partials/site-header.html.twig') }}
    {%- endblock header %}
    <main>
      {%- block content %}
      <article>
        <h1>{{ page.title }}</h1>
        {%- if page.date %}
        <time datetime="{{ page.date|date('c') }}">{{ page.date|format_date('long') }}</time>
        {%- endif %}
        {{ page.content }}
      </article>
      {%- endblock content %}
    </main>
    {%- block footer %}
    {{ include('partials/site-footer.html.twig') }}
    {%- endblock footer %}
    {%- block scripts %}{% endblock scripts %}
  </body>
</html>
```

Guidelines:

- Reuse Cecil built-in partials instead of re-implementing them: `partials/metatags.html.twig` (SEO, Open Graph, feeds, alternates), `partials/paginator.html.twig`, `partials/navigation.html.twig`, `partials/breadcrumb.html.twig`, `partials/languages.html.twig`, `partials/theme-selector.html.twig`.
- Pre-wire optional component themes with `ignore_missing = true` so the theme works whether they are installed or not.
- Pass an isolated context (`with_context = false`) to partials that only need `site`/`page`.

### Step 5: Create child layouts

Extend with a **list of templates** so a site-level override wins over the theme default:

```twig
{# layouts/blog/page.html.twig #}
{% extends ['page.html.twig', '_default/page.html.twig'] %}

{% block content %}
      <article class="post">
        <h1>{{ page.title }}</h1>
        {{ page.content }}
      </article>
      {{- include('partials/post-meta.html.twig') }}
{%- endblock content %}
```

List layout idiom (set the pages variable **before** `extends`):

```twig
{# layouts/_default/list.html.twig #}
{% set pages = site.pages.showable %}
{% if page.pages is defined %}
{% set pages = page.pages %}
{% endif %}
{% if page.pagination.pages is defined %}
{% set pages = page.pagination.pages %}
{% endif %}
{% extends ['page.html.twig', '_default/page.html.twig'] %}

{% block content %}
      <ul class="posts">
      {%- for post in pages %}
        {%- include 'partials/post-item.html.twig' with {post} only %}
      {%- endfor %}
      </ul>
      {{- include('partials/paginator.html.twig') }}
{%- endblock content %}
```

Section alias (e.g. reuse the blog list for the `section` fallback):

```twig
{% extends 'blog/list.html.twig' %}
```

Remember the lookup rules when naming layouts (see the `cecil` skill, "Lookup Rules"): `<section>/page`, `<section>/list`, `section/<section>`, `taxonomy/<plural>`, `term`, `vocabulary`, `_default/*`. For non-HTML outputs, the file name is `<layout>.<format>.twig` (e.g. `list.rss.twig`, `page.webmanifest.twig`).

### Step 6: Assets

Put processed files in `assets/` and reference them with `asset()`:

```twig
{# bundle several CSS files into one #}
{{ html(asset(['css/poole.css', 'css/syntax.css', 'css/hyde.css'])) }}
{# compile Sass and inline it #}
<style>{{ asset('sass/<name>.scss')|inline }}</style>
{# remote asset, downloaded at build time #}
{{ html(asset('https://cdn.jsdelivr.net/npm/lib/dist/lib.min.js', {filename: 'lib.min.js', minify: false}), {defer: ''}) }}
{# responsive image #}
{{ asset(post.image)|html({alt: post.title}, {responsive: true, formats: ['webp']}) }}
{# optional asset #}
{% set logo = asset(site.logo|default('logo.svg'), {ignore_missing: true}) %}
{% if not logo.missing %}{{ logo|html({alt: site.title}) }}{% endif %}
{# small inline CSS/JS #}
<style>{% apply minify_css %}.hero { color: red; }{% endapply %}</style>
```

- Use `static/` for files that must keep their name and not be processed: `favicon.ico`, web fonts, `robots.txt` overrides, etc. Link them with `url('favicon.ico')`.
- Leave minification and fingerprinting to the site config (`assets.minify`, `assets.fingerprint`) rather than forcing them in templates.
- If the theme uses Sass partials in `assets/`, exclude sources from the copy with `static.exclude: ['*.scss']`.
- For Tailwind CSS, commit the compiled file (e.g. `assets/styles.css`) and provide build scripts (see "Packaging").

### Step 7: Menus, URLs and collections

```twig
<nav>
  <ul>
  {%- for entry in site.menus.main|sort_by_weight %}
    <li{% if url(entry.url) == url(page) %} class="active"{% endif %}><a href="{{ url(entry.url) }}">{{ entry.name }}</a></li>
  {%- endfor %}
  </ul>
</nav>
```

- Always build links with `url()` (pages, menu entries, paths, assets), never hardcode `site.baseurl`.
- Filter collections: `site.pages.showable|filter_by('section', 'blog')|sort_by_date|slice(0, 5)`.
- Tags links: `url('tags/' ~ tag|slugify)` or iterate `site.taxonomies.tags`.
- Get a specific page: `site.page('about')`.

### Step 8: Translations

Wrap every UI string so the theme is translatable:

```twig
{% trans %}Read more{% endtrans %}
{% trans with {'%title%': site.title} %}%title% on GitHub{% endtrans %}
{{ 'You are offline'|trans }}
```

Provide at least one translation file, keyed by the source string:

```yaml
# translations/messages.fr.yml
"Read more": "Lire la suite"
"%title% on GitHub": "%title% sur GitHub"
```

- File names follow `messages.<locale>.<ext>` (`yml`, `yaml`, `po`/`mo`…), where `<locale>` matches the language locale or code (`fr`, `fr_FR`).
- Extract strings with `php cecil.phar util:translations:extract --locale=fr --save --theme=<name>`.

## Component Themes Patterns

No automatic injection mechanism exists: a component theme plugs into a site using one or more of these patterns.

1. **Virtual page + output format** (no template change needed), e.g. a `_redirects` file:

   ```yaml
   # config.yml
   pages:
     default:
       redirects:
         path: _redirects
         output: redirects
         multilingual: false
   output:
     formats:
       - name: redirects
         mediatype: text/plain
         extension: ""
   ```

   With the layout `layouts/_default/page.redirects.twig`. Useful keys for `pages.default.<id>`: `path`, `layout`, `output`, `uglyurl`, `multilingual`, `published`, `exclude`.

2. **Partial to include**, documented in the README and guarded by a feature toggle:

   ```twig
   {# layouts/partials/<name>.html.twig #}
   {%- if site.<name>.enabled|default(false) %}
       <link rel="manifest" href="{{ url(site.page('manifest'), {canonical: true}) }}">
   {%- endif %}
   ```

   Usage: `{{ include('partials/<name>.html.twig', {site}, with_context = false) }}`.

3. **Macros** to import:

   ```twig
   {# layouts/macros/icons.twig #}
   {%- macro svg(name, size = 24, class = '') -%}
   <svg width="{{ size }}" height="{{ size }}" class="{{ class }}">{{ site.data.icons[name]|raw }}</svg>
   {%- endmacro -%}
   ```

   Usage: `{% import 'macros/icons.twig' as icons %}{{ icons.svg('github', 16) }}`.

4. **Section-specific layouts** applied automatically when the site has a matching section (e.g. `layouts/episodes/list.rss.twig` for a podcast).

5. **Front matter blocks**: a visual theme can render page blocks dynamically:

   ```twig
   {% for block in page.blocks|default([]) %}
     {{ include('partials/blocks/' ~ block.name ~ '.html.twig', {block}, ignore_missing = true) }}
   {% endfor %}
   ```

## Packaging

### composer.json

Themes are installed with Composer thanks to `cecil/theme-installer`, which copies the package to `themes/<extra.name>` (`extra.name` is **required**):

```json
{
  "name": "cecil/theme-<name>",
  "type": "cecil-theme",
  "description": "Cecil <Name> theme",
  "keywords": ["Cecil", "theme", "<name>"],
  "license": "MIT",
  "require": {
    "cecil/theme-installer": "^1.4||^2.0"
  },
  "extra": {
    "name": "<name>"
  },
  "minimum-stability": "dev",
  "prefer-stable": true,
  "config": {
    "allow-plugins": {
      "cecil/theme-installer": true
    }
  }
}
```

- Use `"description": "Cecil component theme <Name>"` for a component theme.
- Declare dependent themes in `require` (e.g. `"cecil/theme-fontawesome": "^1.5"`) **and** tell the user to add them to `theme`.
- Tailwind themes can add a builder:

  ```json
  "require-dev": { "aligny/tailwind-builder": "^1.0" },
  "scripts": {
    "css:build": "tailwind-builder ./assets/css/tailwind.css --output=./assets/styles.css --minify",
    "css:watch": "tailwind-builder ./assets/css/tailwind.css --output=./assets/styles.css --watch"
  }
  ```

### .editorconfig

```ini
root = true

[*]
indent_style = space
indent_size = 2
end_of_line = lf
charset = utf-8
trim_trailing_whitespace = true
insert_final_newline = true

[*.twig]
insert_final_newline = false
```

### README.md

Follow this outline:

````markdown
# <Name> theme

The _<Name>_ theme for [Cecil](https://cecil.app) is …

![Demo screenshot](docs/screenshot.png)

## Features

## Installation

```bash
composer require cecil/theme-<name>
```

> Or [download the latest archive](https://github.com/<vendor>/theme-<name>/releases/latest/) and uncompress its content in `themes/<name>`.

## Usage

Add `<name>` in the `theme` section of your `config.yml`:

```yaml
theme:
  - <name>
```

### Configuration

(namespaced options with their default values)

### Internationalization

## License

_<Name>_ is a free software distributed under the terms of the MIT license.
````

For a component theme, also document the line to include in layouts (partial or macro import).

### Demo on GitHub Pages (optional)

A CI workflow can build a demo: download `cecil.phar`, run `php cecil.phar new:site -n --demo`, check out the theme in `./themes/<name>`, append `theme: [<name>]` to `config.yml`, remove `./layouts`, then `php cecil.phar build -v` with `CECIL_BASEURL` set, and deploy `_site/`.

## Conventions Checklist

- [ ] `config.yml` (not `.yaml`) with theme options under a `<name>:` namespace
- [ ] Layouts in `layouts/_default/`, children extend `['page.html.twig', '_default/page.html.twig']`
- [ ] Built-in partials reused (`metatags`, `paginator`, `navigation`…)
- [ ] All links built with `url()`, all processed files with `asset()`
- [ ] Every option guarded with `|default()` or `??`
- [ ] Every UI string wrapped in `trans`, at least one `translations/messages.<locale>.yml`
- [ ] 2-space indentation in Twig/YAML/JSON/CSS, LF line endings
- [ ] **Twig files do not end with a trailing newline**
- [ ] `composer.json` with `"type": "cecil-theme"` and `extra.name`
- [ ] README with screenshot, install, usage and configuration sections
- [ ] Tested on a demo site with `php cecil.phar build -v` and `php cecil.phar serve`, without a site `layouts/` directory

## Troubleshooting

- **`Theme "<name>" not found`**: the folder must be `themes/<name>/` (matching `extra.name`) and contain `layouts/` or `config.yml`.
- **Theme layout ignored**: a site `layouts/` file with the same path (or a higher-priority lookup name) wins; check with `php cecil.phar build -vv`, or remove the site layout.
- **Theme option ignored**: the site config defines the same key; theme config never overrides site config.
- **Dependent theme not loaded**: list it explicitly in the site `theme` key.
- **Asset not found**: paths in `asset()` are relative to `assets/` (or `static/`) of the site or theme, without the directory prefix.
- **Translations missing**: check the file name locale matches `languages[].locale` or code, then run `php cecil.phar cache:clear`.
- Run `php cecil.phar doctor` to check theme configuration.

## Useful Resources

- Official themes: https://github.com/orgs/Cecilapp/repositories?q=theme
- Themes showcase: https://cecil.app/themes/
- Templates documentation: https://cecil.app/documentation/templates/
- Configuration (`theme`): https://cecil.app/documentation/configuration/#theme
- Minimal skeleton: https://github.com/Cecilapp/theme-example
