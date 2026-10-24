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

## Reference Files

Read the matching reference **before** working on a topic it covers:

| Read                                                           | Before                                                                                                   |
|----------------------------------------------------------------|----------------------------------------------------------------------------------------------------------|
| [references/component-themes.md](references/component-themes.md) | Creating a component theme, or installing, combining or pre-wiring official component themes (FlexSearch, PWA, dark mode toggle, etc.) |
| [references/packaging.md](references/packaging.md)             | Packaging a theme for Composer: `composer.json`, `.editorconfig`, README, `.gitattributes`, demo site   |

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

| Resource       | Lookup order                                                                                                                                             |
|----------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|
| Layouts        | `layouts/` → `themes/<theme>/layouts/` (in `theme` list order) → Cecil internal `resources/layouts/`                                                     |
| Assets         | `assets/` → `themes/<theme>/assets/` → `static/` → `themes/<theme>/static/` (Sass import paths also include the theme's dirs)                            |
| Static files   | Themes' `static/` are copied first (reverse order), then site `static/`, so the site wins                                                                |
| Translations   | Cecil internal → `themes/<theme>/translations/` (in `theme` list order, so the **last** theme wins between themes) → site `translations/` (site wins)    |
| Data files     | Site `data/` and each `themes/<theme>/data/` are loaded into `site.data` (theme data is loaded even if the site has no `data/` directory)                |
| Configuration  | `themes/<theme>/config.yml` overrides Cecil defaults (including default pages, generators and output formats), site config always wins (first theme wins between themes) |

Consequences:

- The config file **must** be named `config.yml` (not `config.yaml`); it's the only one loaded.
- A theme is considered installed when `themes/<name>/layouts/` or `themes/<name>/config.yml` exists; otherwise the build fails with `Theme "<name>" not found. Did you forgot to install it?`. A theme without `layouts/` (config, data or assets only) is supported.
- Layouts are resolved **per candidate name**: for each name of the lookup rules, Cecil checks the site, then the themes, then the built-in templates, before trying the next name. So a built-in `_default/home`, `_default/term`, `_default/vocabulary` or `_default/404` is picked before a theme's `_default/list` or `_default/page`: ship these layouts in the theme to control them. The built-ins extend `['page.html.twig', '_default/page.html.twig']` and fill the `content` block, so the theme base layout must define a `content` block.
- Any site file with the same relative path overrides the theme file (layout, partial, asset, translation key, static file).
- A list of values defined by a theme (e.g. `static.exclude`, `output.pagetypeformats.<type>`) **replaces** Cecil's default list; only a site list is combined with the theme list.
- Theme dependencies are only partially resolved: when the site uses the list form (`theme: [a]`) and theme `a` declares `theme: [b]`, `b` is added and its layouts, assets, static files and translations are used, but **its `config.yml` is not loaded**; with the string form (`theme: a`), `b` is ignored. Document the full list the user must declare (e.g. `theme: [links, fontawesome]`).
- A theme can also ship PHP classes in `themes/<name>/extensions/` (autoloaded, path matching the namespace), registered in its `config.yml` as Twig extensions (`layouts.extensions`) or generators (`pages.generators`).

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
├─ extensions/             # optional PHP classes (Twig extensions, generators)
├─ layouts/
│  ├─ _default/
│  │  ├─ page.html.twig    # base layout (defines blocks, including `content`)
│  │  ├─ list.html.twig    # sections
│  │  ├─ home.html.twig    # otherwise the built-in one is used
│  │  ├─ term.html.twig    # otherwise the built-in one is used
│  │  ├─ vocabulary.html.twig
│  │  └─ 404.html.twig
│  ├─ <section>/           # optional per-section layouts (e.g. blog/page.html.twig)
│  ├─ components/          # optional Twig components ({% x:<name> %} or <x-name>)
│  ├─ partials/            # header, footer, nav, pagination, post-item...
│  │  └─ notes/            # optional Markdown notes templates (`:::<type>` → <type>.html.twig)
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
├─ config.yml                     # new generated pages, output formats, options
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

Develop the theme directly in `themes/<name>/` and enable it in the site `cecil.yml` (created by `new:site`; `config.yml` is only loaded when there is no `cecil.yml`):

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
    max: 10       # only set what differs from Cecil defaults
# Theme options
<name>:
  sidebar:
    sticky: true
  color: ''       # red, orange, green, blue...
  reverse: false
```

Rules:

- Never rely on a key being set: users may override or remove it. In templates, always guard with `??` or `|default()`, knowing that `|default()` also replaces **empty** values (`false`, `''`, `0`, `[]`), not only undefined ones:
  - use `??` when the user can disable or empty an option, and the fallback is truthy: `site.<name>.breadcrumb ?? true`, `site.<name>.logo ?? 'logo.svg'` (`logo: ''` hides it), `site.<name>.toc.min ?? 2` (`0` is kept). With `|default(true)`, `breadcrumb: false` would be ignored.
  - `|default()` is fine when the fallback is falsy (`site.<name>.reverse|default(false)`, `page.blocks|default([])`), or when an empty value must be replaced (`|default('Latest posts')`).
  - wrap `??` in parentheses inside a condition combined with other operators: `(site.<name>.toc.enabled ?? true) and (page.toc ?? true)`.
- Cecil has no default vocabularies: declare `taxonomies` if the theme displays tags or categories.
- Avoid redefining Cecil's lists of values (`static.exclude`, `pages.ext`…): a theme list replaces the default list instead of extending it.
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
- Metatags already links the favicons (`favicon.svg`, `favicon.png` resized, `favicon.ico`, from assets or static) and outputs JSON-LD as a linked graph (`WebSite`, `WebPage`, `BreadcrumbList`, `BlogPosting`…) when `metatags.data` is enabled: don't add favicon links or a separate JSON-LD/breadcrumb schema. Ship `assets/favicon.png` (or `.svg`) instead.
- Twig autoescape is disabled by default (`layouts.autoescape: false`): `{{ page.content }}` needs no `|raw`, but escape values printed in attributes (`alt="{{ page.title|e('html_attr') }}"`), and don't rely on autoescaping for user-provided text.
- Reuse the other Cecil built-in partials instead of re-implementing them: `partials/paginator.html.twig`, `partials/navigation.html.twig` (expects a `menu` variable, and optionally `page` to mark the current entry with `aria-current`: `{menu: site.menus.main, page: page}`), `partials/breadcrumb.html.twig`, `partials/languages.html.twig`, `partials/theme-selector.html.twig`.
- **Code highlighting**: when replacing `_default/page.html.twig`, keep the code blocks styled, e.g. `<style>{{ include('partials/highlight.css.twig') }}</style>` when `config.pages.body.highlight` is enabled, or ship CSS for both engines' classes (`hljs-*` for `highlightjs`, `hl-*` for `tempest`, see `pages.body.highlight.engine`).
- **Markdown output hooks**: style the empty `<a class="heading-anchor">` link added to `h2`/`h3` headings (see `pages.body.anchors`, e.g. a `#` on hover) and use the `data-lang` attribute of `<pre>` code blocks (e.g. a language label).
- **Markdown notes**: `:::<type>` notes render as `<aside class="note note-<type>">`, and the built-in `tabs`, `code-group`, `steps`, `accordion` and `cards` types as `<div class="note note-<type>">` (no JavaScript); style these classes, and the items: `.note-tab` (tabs, code-group), `.note-steps-list`, `.note-accordion-item` (`<details>`), `.note-cards-list`. A theme can override or add `partials/notes/<type>.html.twig`, which only receives a `note` variable (no `page` or `site`).
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
{% if page.paginator.pages is defined %}
{% set pages = page.paginator.pages %}
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

To map a whole section to another section's layouts, the site (or the theme config) can use `layouts.sections` (e.g. `layouts: {sections: {news: blog}}`).

Remember the lookup rules when naming layouts (see the `cecil` skill, "Lookup Rules"): `<section>/index`, `<section>/page`, `<section>/list`, `section/<section>`, parent sections, `_default/section`, `index`/`home`, `taxonomy/<plural>` (vocabulary), `taxonomy/<plural>/<term>` and `taxonomy/<singular>` (terms), `term`, `vocabulary`, `_default/*`, with optional language variants (`page.html.fr.twig`). For non-HTML outputs, the file name is `<layout>.<format>.twig` (e.g. `list.rss.twig`, `page.webmanifest.twig`).

Non-HTML built-ins a theme may override: feeds (`_default/list.atom.twig`, `list.rss.twig`, `list.jsonfeed.twig`, all extending `extended/feed.twig`), their XSL stylesheets (`_default/feed.xsl.twig`, `_default/sitemap.xsl.twig`), `_default/sitemap.xml.twig`, `_default/robots.txt.twig`, `_default/home.llms.twig`, the `*.markdown.twig` and `*.json.twig` outputs, `_default/404.html.twig` and `_default/redirect.html.twig`. Override them only when needed: they receive Cecil fixes and features.

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
{{ html(asset(post.image), {alt: post.title}, {responsive: true, formats: ['webp']}) }}
{# optional asset #}
{% set logo = asset(site.logo|default('logo.svg'), {ignore_missing: true}) %}
{% if not logo.missing %}{{ html(logo, {alt: site.title}) }}{% endif %}
{# small inline CSS/JS #}
<style>{% apply minify_css %}.hero { color: red; }{% endapply %}</style>
```

- Use the `html()` function (the `|html` filter is deprecated).
- Use `static/` for files that must keep their name and not be processed: web fonts, downloads, etc. Link them with `url()` (favicons are already linked by metatags). Don't ship a `static/robots.txt`: `robots.txt` is a built-in default page (custom rules go in a `pages/robots.md` page of the site).
- Expose design options as Sass variables with `assets.compile.variables` in the theme `config.yml` (e.g. `assets: {compile: {variables: {primary: "#0d6efd"}}}`); Sass import paths (`assets.compile.import`, `[sass, scss, node_modules]` by default) are resolved in the `assets/` and `static/` directories of the site and of the themes, and next to the compiled file.
- Leave minification and fingerprinting to the site config (`assets.minify`, `assets.fingerprint`) rather than forcing them in templates.
- Sass sources in `assets/` are not copied to the output (only `static/` is), and `*.scss` is already excluded by default: no `static.exclude` is needed.
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
- Use the `external` test to detect links outside the site (e.g. `{% if entry is external %} rel="noopener" target="_blank"{% endif %}`), rather than matching `http`.
- Link to the Cecil website with the `cecil` global variable, never a hardcoded `https://cecil.app`: `cecil.url` is already absolute (it includes the version as anchor), so don't pass it to `url()`. `cecil.poweredby` (`Cecil v<version>`) and `cecil.version` are available for the label:

  ```twig
  {% trans with {'%cecil%': '<a href="' ~ cecil.url ~ '">Cecil</a>'} %}Powered by %cecil%{% endtrans %}
  <a href="{{ cecil.url }}">{{ cecil.poweredby }}</a>
  ```
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

- File names follow `messages.<locale>.<ext>` (`yml`/`yaml`, or compiled `mo`; `.po` files are not loaded), where `<locale>` is the language locale (`fr_FR`) or its first two letters (`fr`).
- Extract strings with `php cecil.phar util:translations:extract --locale=fr --save --theme=<name>`: the file is written to the **site** `translations/` directory (`messages.fr.yaml`), move it into `themes/<name>/translations/`.

## Component Themes

No automatic injection mechanism exists: a component theme plugs into a site through generated pages and output formats, partials to include, macros, section-specific layouts, front matter blocks or Twig components. Official component themes exist (FlexSearch, PWA, dark mode toggle, etc.): **ask the user before installing one**. Read [references/component-themes.md](references/component-themes.md) for the patterns and the official component themes.

## Packaging

A theme is published as a Composer package (`composer.json` with type `cecil-theme`). Read [references/packaging.md](references/packaging.md) for the files to provide.

## Conventions Checklist

- [ ] `config.yml` (not `.yaml`) with theme options under a `<name>:` namespace
- [ ] Layouts in `layouts/_default/` (including `home`, `term`, `vocabulary` and `404` to override the built-ins), children extend `['page.html.twig', '_default/page.html.twig']`, base layout defines a `content` block
- [ ] Code blocks styled for the configured highlight engine, Markdown notes classes styled
- [ ] `partials/metatags.html.twig` included in every HTML base layout `<head>`, with no duplicated `<title>`/meta tags (unless the user declined it)
- [ ] Other built-in partials reused (`paginator`, `navigation`, `breadcrumb`…)
- [ ] All links built with `url()`, all processed files with `asset()`
- [ ] Links to the Cecil website use `{{ cecil.url }}` (not a hardcoded URL, not wrapped in `url()`)
- [ ] Every option guarded with `??` or `|default()`; `??` for options that can be set to `false`, `''` or `0` with a truthy fallback (never `|default(true)`)
- [ ] Every UI string wrapped in `trans`, at least one `translations/messages.<locale>.yml`
- [ ] 2-space indentation in Twig/YAML/JSON/CSS, LF line endings
- [ ] **Twig files do not end with a trailing newline**
- [ ] `composer.json` with `"type": "cecil-theme"`, `extra.cecil.theme-name` and `"cecil/theme-installer": "^2.1"`
- [ ] README with screenshot, install, usage and configuration sections
- [ ] Component theme: assets in `assets/<name>/`, CSS classes and custom properties prefixed with `<name>`, technical generated pages with `excluded: true` and `serviceworker.precache: false`
- [ ] Tested on a demo site (`demo/` + `link.php`) with `php cecil.phar build -v` and `php cecil.phar serve`, without a site `layouts/` directory

## Troubleshooting

- **`Theme "<name>" not found`**: the folder must be `themes/<name>/` (matching the theme name resolved by the installer: `extra.cecil.theme-name`, `extra.name` or the package name) and contain `layouts/` or `config.yml`.
- **Theme installed in `vendor/` instead of `themes/`**: the project doesn't allow the plugin; add `"cecil/theme-installer": true` to `config.allow-plugins` in the project's `composer.json`, then reinstall.
- **`The ".../themes/<name>/layouts" directory does not exist`**: the theme has a `config.yml` but no `layouts/` directory; add one, even empty.
- **Theme layout ignored**: a site `layouts/` file with the same path, or any file (site, theme or built-in) matching a higher-priority lookup name, wins (e.g. the built-in `_default/home.html.twig` before the theme `_default/list.html.twig`); check with `php cecil.phar build -vv`, or remove the site layout.
- **Theme data missing (`site.data.*`)**: the site has no `data/` directory; create one.
- **Theme option ignored**: the site config defines the same key; theme config never overrides site config. Lists of values are the exception: a list defined by the site (e.g. `output.pagetypeformats.homepage`) is added to the theme list, so the values required by the theme are kept; the site can only remove one with a map of booleans (e.g. `homepage: {atom: false}`).
- **Dependent theme not loaded (or its options missing)**: list it explicitly in the site `theme` key; a dependency declared in a theme's `config.yml` never has its own `config.yml` loaded.
- **Asset not found**: paths in `asset()` are relative to `assets/` (or `static/`) of the site or theme, without the directory prefix.
- **Translations missing**: check the file name matches `messages.<locale>` or `messages.<first two letters of the locale>` (e.g. `fr_FR` → `messages.fr_FR.yml` or `messages.fr.yml`); the translations cache is refreshed when a file is updated or removed, but not when a new one is added: run `php cecil.phar cache:clear:translations`.
- **`Deprecated configuration option(s) in …`**: the theme's `config.yml` uses a renamed or removed option (see `config/deprecated.php`), which makes the build fail for every site using the theme; apply the snippet given in the error message.
- Run `php cecil.phar doctor` to check theme configuration.

## Useful Resources

- Official themes: https://github.com/orgs/Cecilapp/repositories?q=theme
- Themes showcase: https://cecil.app/themes/
- Templates documentation: https://cecil.app/documentation/templates/
- Configuration (`theme`): https://cecil.app/documentation/configuration/site/#theme
- Component themes: https://github.com/Cecilapp/theme-flexsearch, https://github.com/Cecilapp/theme-pwa, https://github.com/Cecilapp/theme-darkmodetoggle
- Minimal skeleton: https://github.com/Cecilapp/theme-example
