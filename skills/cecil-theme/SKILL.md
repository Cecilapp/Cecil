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
- Creating a component theme (search, PWA, dark mode toggle, icons, redirects, headers, analytics, etc.)
- Installing or integrating an official component theme (e.g. `flexsearch`, `pwa`, `darkmodetoggle`) into a site or a visual theme; ask the user first (see "Ask the user before installing")
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
| Translations   | Cecil internal → `themes/<theme>/translations/` (in `theme` list order, so the **last** theme wins between themes) → site `translations/` (site wins) |
| Data files     | Site `data/` and each `themes/<theme>/data/` are loaded into `site.data`                                                              |
| Configuration  | `themes/<theme>/config.yml` is imported with `IMPORT_PRESERVE`: it **only adds missing keys**, site config always wins (first theme wins between themes) |

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

A component theme only ships what it needs, for example (structure of `theme-flexsearch`):

```plaintext
theme-<name>/
├─ .github/workflows/demo.yml     # builds demo/ and deploys it to GitHub Pages
├─ .gitattributes                 # export-ignore for .*, /demo, /docs
├─ composer.json
├─ config.yml                     # new virtual pages, output formats, options
├─ theme.yml
├─ README.md
├─ assets/<name>/                 # namespaced assets (e.g. flexsearch/flexsearch.css)
├─ demo/                          # demo website using the theme
│  ├─ config.yml
│  ├─ layouts/_default/page.html.twig
│  ├─ pages/
│  └─ link.php                    # links the repository as demo/themes/<name>
├─ docs/screenshot.png
├─ layouts/
│  ├─ _default/<layout>.<format>.twig
│  ├─ partials/<name>.html.twig   # snippet to include in <head> or <body>
│  ├─ partials/<name>/*.html.twig # optional sub-partials (e.g. trigger + dialog)
│  └─ macros/<name>.twig          # macros to import
└─ translations/messages.fr.yml
```

Put a component theme's assets in an `assets/<name>/` subfolder so they don't collide with the site's or another theme's files.

## Step-by-Step: Create a Theme

Before scaffolding, ask the user which features the theme needs and whether official component themes should provide some of them (search, dark mode, PWA, icons…), as described in "Ask the user before installing". In the same message, confirm the use of the built-in metatags partial (see Step 4).

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
- Cecil has no default vocabularies: declare `taxonomies` if the theme displays tags or categories.
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

- **Always include `partials/metatags.html.twig`** in the `<head>` of every HTML base layout, and never hand-write `<title>`, description, canonical, Open Graph or Twitter tags next to it (they would be duplicated). Since it replaces the theme's own `<title>`, confirm with the user first, explaining the benefit in one line, e.g.: _"I'll use Cecil's built-in metatags partial: it generates the title, description, canonical, favicons, Open Graph/Twitter cards, feeds, hreflang alternates (and optional JSON-LD) from front matter and config, with no code to maintain. OK?"_ Only skip it if the user explicitly declines; in that case, document in the README which tags the theme outputs.
- Reuse the other Cecil built-in partials instead of re-implementing them: `partials/paginator.html.twig`, `partials/navigation.html.twig`, `partials/breadcrumb.html.twig`, `partials/languages.html.twig`, `partials/theme-selector.html.twig`.
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

   For a technical virtual page (JSON index, manifest, script…), set `exclude: true` to keep it out of lists, and `serviceworker: {precache: false}` so the `pwa` theme does not precache it:

   ```yaml
   pages:
     default:
       flexsearch:
         path: flexsearch
         layout: flexsearch      # layouts/_default/flexsearch.json.twig
         output: json
         exclude: true
         serviceworker:
           precache: false
   ```

   Virtual pages are multilingual by default (one file per language, e.g. `/flexsearch.json` and `/fr/flexsearch.json`); set `multilingual: false` for a single file.

2. **Partial to include**, documented in the README and guarded by a feature toggle:

   ```twig
   {# layouts/partials/<name>.html.twig #}
   {%- if site.<name>.enabled|default(false) %}
       <link rel="manifest" href="{{ url(site.page('manifest'), {canonical: true}) }}">
   {%- endif %}
   ```

   Usage: `{{ include('partials/<name>.html.twig', {site}, with_context = false) }}`.

   When a component needs code in several places, ship one partial per location (e.g. `partials/darkmodetoggle-head.html.twig` in `<head>` + `partials/darkmodetoggle.html.twig` for the button), and accept optional variables (`{class: 'ml-2', label: 'Night mode'}`).

   Make the component easy to restyle and to hook into:

   - expose colors and sizes as CSS custom properties prefixed with the theme name (`--flexsearch-accent`, `--darkmodetoggle-size`), with light and dark defaults
   - prefix every CSS class with the theme name (`.flexsearch-*`, `.darkmodetoggle`)
   - offer `data-<name>-*` attributes as JavaScript hooks (e.g. `data-flexsearch-open`), and a small global API or custom event when relevant
   - respect Cecil's color scheme conventions: `data-theme` attribute and/or `dark` class on `<html>`, falling back to `prefers-color-scheme`
   - prefer vanilla JavaScript without dependencies; lazy-load heavy libraries and data on first use

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

## Official Component Themes

Before writing a feature, check whether an official component theme already provides it. They are all installed with `composer require cecil/theme-<name>` (or by uncompressing the release archive in `themes/<name>/`), enabled in `theme`, then wired into layouts by including their partials.

| Theme            | Feature                                                                       |
|------------------|-------------------------------------------------------------------------------|
| `flexsearch`     | Client-side full-text search: index generated at build time, DocSearch-like modal |
| `pwa`            | Web manifest, service worker, offline page (Progressive Web App)              |
| `darkmodetoggle` | Icon button to switch between light and dark color schemes                    |
| `docsearch`      | Algolia DocSearch                                                             |
| `fontawesome`    | Font Awesome icons helpers                                                    |
| `octicons`       | Twig macro for Primer Octicons icons                                          |
| `podcast`        | Templates to publish an audio show (RSS feed)                                 |
| `redirects`      | `_redirects` file generation                                                  |
| `headers`        | `_headers` file generation                                                    |
| `netlify`        | Netlify's `_redirects` and `_headers`                                         |
| `netlifycms`     | Netlify CMS support                                                           |

### Ask the user before installing

Installing a component theme adds a Composer dependency, entries in `theme` and includes in layouts, so **ask the user** instead of deciding alone. Ask when:

- creating a visual theme or a site, and a feature could come from a component theme (search, dark mode, PWA, icons…): offer to install it, to pre-wire it with `ignore_missing = true` (optional for the theme's users), or to skip it
- the user requests a feature already covered by a component theme: offer to install it rather than re-implement it
- a feature has several options (e.g. `flexsearch` vs `docsearch`, `darkmodetoggle` vs the built-in `theme-selector`): present the choice with the trade-offs

Suggested questions:

- "Should the theme use Cecil's built-in metatags partial for all SEO/social tags (recommended)?" (see Step 4)
- "Do you want a search box? FlexSearch (client-side, no service) or Algolia DocSearch?"
- "Do you want a light/dark mode toggle (`darkmodetoggle`)?"
- "Should the site work offline and be installable as an app (`pwa`)?"
- "Should these components be required by the theme, or only optional (included if installed)?"

Group the questions in a single message, recommend a default for each, and don't ask again for components already declared in `theme` or explicitly declined. Once answered, install (`composer require cecil/theme-<name>`), add the theme to `theme`, and include its partials and assets as documented below.

### Combining themes

Several component themes can be combined, together with a visual theme:

```yaml
theme:
  - flexsearch
  - darkmodetoggle
  - pwa
  - hyde
```

### FlexSearch (`flexsearch`)

Adds a search box powered by [FlexSearch](https://github.com/nextapps-de/flexsearch). The index is a static JSON file generated at build time (`/flexsearch.json`, one per language), split into one record per `<h2>`/`<h3>` heading, with results grouped by section. `Ctrl`/`⌘` + `K` opens the modal.

```twig
{# in <head> #}
{{ html(asset('flexsearch/flexsearch.css')) }}
{# where the button should be displayed (trigger + modal) #}
{{ include('partials/flexsearch.html.twig') }}
{# or separately: trigger in the header, dialog before </body> #}
{{ include('partials/flexsearch/trigger.html.twig') }}
{{ include('partials/flexsearch/dialog.html.twig') }}
```

Any element with a `data-flexsearch-open` attribute opens the modal. Main options:

```yaml
flexsearch:
  enabled: true
  trigger: auto        # auto (icon only on mobile), icon or full
  hotkey: k            # false to disable
  sections:            # indexed sections, in the order of result groups (all root sections by default)
    docs:
      limit: 5
    blog:
      title: Posts
      limit: 3
      split: false     # one record per page instead of one per heading
      date: true
```

Pages with `exclude: true` are not indexed. Styles are overridable with `--flexsearch-*` custom properties.

### PWA (`pwa`)

Generates `/manifest.webmanifest`, `/serviceworker.js` and `/offline.html`. Include in `<head>`:

```twig
{{ include('partials/pwa.html.twig', {site}, with_context = false) }}
```

The service worker is **disabled by default**:

```yaml
manifest:
  background_color: '#FFFFFF'
  theme_color: '#202020'
  theme_color_dark: '#000000'   # optional
  shortcuts: true               # main menu entries as app shortcuts
serviceworker:
  enabled: true
  install:
    prompt: false
    button: '#install-button'   # custom install button (hidden by default)
    precache:
      pages:
        limit: 10
  update:
    snackbar: true
  offline:
    snackbar: true
```

- Icons are generated from the site `assets/icon.png` if `manifest.icons` is not set.
- Exclude a page from precaching with front matter `serviceworker: {precache: false}`; other component themes should do the same for their technical virtual pages.
- Disabling the service worker afterwards unregisters it from visitors' browsers and clears their caches.

### Dark mode toggle (`darkmodetoggle`)

Adds an icon button (moon/sun) that follows the system preference, remembers the user choice and avoids a flash on load.

```twig
{# in <head>, as early as possible #}
{{ include('partials/darkmodetoggle-head.html.twig', {site}, with_context = false) }}
{# where the button should be displayed (can be included several times) #}
{{ include('partials/darkmodetoggle.html.twig') }}
```

In dark mode, `<html>` gets `class="dark" data-theme="dark" style="color-scheme: dark;"`, so it works with Tailwind CSS (`dark:` variant), Bootstrap/plain CSS (`[data-theme="dark"]`) and Pico CSS. Options:

```yaml
darkmodetoggle:
  storage_key: theme   # localStorage key
  class: dark          # class added to <html> in dark mode (empty to disable)
  style: true          # minimal button style
```

JavaScript API: `window.colorScheme.current()`, `.set('dark')`, `.toggle()`, and the `colorschemechange` event. Any element with `data-darkmodetoggle` toggles the scheme.

> Cecil's built-in `partials/theme-selector.html.twig` is a basic toggle that only sets `data-theme` (inline styles, no options, single instance per page). Prefer `darkmodetoggle` for a configurable, accessible button that also sets the `dark` class and `color-scheme`, and exposes a JavaScript API.

### Pre-wiring component themes in a visual theme

A visual theme can include component partials with `ignore_missing = true`, so they are rendered only when the user installs the component theme:

```twig
<head>
  {{- include('partials/darkmodetoggle-head.html.twig', {site}, with_context = false, ignore_missing = true) }}
  {{- include('partials/pwa.html.twig', {site}, with_context = false, ignore_missing = true) }}
</head>
<header>
  {{- include('partials/flexsearch.html.twig', ignore_missing = true) }}
  {{- include('partials/darkmodetoggle.html.twig', ignore_missing = true) }}
</header>
```

Assets referenced by a component (e.g. `flexsearch/flexsearch.css`) must be guarded too, for example with `asset('flexsearch/flexsearch.css', {ignore_missing: true})` and a `missing` check.

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
- Add a branch alias for the development branch, e.g. `"extra": {"name": "<name>", "branch-alias": {"dev-main": "1.x-dev"}}`.
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

### .gitattributes

Keep development files out of the release archive:

```ini
# Files to exclude when creating archive
.* export-ignore
/demo export-ignore
/docs export-ignore

# Auto detect text files and perform LF normalization
* text=auto
```

### Demo site (recommended)

Recent official themes (e.g. `flexsearch`, `darkmodetoggle`) ship a `demo/` website in the repository:

- `demo/config.yml` enables the theme (`theme: [<name>]`) and sets its options; `demo/pages/` and a minimal `demo/layouts/_default/page.html.twig` show the feature.
- `demo/link.php` links the repository as `demo/themes/<name>` (symlink `../..`, or `mklink /J` junction on Windows), so the demo always uses the theme under development.
- `demo/.gitignore` ignores `_site/`, `.cache/`, `.cecil/` and `themes/`.

Run it locally:

```bash
php demo/link.php
php cecil.phar serve demo
```

A `.github/workflows/demo.yml` workflow deploys it to GitHub Pages: set `CECIL_BASEURL: https://<vendor>.github.io/theme-<name>/`, download `cecil.phar`, run `php demo/link.php`, `php cecil.phar build demo -vv`, then upload `demo/_site` with `actions/upload-pages-artifact` and deploy with `actions/deploy-pages`. Link the demo from the README (`**[Demo](https://<vendor>.github.io/theme-<name>/)**`).

## Conventions Checklist

- [ ] `config.yml` (not `.yaml`) with theme options under a `<name>:` namespace
- [ ] Layouts in `layouts/_default/`, children extend `['page.html.twig', '_default/page.html.twig']`
- [ ] `partials/metatags.html.twig` included in every HTML base layout `<head>`, with no duplicated `<title>`/meta tags (unless the user declined it)
- [ ] Other built-in partials reused (`paginator`, `navigation`, `breadcrumb`…)
- [ ] All links built with `url()`, all processed files with `asset()`
- [ ] Every option guarded with `|default()` or `??`
- [ ] Every UI string wrapped in `trans`, at least one `translations/messages.<locale>.yml`
- [ ] 2-space indentation in Twig/YAML/JSON/CSS, LF line endings
- [ ] **Twig files do not end with a trailing newline**
- [ ] `composer.json` with `"type": "cecil-theme"` and `extra.name`
- [ ] README with screenshot, install, usage and configuration sections
- [ ] Component theme: assets in `assets/<name>/`, CSS classes and custom properties prefixed with `<name>`, technical virtual pages with `exclude: true` and `serviceworker.precache: false`
- [ ] Tested on a demo site (`demo/` + `link.php`) with `php cecil.phar build -v` and `php cecil.phar serve`, without a site `layouts/` directory

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
- Configuration (`theme`): https://cecil.app/documentation/configuration/site/#theme
- Component themes: https://github.com/Cecilapp/theme-flexsearch, https://github.com/Cecilapp/theme-pwa, https://github.com/Cecilapp/theme-darkmodetoggle
- Minimal skeleton: https://github.com/Cecilapp/theme-example
