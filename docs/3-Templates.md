<!--
description: "Working with templates and use variables."
date: 2021-05-07
updated: 2022-09-18
alias: documentation/layouts
-->

# Templates

Cecil is powered by the [Twig](https://twig.symfony.com) template engine.

:::info
Refer to the **[official documentation](https://twig.symfony.com/doc/templates.html)** to learn how to use Twig.
:::

## Example

```twig
<h1>{{ page.title }} - {{ site.title }}</h1>
<span>{{ page.date|date('j M Y') }}</span>
<p>{{ page.content }}</p>
<p>{{ page.variable }}</p>
```

## Files organization

Templates files are stored in `layouts/`.

```plaintext
<mywebsite>
├─ pages
├─ layouts
|  ├─ _default           <- Contains default templates
|  |  ├─ list.html.twig  <- Used by "section" and "term" pages type
|  |  └─ page.html.twig  <- Used by "page" pages type
|  └─ index.html.twig    <- Used by the "homepage" type
└─ themes
   └─ <theme>            <- A custom theme
      ├─ layouts
      └─ ...
```

## Lookup rules

In most of cases **you don’t need to [specify the template](2-Content.md#predefined-variables)** to use: Cecil selects the most appropriate template, by page type.

:::
**Glossary:**

- `<format>`: output format (e.g.: `html`)
- `<layout>`: value of variable `layout` set in front matter (e.g.: `layout: post`)
- `<section>`: page’s _Section_ (e.g.: `blog`)
:::

### Type _homepage_

1. `<layout>.<format>.twig`
2. `index.<format>.twig`
3. `list.<format>.twig`
4. `_default/index.<format>.twig`
5. `_default/list.<format>.twig`
6. `_default/page.<format>.twig`

### Type _page_

1. `<section>/<layout>.<format>.twig`
2. `<layout>.<format>.twig`
3. `<section>/page.<format>.twig`
4. `page.<format>.twig`
5. `_default/page.<format>.twig`

### Type _section_

1. `<layout>.<format>.twig`
2. `<section>/list.<format>.twig`
3. `section/<section>.<format>.twig`
4. `_default/section.<format>.twig`
5. `_default/list.<format>.twig`

### Type _vocabulary_

1. `taxonomy/<plural>.<format>.twig`
2. `_default/vocabulary.<format>.twig`

### Type _term_

1. `taxonomy/<term>.<format>.twig`
2. `_default/term.<format>.twig`
3. `_default/list.<format>.twig`

## Variables

> The application passes variables to the templates for manipulation in the template. Variables may have attributes or elements you can access, too.
>
> Use a dot (.) to access attributes of a variable: `{{ foo.bar }}`

You can use variables from different scopes: [`site`](#site), [`page`](#page), [`cecil`](#cecil).

### site

The `site` variable contains all variables from the configuration and some built-in variables.

_Example:_

```yaml
title: "My amazing website!"
```

Can be displayed in a template with:

```twig
{{ site.title }}
```

#### Built-in variables

| Variable              | Description                                            |
| --------------------- | ------------------------------------------------------ |
| `site.home`           | ID of the home page.                                   |
| `site.pages`          | Collection of pages, in the current language.          |
| `site.pages.showable` | Same as `site.pages` but filtered by "showable" status (published pages and not virtual/redirect/excluded). |
| `site.page('id')`     | A page with the given ID in the current language.      |
| `site.allpages`       | Collection of all pages, regardless of their language. |
| `site.taxonomies`     | Collection of vocabularies.                            |
| `site.time`           | [_Timestamp_](https://wikipedia.org/wiki/Unix_time) of the last generation. |

:::tip
You can get any page, regardless of their language, with `site.pages['id']` where `id` is the _ID_ of a page.
:::

#### site.menus

Loop on `site.menus.<menu>` to get each entry of the `<menu>` collection (e.g.: `main`).

| Variable         | Description                                      |
| ---------------- | ------------------------------------------------ |
| `<entry>.name`   | Menu entry name.                                 |
| `<entry>.url`    | Menu entry URL.                                  |
| `<entry>.weight` | Menu entry weight (useful to sort menu entries). |

_Example:_

```twig
<nav>
  <ol>
  {% for entry in site.menus.main|sort_by_weight %}
    <li><a href="{{ url(entry.url) }}" data-weight="{{ entry.weight }}">{{ entry.name }}</a></li>
  {% endfor %}
  </ol>
</nav>
```

#### site.language

Informations about the current language.

| Variable               | Description                                                  |
| ---------------------- | ------------------------------------------------------------ |
| `site.language`        | Language code (e.g.: `en`).                                  |
| `site.language.name`   | Language name (e.g.: `English`).                             |
| `site.language.locale` | Language [locale code](configuration/locale-codes.md) (e.g.: `en_EN`). |
| `site.language.weight` | Language position in the `languages` list.                   |

:::tip
You can retrieve `name`, `locale` and `weight` of a specific language by passing its code as a parameter.  
e.g.: `site.language.name('fr')`.
:::

#### site.static

The static files collection can be accessed via `site.static` if the [_static load_](4-configuration.md#static) is enabled.

Each file exposes the following properties:

- `path`: relative path (e.g.: `/images/img-1.jpg`)
- `date`: creation date (_timestamp_)
- `updated`: modification date (_timestamp_)
- `name`: name (e.g.: `img-1.jpg`)
- `basename`: name without extension (e.g.: `img-1`)
- `ext`: extension (e.g.: `jpg`)

#### site.data

A data collection can be accessed via `site.data.<filename>` (without file extension).

_Examples:_

- `data/authors.yml` : `site.data.authors`
- `data/galleries/gallery-1.json` : `site.data.galleries.gallery-1`

### page

Contains built-in variables of a page **and** those set in the [front matter](2-Content.md#front-matter).

| Variable              | Description                                            | Example                    |
| --------------------- | ------------------------------------------------------ | -------------------------- |
| `page.id`             | Unique identifier.                                     | `blog/post-1`              |
| `page.title`          | File name (without extension).                         | `Post 1`                   |
| `page.date`           | File creation date.                                    | _DateTime_                 |
| `page.updated`        | File modification date.                                | _DateTime_                 |
| `page.body`           | File body.                                             | _Markdown_                 |
| `page.content`        | File body converted in HTML.                           | _HTML_                     |
| `page.section`        | File root folder (_slugified_).                        | `blog`                     |
| `page.path`           | File path (_slugified_).                               | `blog/post-1`              |
| `page.slug`           | File name (_slugified_).                               | `post-1`                   |
| `page.tags`           | Array of _tags_.                                       | `[Tag 1, Tag 2]`           |
| `page.categories`     | Array of _categories_.                                 | `[Category 1, Category 2]` |
| `page.pages`          | Collection of all sub pages.                           | _Collection_               |
| `page.pages.showable` | `page.pages` with "showable" pages only.               | _Collection_               |
| `page.type`           | `homepage`, `page`, `section`, `vocabulary` or `term`. | `page`                     |
| `page.filepath`       | File system path.                                      | `Blog/Post 1.md`           |
| `page.translations`   | Collection of translated pages.                        | _Collection_               |

#### page.<prev/next>

Navigation between pages in a same _Section_.

| Variable              | Description                                            | Example                    |
| --------------------- | ------------------------------------------------------ | -------------------------- |
| `page.prev`           | Previous page.                                         | _Page_                     |
| `page.next`           | Next page.                                             | _Page_                     |

_Example:_

```twig
<a href="{{ url(page.prev) }}">{{ page.prev.title }}</a>
```

#### page.paginator

_Paginator_ help you to build a navigation for list pages: homepage, sections, and taxonomies.

| Variable                     | Description                   |
| ---------------------------- | ----------------------------- |
| `page.paginator.pages`       | Paginated pages collection.   |
| `page.paginator.totalpages`  | Paginated total pages.        |
| `page.paginator.current`     | Position of the current page. |
| `page.paginator.count`       | Number of pages.              |
| `page.paginator.links.self`  | Page ID of the current page.  |
| `page.paginator.links.first` | Page ID of the first page.    |
| `page.paginator.links.prev`  | Page ID of the previous page. |
| `page.paginator.links.next`  | Page ID of the next page.     |
| `page.paginator.links.last`  | Page ID of the last page.     |

:::important
Links entries are Page ID, so you must use the `url()` function to create working links.  
In a future release, links should be a _Page_.
:::

_Example:_

```twig
{% if page.paginator %}
  {% set links = page.paginator.links %}
<div>
  {% if links.prev is defined %}
  <a href="{{ url(links.prev) }}">Previous</a>
  {% endif %}
  {% if links.next is defined %}
  <a href="{{ url(links.next) }}">Next</a>
  {% endif %}
</div>
{% endif %}
```

#### Taxonomy

Variables available in _vocabulary_ and _term_ templates.

##### Vocabulary

| Variable        | Description                       |
| --------------- | --------------------------------- |
| `page.plural`   | Vocabulary name in plural form.   |
| `page.singular` | Vocabulary name in singular form. |
| `page.terms`    | List of terms (_Collection_).     |

##### Term

| Variable     | Description                                |
| ------------ | ------------------------------------------ |
| `page.term`  | Term ID.                                   |
| `page.pages` | List of pages in this term (_Collection_). |

### cecil

| Variable          | Description                                         |
| ----------------- | --------------------------------------------------- |
| `cecil.url`       | URL of the official website.                        |
| `cecil.version`   | Cecil current version.                              |
| `cecil.poweredby` | Print `Cecil v%s` with `%s` is the current version. |

## Functions

> [Functions](https://twig.symfony.com/doc/functions/index.html) can be called to generate content. Functions are called by their name followed by parentheses (`()`) and may have arguments.

### url

Create a valid URL for a page, a page ID or a path.

```twig
{{ url(value, {options}) }}
```

| Option    | Description                                | Type    | Default |
| --------- | ------------------------------------------ | ------- | ------- |
| canonical | Prefixes the relative URL with `baseurl`.  | boolean | `false` |
| format    | Defines page output format (e.g.: `json`). | string  | `html`  |

For assets prefer the [`url` filter](#url-1).

_Examples:_

```twig
{{ url(page) }}
{{ url('page-id') }}
{{ url(menu.url) }}
{{ url('tags/'~tag) }}
```

### asset

Creates an asset (CSS, JavaScript, image, audio, etc.) from a file path, an URL or an array of files path (bundle).

```twig
{{ asset(path, {options}) }}
```

| Option         | Description                                         | Type    | Default |
| -------------- | --------------------------------------------------- | ------- | ------- |
| fingerprint    | Add the file content finger print to the file name. | boolean | `true`  |
| minify         | Compress file content (CSS or JavaScript).          | boolean | `true`  |
| filename       | File where to save content.                         | string  |         |
| ignore_missing | Do not stop build if file don't exists.             | boolean | `false` |

:::info
Refer to [assets configuration](4-Configuration.md#assets) to define the global behavior.  
:::

:::tip
Uses [filters](#filters) to manipulate assets.
:::

_Examples:_

```twig
{{ asset('styles.css')|minify }}
{{ asset('styles.scss')|inline }}
{{ asset('scripts.js', {minify: false}) }}
{{ asset('image.png', {fingerprint: false}) }}
{{ asset(['poole.css', 'hyde.css'], {filename: styles.css}) }}
{{ asset('https://cdnjs.cloudflare.com/ajax/libs/anchor-js/4.3.1/anchor.min.js') }}
```

#### Asset attributes

Assets created with the `asset()` function expose some useful attributes :

- `file`: filesystem path
- `path`: relative path
- `ext`: extension
- `type`: media type (e.g.: `image`)
- `subtype`: media sub type (e.g.: `image/jpeg`)
- `size`: size in octets
- `source`: content before compiling and/or minifying
- `content`: file content
- `integrity`: integrity hash
- `width`: image width
- `height`: image height
- `audio`: [Mp3Info](https://github.com/wapmorgan/Mp3Info#audio-information)

_Examples:_

```twig
{{ asset('image.png').width }}px
{{ asset('title.mp3').audio.duration|round }} min
{% set integrity = asset('styles.scss').integrity %}
```

### integrity

Returns the hash (`sha384`) of a file (from an asset or a path).

```twig
{{ integrity(asset) }}
```

Used for SRI ([Subresource Integrity](https://developer.mozilla.org/fr/docs/Web/Security/Subresource_Integrity)).

_Example:_

```twig
{{ integrity('styles.css') }}
```

### readtime

Returns read time of a text, in minutes.

```twig
{{ readtime(text) }}
```

_Example:_

```twig
{{ readtime(page.content) }} min
```

### getenv

Gets the value of an environment variable from its key.

```twig
{{ getenv(var) }}
```

_Example:_

```twig
{{ getenv('VAR') }}
```

## Sorts

Sorting collections (of pages, menus or taxonomies).

### sort_by_title

Sorts a collection by title (with [natural sort](https://en.wikipedia.org/wiki/Natural_sort_order)).

```twig
{{ <collection>|sort_by_title }}
```

### sort_by_date

Sorts a collection by date (most recent first).

```twig
{{ <collection>|sort_by_date }}
```

### sort_by_weight

Sorts a collection by weight (lighter first).

```twig
{{ <collection>|sort_by_weight }}
```

### sort

For more complex cases, you should use [Twig’s native `sort`](https://twig.symfony.com/doc/filters/sort.html).

_Example:_

```twig
{% set files = site.static|sort((a, b) => a.date|date('U') < b.date|date('U')) %}
```

## Filters

> Variables can be modified by [filters](https://twig.symfony.com/doc/filters/index.html). Filters are separated from the variable by a pipe symbol (`|`). Multiple filters can be chained. The output of one filter is applied to the next.

```twig
{{ page.title|truncate(25)|capitalize }}
```

### filter_by

Filters a pages collection by variable name/value.

```twig
{{ <collection>|filter_by(variable, value) }}
```

_Example:_

```twig
{{ pages|filter_by('section', 'blog') }}
```

### filter

For more complex cases, you should use [Twig’s native `filter`](https://twig.symfony.com/doc/filters/filter.html).

_Example:_

```twig
{% pages|filter(p => p.virtual == false and p.id not in ['page-1', 'page-2']) %}
```

### url

Returns the valid URL of a page, an asset or a path.

```twig
{{ <value>|url({options}) }}
```

| Option    | Description                                | Type    | Default |
| --------- | ------------------------------------------ | ------- | ------- |
| canonical | Prefixes the relative URL with `baseurl`.  | boolean | `false` |
| format    | Defines page output format (e.g.: `json`). | string  | `html`  |

_Examples:_

```twig
{{ page|url }}
{{ asset('styles.css')|url({canonical: true}) }}
{{ page|url({format: json}) }}
```

### markdown_to_html

Converts a Markdown string to HTML.

```twig
{{ markdown|markdown_to_html }}
```

```twig
{% apply markdown_to_html %}
{# Markdown here #}
{% endapply %}
```

_Examples:_

```twig
{% set markdown = '**This is bold text**' %}
{{ markdown|markdown_to_html }}
```

```twig
{% apply markdown_to_html %}
**This is bold text**
{% endapply %}
```

### json_decode

Converts a JSON string to an array.

```twig
{{ json|json_decode }}
```

_Example:_

```twig
{% set json = '{"foo": "bar"}' %}
{% set array = json|json_decode %}
{{ array.foo }}
```

### yaml_parse

Converts a YAML string to an array.

```twig
{{ yaml|yaml_parse }}
```

_Example:_

```twig
{% set yaml = 'key: value' %}
{% set array = yaml|yaml_parse %}
{{ array.key }}
```

### slugify

Converts a string to a slug.

```twig
{{ string|slugify }}
```

### excerpt

Truncates a string and appends suffix.

```twig
{{ string|excerpt(integer, suffix) }}
```

| Option | Description                                | Type    | Default |
| ------ | ------------------------------------------ | ------- | ------- |
| length | Truncates after this number of characters. | integer | 450     |
| suffix | Appends characters.                        | string  | `…`     |

_Examples:_

```twig
{{ variable|excerpt }}
{{ variable|excerpt(250, '...') }}
```

### excerpt_html

Reads characters before `<!-- excerpt -->` or `<!-- break -->` tag.

```twig
{{ string|excerpt_html(separator, capture) }}
```

| Option    | Description                                           | Type    | Default         |
| --------- | ----------------------------------------------------- | ------- | --------------- |
| separator | String to use as separator.                           | string  | `excerpt|break` |
| capture   | Capture characters `before` or `after` the separator. | string  | `before`        |

_Examples:_

```twig
{{ variable|excerpt_html }}
{{ variable|excerpt_html('excerpt|break', 'before') }}
```

### to_css

Compiles a [Sass](https://sass-lang.com) file to CSS.

```twig
{{ asset(path)|to_css }}
{{ path|to_css }}
```

_Examples:_

```twig
{{ asset('styles.scss')|to_css }}
{{ 'styles.scss'|to_css }} {# deprecated #}
```

### fingerprint

Add the file content finger print to the file name.

```twig
{{ asset(path)|fingerprint }}
{{ path|fingerprint }}
```

_Examples:_

```twig
{{ asset('styles.css')|fingerprint }}
{{ 'styles.css'|fingerprint }} {# deprecated #}
```

### minify

Minifying a CSS or a JavaScript file.

```twig
{{ asset(path)|minify }}
{{ path|minify }} {# deprecated #}
```

_Examples:_

```twig
{{ asset('styles.css')|minify }}
{{ 'styles.css'|minify }} {# deprecated #}
{{ asset('scripts.js')|minify }}
```

### minify_css

Minifying a CSS string.

```twig
{{ variable|minify_css }}
```

```twig
{% apply minify_css %}
{# CSS here #}
{% endapply %}
```

_Examples:_

```twig
{% set styles = 'some CSS here' %}
{{ styles|minify_css }}
```

```twig
<style>
{% apply minify_css %}
  html {
    background-color: #fcfcfc;
    color: #444;
  }
{% endapply %}
</style>
```

### minify_js

Minifying a JavaScript string.

```twig
{{ variable|minify_js }}
```

```twig
{% apply minify_js %}
{# JavaScript here #}
{% endapply %}
```

_Examples:_

```twig
{% set script = 'some JavaScript here' %}
{{ script|minify_js }}
```

```twig
<script>
{% apply minify_js %}
  var test = 'test';
  console.log(test);
{% endapply %}
</script>
```

### scss_to_css

Compiles a [Sass](https://sass-lang.com) string to CSS.

```twig
{{ variable|scss_to_css }}
```

```twig
{% apply scss_to_css %}
{# SCSS here #}
{% endapply %}
```

Alias: `sass_to_css`.

_Examples:_

```twig
{% set scss = 'some SCSS here' %}
{{ scss|scss_to_css }}
```

```twig
<style>
{% apply scss_to_css %}
  $color: #fcfcfc;
  div {
    color: lighten($color, 20%);
  }
{% endapply %}
</style>
```

### resize

Resizes an image to a specified with.

```twig
{{ asset(image_path)|resize(integer) }}
{{ <image_path>|resize(integer) }} {# deprecated #}
```

:::info
Aspect ratio is preserved, the original file is not altered and the resized version is stored in `/assets/thumbnails/<integer>/images/image.jpg`.
:::

_Example:_

```twig
{{ asset(page.image)|resize(300) }}
```

### dataurl

Returns the [data URL](https://developer.mozilla.org/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) of an image.

```twig
{{ asset(image_path)|dataurl }}
```

### inline

Outputs the content of an _Asset_.

```twig
{{ asset(path)|inline }}
```

_Example:_

```twig
{{ asset('styles.css')|inline }}
```

### html

Converts an asset into an HTML element.

```twig
{{ asset(path)|html({attributes, options}) }}
```

:::info
The `html` filter is available for images, CSS and JavaScript. The `attributes` and `options` parameters are optional.
:::

| Option     | Description                                     | Type  | Default |
| ---------- | ----------------------------------------------- | ----- | ------- |
| attributes | Adds `name="value"` couple to the HTML element. | array |         |
| options    | `{responsive: true}`: creates responsives images.<br>`{webp: true}`: creates WebP versions of the image.<br>`{preload: true}`: preloads CSS. | array |         |

_Examples:_

```twig
{{ asset('image.png')|html }}
```

```twig
{{ asset('image.jpg')|html({alt: 'Description', loading: 'lazy'}, {responsive: true, webp: true}) }}
```

```twig
{{ asset('styles.css')|html({media: print}) }}
```

```twig
{{ asset('styles.css')|html({title: 'Main theme'}, {preload: true}) }}
```

### preg_split

Splits a string into an array using a regular expression.

```twig
{{ string|preg_split(pattern, limit) }}
```

_Example:_

```twig
{% set headers = page.content|preg_split('/<h3[^>]*>/') %}
```

### preg_match_all

Performs a regular expression match and return the group for all matches.

```twig
{{ string|preg_match_all(pattern, group) }}
```

_Example:_

```twig
{% set tags = page.content|preg_match_all('/<[^>]+>(.*)<\\/[^>]+>/') %}
```

### hex_to_rgb

Converts a hexadecimal color to RGB.

```twig
{{ color|hex_to_rgb }}
```

## Localization

Cecil support [text translation](#text-translation) and [date localization](#date-localization).

### Text translation

Uses the `trans` _tag_ or _filter_ to translate texts in templates.

```twig
{% trans with variables into locale %}{% endtrans %}
```

```twig
{{ message|trans(variables = []) }}
```

#### Examples

```twig
{% trans %}Hello World!{% endtrans %}
```

```twig
{{ message|trans }}
```

Include variables:

```twig
{% trans with {'%name%': 'Arnaud'} %}Hello %name%!{% endtrans %}
```

```twig
{{ message|trans({'%name%': 'Arnaud'}) }}
```

Force locale:

```twig
{% trans into 'fr_FR' %}Hello World!{% endtrans %}
```

Pluralize:

```twig
{% trans with {'%count%': 42}%}{0}I don't have apples|{1}I have one apple|]1,Inf[I have %count% apples{% endtrans %}
```

#### Translation files

Translation files must be named `messages.<locale>.<format>` and stored in the [`translations`](4-Configuration.md#translations) directory.  
Cecil supports `yaml` and `mo` (Gettext) file [formats by default](4-Configuration.md#translations).

_Example:_

```plaintext
<mywebsite>
└─ translations
   ├─ messages.fr_FR.mo   <- Machine Object format
   └─ messages.fr_FR.yaml <- Yaml format
```

:::tip
[_Poedit Pro_](https://poedit.net/pro) (~ 35 €) is recommended to easily translate your templates.
:::

:::important
Be carreful about the cache ([enabled by default](4-Configuration.md#cache)) when you update translations files.  Cache can be cleared with with the following command: `php cecil.phar cache:clear:translations`.
:::

### Date localization

Uses the Twig [`format_date`](https://twig.symfony.com/doc/3.x/filters/format_date.html) filter to localize a date in templates.

```twig
{{ page.date|format_date('long') }}
```

:::important
If you want to use the `format_date` filter **with other locales than "en"**, you should [install the intl PHP extension](https://php.net/intl.setup).
:::

## Built-in templates

Cecil comes with a set of [built-in templates](https://github.com/Cecilapp/Cecil/tree/master/resources/layouts).

### Default templates

[`_default/page.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/page.html.twig)
:   A very simple default main template with a clean CSS.

[`_default/list.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/list.html.twig)
:   A pages list with pagination.

[`_default/vocabulary.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/vocabulary.html.twig)
:   A basic list of all terms of a vocabulary.

[`_default/list.atom.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/list.atom.twig)
:   A clean Atom feed for sections.

[`_default/list.rss.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/list.rss.twig)
:   A clean RSS feed for sections.

### Utility templates

[`robots.txt.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/robots.txt.twig)
:   The `robots.txt` template: allow all pages except 404, with a reference to the XML sitemap.

[`sitemap.xml.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/sitemap.xml.twig)
:   The `sitemap.xml` template: list all pages sorted by date.

### Partial templates

[`partials/pagination.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/pagination.html.twig)
:   A simple pagination for list templates with "Older" and "Newer" links.

[`partials/metatags.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/metatags.html.twig)
:   All metatags in one template: title, description, canonical, open-graph, twitter card, etc. See [configuration](4-Configuration.md#metatags).

[`partials/googleanalytics.js.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/googleanalytics.js.twig)
:   Google Analytics traking script. See [configuration](4-Configuration.md#googleanalytics).

[`partials/languages.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/languages.html.twig)
:   Switcher between [languages](4-Configuration.md#languages).

[`partials/navigation.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/navigation.html.twig)
:   Menu navigation.
