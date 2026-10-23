# Templates

Detailed reference for Twig templates in `layouts/`. The essential rules are in [SKILL.md](../SKILL.md#templates).

## Contents

- [Naming Convention](#naming-convention)
- [Lookup Rules](#lookup-rules-how-cecil-chooses-a-template)
- [Built-in Templates](#built-in-templates)
- [Metatags](#metatags-partialsmetatagshtmltwig)
- [Template Variables](#template-variables)
- [Taxonomies](#taxonomies)
- [Example Template](#example-template)
- [Built-in Partials and Utilities](#built-in-partials-and-utilities)
- [Pagination](#pagination)
- [Custom Filters and Functions](#custom-filters-and-functions)
- [Components](#components)
- [Fragments Cache](#fragments-cache)

## Naming Convention

Use this pattern:

```plaintext
layouts/(<section>/)<type>|<layout>.<format>(.<language>).twig
```

Examples:

- `layouts/page.html.twig` - default page template
- `layouts/list.html.twig` - section/home listing template
- `layouts/blog/list.atom.twig` - Atom feed template for `blog` section (RSS is not output by default: enable it with `output.pagetypeformats.section: {rss: true}`)
- `layouts/page.html.fr.twig` - French page template
- `layouts/_default/page.html.twig` - fallback template

## Lookup Rules (How Cecil Chooses a Template)

Cecil uses the first existing template, in priority order, for each page type. `<layout>` is the front matter `layout` variable, and each entry resolves to `<name>.<format>.twig` (e.g. `blog/list.html.twig`):

| Page type  | Lookup order                                                                                                                                                                      |
|------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Homepage   | `<layout>` → `index` → `home` → `list` → `_default/<layout>` → `_default/index` → `_default/home` → `_default/list` → `_default/page`                                             |
| Page       | `<section>/<layout>` → `<layout>` → `<section>/page` → `_default/<layout>` → `page` → `_default/page`                                                                             |
| Section    | `<layout>` → `<section>/index` → `<section>/list` → `section/<section>` → `<parent>/index` → `<parent>/list` → `section/<parent>` → `_default/section` → `list` → `_default/list` |
| Vocabulary | `taxonomy/<plural>` → `vocabulary` → `_default/vocabulary`                                                                                                                        |
| Term       | `taxonomy/<plural>/<term>` → `taxonomy/<singular>` → `term` → `_default/term` → `_default/list`                                                                                   |

For a term, `<plural>/<term>` is the term ID (e.g. `taxonomy/categories/php.html.twig` for one term, `taxonomy/category.html.twig` for all the terms of `categories`).

For a language other than the main one, the localized variant of each candidate (e.g. `page.html.fr.twig`) is tried first: all localized candidates come before the non-localized ones.

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

## Built-in Templates

Cecil embeds default templates in [`resources/layouts/`](https://github.com/Cecilapp/Cecil/tree/main/resources/layouts). They are always available to Twig (lowest priority, after site and theme layouts), so they can be rendered, included or extended **without being copied** into `layouts/`.

- `_default/` - fallback layouts: `page.html.twig`, `list.html.twig`, `home.html.twig`, `vocabulary.html.twig`, `term.html.twig`, `404.html.twig`, `404.json.twig`, `redirect.html.twig`, feeds (`list.atom.twig`, `list.rss.twig`, `list.jsonfeed.twig`), embeds (`page.embed.twig`, `page.oembed.twig`), JSON outputs, Markdown and LLMs outputs (`page.markdown.twig`, `list.markdown.twig`, `home.markdown.twig`, `home.llms.twig`, see [ai.md](ai.md)), `sitemap.xml.twig`, `robots.txt.twig`, etc.
- `partials/` - reusable fragments (see [Built-in Partials](#built-in-partials-and-utilities))
- `extended/` - base templates extended by other built-ins (`feed.twig`, extended by the Atom, RSS and JSON Feed templates)
- `components/` - none built-in, but site and theme [components](#components) live in `layouts/components/`
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

## Metatags (`partials/metatags.html.twig`)

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
- `description`, `keywords` (from `tags`), `author`, `robots` (`noindex` on paginated pages; pages with a `noindex` `metatags.robots` are also excluded from the sitemap)
- favicons (from `favicon.ico`, `favicon.svg`, `favicon.png` assets, resized)
- `prev`/`next`/`first`/`last` links, canonical and alternate formats, feeds, `hreflang` alternates
- `rel=me` links, Open Graph, Facebook, Twitter/X Card, Fediverse creator
- optional Dublin Core and JSON-LD structured data

Important:

- **Never add a separate `<title>`, `<meta name="description">`, canonical or `og:*` tags** next to this partial: they would be duplicated.
- Feed it through front matter (page) or configuration (site fallback): `title`, `description`, `tags`, `author`, `image`, `canonical.url`, `social.*`. `author` and `social` are maps (`social.twitter.site`, `social.twitter.creator`, `social.mastodon.creator`, `social.facebook.id`…), not plain handles: see [configuration.md](configuration.md#site).
- Tune it with the `metatags` configuration (per page with front matter `metatags`):

  ```yaml
  metatags:
    title:
      divider: " &middot; "
      only: false        # page title only
      pagination:
        shownumber: true # page number in paginated lists titles
        label: "Page %s"
    robots: "index,follow"
    navigation: true     # prev/next/first/last links
    image: true          # default social image (`false` to disable)
    favicon: true        # or `{enabled: true, image: favicon.png, sizes: {...}}`
    og: true
    twitter: true
    mastodon: true
    articles: "blog"     # site config only: section(s) rendered as Open Graph "article" and JSON-LD `BlogPosting`
    dc: false            # Dublin Core
    data: false          # JSON-LD structured data (`true` or a map of options)
  ```

- JSON-LD structured data (`metatags.data`) is a `@graph` of linked entities (`WebSite`, publisher, `WebPage`/`CollectionPage`, `BreadcrumbList`, `BlogPosting`, `VideoObject`, main menu `ItemList`). For a personal site, set the publisher as a `Person`; per page, set a specific type (`AboutPage`, `ContactPage`, etc.):

  ```yaml
  metatags:
    data:
      article: BlogPosting   # or NewsArticle
      publisher:
        type: Person         # Organization by default
        name: "Jane Doe"     # site title (or site author for a Person) by default
        logo: logo.png       # favicon image by default
  ```

  ```yaml
  # page front matter
  metatags:
    data:
      type: AboutPage
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

## Template Variables

Most useful variables in Twig:

- `site.<option>` - any configuration option (`site.title`, `site.baseurl`, `site.description`, custom options…), merged with built-in variables; when a built-in variable hides an option (e.g. `pages.default`), read the raw configuration with `config.<path>` (`config.pages.default.sitemap.priority`)
- `site.pages` - pages collection (current language); `site.allpages` - pages in all languages
- `site.pages.showable` - skips draft/paginated/redirect/excluded pages (generated sections and taxonomies are included)
- `site.page('about')` - a page by ID; `site.home` - ID of the homepage (`url(site.home)`, `site.page(site.home).sections`)
- `site.taxonomies` - vocabularies and terms
- `site.menus.<name>` - menu entries, with `id` (page ID without language prefix, useful to flag the active entry), `name`, `url` and `weight`; sort them with `|sort_by_weight` (a `main` menu is created automatically with the homepage and the top-level sections, unless they define their own `menu` variable)
- `site.data.<file>` - data files; use the bracket notation for subfolders and dashes (`data/galleries/gallery-1.json` → `site.data.galleries['gallery-1']`)
- `site.static` - static files (only when `static.load: true`), with `path`, `name`, `basename`, `ext`, `type`, `subtype`, `date`, `updated`, `exif`, `audio`, `video`
- `site.time`, `site.debug`, `site.build` (build ID)
- `page.id` (`blog/post-1`), `page.title`, `page.date`, `page.updated`, `page.body` (raw Markdown), `page.content` (HTML), `page.path`, `page.slug`, `page.filepath`, `page.type` (`homepage`, `page`, `section`, `vocabulary` or `term`), `page.section`, `page.<custom>` (any front matter variable)
- `page.pages` - sub pages of a section or the homepage (use `page.pages.showable`); `page.parent`, `page.ancestors`, `page.sections`, `page.toplevel` for nested sections
- `page.prev` / `page.next` - adjacent pages in the section: `{% if page.prev is defined %}<a href="{{ url(page.prev) }}">{{ page.prev.title }}</a>{% endif %}`
- `page.translations` - translated versions of the page
- `cecil.url`, `cecil.version`, `cecil.poweredby` - to link the Cecil website

For multilingual sites (`site.language`, translations, language switcher), see [multilingual.md](multilingual.md).

## Taxonomies

- Vocabulary page (`/tags/`): `page.plural`, `page.singular`, `page.terms`; each term has `term.id` (`tags/php`), `term.name` (`PHP`) and `term|length` (number of pages).
- Term page (`/tags/php/`): `page.title` (term name), `page.term` (term ID), `page.plural`, `page.singular`, `page.pages` (sorted by date).

```twig
{# layouts/taxonomy/tags.html.twig (vocabulary) #}
{% for term in page.terms %}
  <a href="{{ url(term.id) }}">{{ term.name }}</a> ({{ term|length }})
{% endfor %}

{# layouts/taxonomy/tag.html.twig (all terms of "tags") #}
{% for p in page.paginator.pages ?? page.pages %}
  <a href="{{ url(p) }}">{{ p.title }}</a>
{% endfor %}

{# terms of the current page #}
{% for tag in page.tags ?? [] %}
  <a href="{{ url('tags/' ~ tag) }}">{{ tag }}</a>
{% endfor %}
```

`url()` slugifies the string to find the term page (`url('tags/Static site')` → `/tags/static-site/`). The built-in `partials/terms-list.html.twig` renders the terms of a vocabulary: `{{ include('partials/terms-list.html.twig', {vocabulary: 'tags'}) }}`.

## Example Template

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

## Built-in Partials and Utilities

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
- `partials/notes/<type>.html.twig` - rendering of the Markdown `:::<type>` notes (`tabs`, `code-group`, `steps`, `accordion`, `cards`), rendered during conversion with a `note` variable only (no `page` or `site`); add one to render another note type. To nest notes, open the parent with a longer fence (`::::`, then `:::::`…): a note is closed by a line with at least as many `:` as its opening line

If a built-in template really needs to be modified, extract them all into `layouts/` (last resort, see [Built-in Templates](#built-in-templates)):

```bash
php cecil.phar util:templates:extract
```

## Pagination

Pagination is configured globally under `pages.pagination` (defaults: `max: 5`, `path: page`), and can be overridden in section front matter (see [Front Matter](../SKILL.md#front-matter)).

```yaml
pages:
  pagination:
    max: 10
```

`pages.pagination: false` disables it globally; a section can then enable it again with `pagination: {enabled: true}`.

In templates, paginated lists (homepage, sections, terms) expose `page.paginator`:

- `pages` (pages of the current paginator page), `pages_total` (total number of pages), `count` (number of paginator pages), `current` (current position)
- `links.first`, `links.prev`, `links.self`, `links.next`, `links.last`, `links.path`: they are **page IDs**, to wrap with `url()`

```twig
{% for p in page.paginator.pages ?? page.pages %}…{% endfor %}
{% if page.paginator.links.next is defined %}
  <a href="{{ url(page.paginator.links.next) }}">Next</a>
{% endif %}
{# numbered link (page 1 is `links.first`) #}
<a href="{{ url(page.paginator.links.path ~ '/' ~ i) }}">{{ i }}</a>
```

Or include the built-in paginator links:

```twig
{% include 'partials/paginator.html.twig' %}
```

## Custom Filters and Functions

Cecil Twig helpers commonly used in templates (full list in the [templates reference](https://cecil.app/documentation/templates/reference/)):

- Functions: `url()`, `asset()`, `html()` (HTML element of an asset: `<img>`, `<picture>`, `<link>`, `<script>`, `<audio>`, `<video>`), `image()`, `css()`, `js()`, `audio()`, `video()`, `integrity()`, `image_srcset()`, `image_sizes()`, `image_from_website()`, `readtime()`, `hash(value, algo)` (`xxh128` by default), `cache_key()`, `getenv('VAR')`, `dump()` and `d(var, {theme: 'dark'})` (debug mode only)
- Asset filters: `resize`, `webp`, `avif`, `lqip`, `dominant_color`, `maskable`, `dataurl`, `inline`, `minify`, `fingerprint`, `to_css`; string filters: `minify_css`, `minify_js`, `scss_to_css` (see [assets.md](assets.md))
- Content filters: `markdown_to_html` (also `{% apply markdown_to_html %}`), `toc`, `excerpt`, `excerpt_html`, `slugify`, `highlight`, `trans`
- Collection filters: `filter_by`, `sort_by_date`, `sort_by_title`, `sort_by_weight`
- Utility filters: `json_decode`, `yaml_parse`, `iterable` (scalar or list front matter value → list: `page.authors|iterable`), `unique` (case-insensitive), `singular`, `plural`, `u` (Symfony UnicodeString), `preg_split`, `preg_match_all`, `hex_to_rgb` (`.red`, `.green`, `.blue`), `splitline`, `duration_to_iso8601`
- Tests: `enabled` (config options; checks only the given value, not its parents), `external` (URL or menu entry outside the site), `asset`, `image_large` (landscape, at least 600 × 315), `image_square`

`url()` accepts a page, a menu entry, an asset, a page ID or a path, and options; it is also available as a filter (`page|url`):

```twig
{{ url(page) }}
{{ url(page, {canonical: true}) }}  {# absolute URL (or `canonical.url`); `canonicalurl: true` makes it the default #}
{{ url(page, {format: 'json'}) }}   {# URL of another output format #}
{{ url(page, {language: 'fr'}) }}   {# URL of the translation #}
{{ url('tags/My Tag') }}            {# strings are slugified to find a page ID: /tags/my-tag/ #}
```

`html()` takes an asset, HTML attributes, then options (`preload`, and for images `formats`, `responsive` (`true`/`width` or `density`, which needs a `width` attribute) and `placeholder` (`color` or `lqip`); defaults in `layouts.images`):

```twig
{{ html(asset(page.image), {alt: page.title}, {responsive: true, formats: ['avif', 'webp']}) }}
{{ html(asset('logo.png'), {width: 256, alt: ''}, {responsive: 'density'}) }}
{{ html(asset('styles.css'), {title: 'Main theme'}, {preload: true}) }}
{{ html([
  {asset: asset('css/style.css')},
  {asset: asset('css/dark.css'), attributes: {media: '(prefers-color-scheme: dark)'}}
]) }}
```

Signatures of the content filters (arguments are positional or named):

```twig
{{ page.body|toc }}                                {# or toc(format='json', selectors=['h2'], url=url(page)) #}
{{ page.content|excerpt_html }}                    {# or excerpt_html({separator: 'excerpt|break', capture: 'after'}) #}
{{ page.description|excerpt(250, '…') }}           {# length (450 by default), suffix #}
```

Sorting and filtering:

```twig
{{ site.pages.showable|filter_by('section', 'blog')|sort_by_date|slice(0, 5) }} {# most recent first #}
{{ site.pages|sort_by_date(variable='updated', desc_title=true) }}
{{ site.pages|sort_by_date|reverse }}                                        {# oldest first #}
{{ site.pages|filter(p => p.generated == false) }}                           {# Twig filter for other cases #}
{% for p in site.pages.showable.sortBy({variable: 'updated', reverse: true}) %}…{% endfor %}
```

`filter_by` only returns regular pages (type `page`, no sections nor redirections); `sort_by_title` is a natural sort.

Deprecated helpers to avoid: the `html` **filter** (`asset|html`, use the `html()` function), `minify()` (use `|minify`), `toCSS()` (use `|to_css`), `image_from_url()` (use `image_from_website()`), `|cover(w, h)` (use `|resize(w, h)`).

## Components

Reusable Twig components are stored in `layouts/components/` (e.g. `layouts/components/button.twig`; `layouts.components.dir` and `ext` to change it) and used with the `x` tag or an HTML-like syntax; the `slot` variable holds the inner content and `attributes` the passed attributes:

```twig
{# layouts/components/button.twig #}
<button {{ attributes.merge({class: 'btn'}) }}>{{ slot }}</button>
```

```twig
{% x:button with {class: 'primary'} %}Click me{% endx %}
<x-button class="primary">Click me</x-button>   {# renders class="primary btn" #}
<x-button :title="page.title">Click me</x-button> {# `:` prefix: the value is a Twig expression #}
```

- `attributes.merge({...})` or `attributes.class('...')` merges the passed attributes with default ones.
- Components in subfolders use the dot notation: `layouts/components/button/primary.twig` → `{% x:button.primary %}` / `<x-button.primary>`.
- Named slots:

  ```twig
  {# layouts/components/card.twig #}
  <div {{ attributes.class('card') }}>
    <h2 {{ title.attributes.class('card-title') }}>{{ title }}</h2>
    <div>{{ body }}</div>
  </div>
  ```

  ```twig
  {% x:card %}
    {% slot:title with {class: 'h3'} %}Title{% endslot %}
    {% slot:body %}Body{% endslot %}
  {% endx %}
  {# or #}
  <x-card>
    <x-slot name="title" class="h3">Title</x-slot>
    <x-slot name="body">Body</x-slot>
  </x-card>
  ```

## Fragments Cache

Cache an expensive part of a template (e.g. a sidebar built from many pages) with the `cache` tag. The cache is **persistent across builds**: always build the key with `cache_key(name, value)`, which adds a hash of the value, the language and the build ID.

```twig
{% cache cache_key('sidebar', page.id) %}
  {# cacheable content #}
{% endcache %}
```

Clear it with `php cecil.phar cache:clear:templates --fragments`.
