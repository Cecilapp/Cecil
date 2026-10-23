# Component Themes

How a component theme plugs into a site, and how to work with the official component themes.

## Contents

- [Component Themes Patterns](#component-themes-patterns)
- [Official Component Themes](#official-component-themes)
  - [Ask the user before installing](#ask-the-user-before-installing)
  - [Combining themes](#combining-themes)
  - [FlexSearch (`flexsearch`)](#flexsearch-flexsearch)
  - [PWA (`pwa`)](#pwa-pwa)
  - [Dark mode toggle (`darkmodetoggle`)](#dark-mode-toggle-darkmodetoggle)
  - [Pre-wiring component themes in a visual theme](#pre-wiring-component-themes-in-a-visual-theme)

## Component Themes Patterns

No automatic injection mechanism exists: a component theme plugs into a site using one or more of these patterns.

1. **Generated page + output format** (no template change needed), e.g. a `_redirects` file:

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

   With the layout `layouts/_default/page.redirects.twig`. Useful keys for `pages.default.<id>`: `path`, `layout`, `output`, `uglyurl`, `multilingual`, `published`, `excluded`.

   For a technical generated page (JSON index, manifest, script…), set `excluded: true` to keep it out of lists, and `serviceworker: {precache: false}` so the `pwa` theme does not precache it:

   ```yaml
   pages:
     default:
       flexsearch:
         path: flexsearch
         layout: flexsearch      # layouts/_default/flexsearch.json.twig
         output: json
         excluded: true
         serviceworker:
           precache: false
   ```

   Generated pages are multilingual by default (one file per language, e.g. `/flexsearch.json` and `/fr/flexsearch.json`); set `multilingual: false` for a single file.

2. **Partial to include**, documented in the README and guarded by a feature toggle:

   ```twig
   {# layouts/partials/<name>.html.twig #}
   {%- if site.<name>.enabled|default(false) %}
       <link rel="manifest" href="{{ url(site.page('manifest'), {canonical: true}) }}">
   {%- endif %}
   ```

   Usage: `{{ include('partials/<name>.html.twig', {site}, with_context = false) }}`.

   For an option accepting the `true|false` shorthand or an `enabled` key (e.g. `cache: false` is `cache: {enabled: false}`), use the `enabled` Twig test rather than testing the value: `{%- if config.cache is enabled %}` (custom options: `site.<name>|default(false) is enabled`).

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

   Usage: `{% import 'macros/icons.twig' as icons %}{{ icons.svg('github', 16) }}`. Data files shipped in the theme `data/` are available as `site.data.*`.

4. **Section-specific layouts** applied automatically when the site has a matching section (e.g. `layouts/episodes/list.rss.twig` for a podcast).

5. **Front matter blocks**: a visual theme can render page blocks dynamically:

   ```twig
   {% for block in page.blocks|default([]) %}
     {{ include('partials/blocks/' ~ block.name ~ '.html.twig', {block}, ignore_missing = true) }}
   {% endfor %}
   ```

6. **Twig components** in `layouts/components/<name>.twig`, used with `{% x:<name> with {...} %}…{% endx %}` or `<x-<name>>…</x-<name>>` (`slot` holds the inner content, `attributes` the passed attributes).

## Official Component Themes

Before writing a feature, check whether an official component theme already provides it. They are all installed with `composer require cecil/theme-<name>` (or by uncompressing the release archive in `themes/<name>/`), enabled in `theme`, then wired into layouts by including their partials.

| Theme            | Feature                                                                           |
|------------------|-----------------------------------------------------------------------------------|
| `flexsearch`     | Client-side full-text search: index generated at build time, DocSearch-like modal |
| `pwa`            | Web manifest, service worker, offline page (Progressive Web App)                  |
| `darkmodetoggle` | Icon button to switch between light and dark color schemes                        |
| `docsearch`      | Algolia DocSearch                                                                 |
| `fontawesome`    | Font Awesome icons helpers                                                        |
| `octicons`       | Twig macro for Primer Octicons icons                                              |
| `podcast`        | Templates to publish an audio show (RSS feed)                                     |
| `redirects`      | `_redirects` file generation                                                      |
| `headers`        | `_headers` file (Netlify, statichost, Cloudflare Pages) from `server.headers`     |
| `netlify`        | Netlify's `_redirects` and `_headers`                                             |
| `netlifycms`     | Netlify CMS support                                                               |

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

Pages with `excluded: true` are not indexed. Styles are overridable with `--flexsearch-*` custom properties.

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
- Exclude a page from precaching with front matter `serviceworker: {precache: false}`; other component themes should do the same for their technical generated pages.
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
