<!--
description: "Works with templates and use variables."
date: 2021-05-07
alias: documentation/layouts
-->

# Templates

Cecil use [Twig](https://twig.symfony.com) as template engine, so refer to the [official documentation](https://twig.symfony.com/doc/templates.html) for basic usage.

## Example

```twig
<h1>{{ page.title }} | {{ site.title }}</h1>
<span>{{ page.date|date('j M Y') }}</span>
<p>{{ page.content }}</p>
<p>{{ page.customvar }}</p>
```

## Files organization

Templates files are stored in `layouts/`.

```plaintext
<mywebsite>
├─ content
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

Cecil searches for the best layout to use, for a given page, in a defined order.

In most of cases **you don’t need to specify the template** in layout variable: Cecil selects the most appropriate template for you.

_Vocabulary:_

- `<format>`: output format (ie: `html`)
- `<layout>`: value of variable `layout` set in front matter (ie: `layout: post`)
- `<section>`: page’s *Section* (ie: `blog`)

### _homepage_

1. `<layout>.<format>.twig`
2. `index.<format>.twig`
3. `list.<format>.twig`
4. `_default/index.<format>.twig`
5. `_default/list.<format>.twig`
6. `_default/page.<format>.twig`

### _page_

1. `<section>/<layout>.<format>.twig`
2. `<layout>.<format>.twig`
3. `<section>/page.<format>.twig`
4. `page.<format>.twig`
5. `_default/page.<format>.twig`

### _section_

1. `<section>/list.<format>.twig`
2. `section/<section>.<format>.twig`
3. `_default/section.<format>.twig`
4. `_default/list.<format>.twig`

### _vocabulary_

1. `taxonomy/<plural>.<format>.twig`
2. `_default/vocabulary.<format>.twig`

### _term_

1. `taxonomy/<term>.<format>.twig`
2. `_default/term.<format>.twig`
3. `_default/list.<format>.twig`

## Variables

You can use variables from different scopes (site, page, etc.) in templates.

### site

Contains all variables from the configuration file (`config.yml`) and some built-in variables.

#### Configuration variables

_Example:_

```ymal
title: "My amazing website!"
```

Is printed in a template through:

```twig
{{ site.title }}
```

#### Built-in variables

| Variable              | Description                                           |
| --------------------- | ----------------------------------------------------- |
| `site.home`           | ID of the home page.                                  |
| `site.pages`          | Collection of pages, in the current language.         |
| `site.pages.showable` | `site.pages` with "showable" pages only (published, not virtual/redirect/excluded). |
| `site.allpages`       | Collection of pages, regardless of their translation. |
| `site.taxonomies`     | Collection of vocabularies.                           |
| `site.time`           | [*Timestamp*](https://wikipedia.org/wiki/Unix_time) of the last generation. |

#### site.menus

Loop on `site.menus.<key>` to get each entry of the `<key>` menu collection (ie: `main`).

| Variable         | Description                                      |
| ---------------- | ------------------------------------------------ |
| `<entry>.name`   | Menu entry name.                                 |
| `<entry>.url`    | Menu entry URL (relative or absolute).           |
| `<entry>.weight` | Menu entry weight (useful to sort menu entries). |

#### site.language

Informations about the current language.

| Variable               | Description                                                  |
| ---------------------- | ------------------------------------------------------------ |
| `site.language`        | Language code (ie: `en`).                                    |
| `site.language.name`   | Language name (ie: `English`).                               |
| `site.language.locale` | Language [locale code](configuration/locale-codes.md) (ie: `en_EN`). |
| `site.language.weight` | Language position in the `languages` list.                   |

**Note:**

You can retrieve `name`, `locale` and `weight` of a language different of the current one by passing its code as a parameter, ie: `site.language.name('fr')`.

#### site.static

The static files collection can be accessed via `site.static` (if [*static load*](4.configuration.md#static) is enabled).

Each file exposes the following properties:

- `path`: relative path (ie: `/images/img-1.jpg`)
- `date`: creation date (*timestamp*)
- `updated`: modification date (*timestamp*)
- `name`: name (ie: `img-1.jpg`)
- `basename`: name without extension (ie: `img-1`)
- `ext`: extension (ie: `jpg`)

#### site.data

A data collection can be accessed via `site.data.<filename>` (without file extension).

_Examples:_

- `data/authors.yml` : `site.data.authors`
- `data/galleries/gallery-1.json` : `site.data.galleries.gallery-1`

### page

Contains variables of the current _Page_ **and** those set in the front matter.

| Variable              | Description                                           | Example                    |
| --------------------- | ----------------------------------------------------- | -------------------------- |
| `page.id`             | Unique identifier.                                    | `blog/post-1`              |
| `page.title`          | File name (without extension).                        | `Post 1`                   |
| `page.date`           | File creation date.                                   | _DateTime_                 |
| `page.updated`        | File modification date.                               | _DateTime_                 |
| `page.body`           | File body.                                            | _Markdown_                 |
| `page.content`        | File body converted in HTML.                          | _HTML_                     |
| `page.section`        | File root folder (_slugified_).                       | `blog`                     |
| `page.path`           | File path (_slugified_).                              | `blog/post-1`              |
| `page.slug`           | File name (_slugified_).                              | `post-1`                   |
| `page.tags`           | Array of _tags_.                                      | `[Tag 1, Tag 2]`           |
| `page.categories`     | Array of _categories_.                                | `[Category 1, Category 2]` |
| `page.pages`          | Collection of all subpages.                           | _Collection_               |
| `page.pages.showable` | `page.pages` with "showable" pages only.              | _Collection_               |
| `page.type`           | `homepage`, `page`, `section`, `vocabulary` or `term` | `page`                     |
| `page.filepath`       | File system path.                                     | `Blog/Post 1.md`           |
| `page.translations`   | Collection of translated pages.                       | _Collection_               |

#### page.<prev/next>

Navigation between pages of a _Section_.

| Variable                 | Description                                           |
| ------------------------ | ----------------------------------------------------- |
| `page.<prev/next>.id`    | ID of the previous / next page (ie: `blog/post-2`).   |
| `page.<prev/next>.path`  | Path of the previous / next page (ie: `blog/post-2`). |
| `page.<prev/next>.title` | Title of the previous / next page (ie: `Post 2`).     |

#### page.pagination

Pagination for homepage, section pages, and taxonomies.

| Variable                      | Description                 |
| ----------------------------- | --------------------------- |
| `page.pagination.pages`       | Paginated pages collection. |
| `page.pagination.totalpages`  | Paginated total pages.      |
| `page.pagination.current`     | Number of the current page. |
| `page.pagination.count`       | Number of the last page.    |
| `page.pagination.links.self`  | Path of the current page.   |
| `page.pagination.links.first` | Path of the first page.     |
| `page.pagination.links.prev`  | Path of the previous page.  |
| `page.pagination.links.next`  | Path of the next page.      |
| `page.pagination.links.last`  | Path of the last page.      |

#### Taxonomy

Variables available in _vocabulary_ and _term_ layouts.

##### Vocabulary

| Variable        | Description                      |
| --------------- | -------------------------------- |
| `page.plural`   | Vocabulary name (plural form).   |
| `page.singular` | Vocabulary name (singular form). |
| `page.terms`    | List of terms (_Collection_).    |

##### Term

| Variable     | Description                                |
| ------------ | ------------------------------------------ |
| `page.term`  | Term ID.                                   |
| `page.pages` | List of pages in this term (_Collection_). |

### cecil

| Variable          | Description                                         |
| ----------------- | --------------------------------------------------- |
| `cecil.url`       | URL to the official website.                        |
| `cecil.version`   | Cecil current version.                              |
| `cecil.poweredby` | Print `Cecil v%s` with `%s` is the current version. |

## Functions

Functions can be called to generate content. Functions are called by their name followed by parentheses (`()`) and may have arguments.

### url

Turns a _Page_, a _Page_ ID or a relative path into an URL.

```twig
{{ url(Page|page-id|path,{options}) }}
```

| Option    | Description                              | Type    | Default |
| --------- | ---------------------------------------- | ------- | ------- |
| canonical | Prefixes with `baseurl`.                 | boolean | `false` |
| format    | Defines Page output format (ie: `json`). | string  | `html`  |

Note: Prefer the [`url` filter](#url-1) for assets.

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
{{ asset(path|url|[paths],{options}) }}
```

| Option         | Description                                         | Type    | Default |
| -------------- | --------------------------------------------------- | ------- | ------- |
| fingerprint    | Add the file content finger print to the file name. | boolean | `true`  |
| minify         | Compress file content (CSS and JavaScript).         | boolean | `true`  |
| filename       | File to save content.                               | string  |         |
| ignore_missing | Ignore if file don't exists.                        | boolean | `false` |

See [assets configuration](4.Configuration.md#assets) to define the global behavior.

Uses [filters](#filters) to manipulate assets:

```twig
{{ asset('styles.scss')|html }}
{{ asset('styles.scss')|inline }}
```

_Examples:_

```twig
{{ asset('styles.css')|minify }}
{{ asset('scripts.js',{minify:false}) }}
{{ asset('image.png',{fingerprint:false}) }}
{{ asset(['poole.css','hyde.css'],{filename:styles.css}) }}
{{ asset('https://cdnjs.cloudflare.com/ajax/libs/anchor-js/4.3.1/anchor.min.js') }}
```

#### Attributes

- `file`: filesystem path
- `path`: relative path
- `ext`: extension
- `type`: media type (ie: `image`)
- `subtype`: media sub type (ie: `image/jpeg`)
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

Returns the hash (`sha384`) of a file.

```twig
{{ integrity(Asset|path) }}
```

Used for SRI ([Subresource Integrity](https://developer.mozilla.org/fr/docs/Web/Security/Subresource_Integrity)).

_Example:_

```twig
{{ integrity('styles.css') }}
```

### readtime

Returns read time, in minutes.

```twig
{{ readtime(string) }}
```

_Example:_

```twig
{{ readtime(page.content) }} min
```

### getenv

Gets the value of an environment variable.

```twig
{{ getenv(key) }}
```

_Example:_

```twig
{{ getenv('VAR') }}
```

## Sorts

Sorting collections.

### sort_by_title

Sorts a pages collection by title (with [natural sort](https://en.wikipedia.org/wiki/Natural_sort_order)).

```twig
{{ <collection>|sort_by_title }}
```

### sort_by_date

Sorts a pages collection by date (most recent first).

```twig
{{ <collection>|sort_by_date }}
```

### sort_by_weight

Sorts a pages or menu collection by weight (lighter first).

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

Filtering collections.

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

Turns a Page, an Asset or a path into an URL.

```twig
{{ <Page|Asset|path>|url({options}) }}
```

| Option    | Description                              | Type    | Default |
| --------- | ---------------------------------------- | ------- | ------- |
| canonical | Prefixes with `baseurl`.                 | boolean | `false` |
| format    | Defines Page output format (ie: `json`). | string  | `html`  |

_Examples:_

```twig
{{ page|url }}
{{ asset('styles.css')|url({canonical:true}) }}
{{ page|url({format:json}) }}
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
{% set json = '{"foo":"bar"}' %}
{% set array = json|json_decode %}
{{ array.foo }}
```

### slugify

Converts a string to a slug.

```twig
{{ string|slugify }}
```

### excerpt

Truncates a string and appends suffix.

```twig
{{ string|excerpt(integer,suffix) }}
```

| Option | Description                                | Type    | Default |
| ------ | ------------------------------------------ | ------- | ------- |
| length | Truncates after this number of characters. | integer | 450     |
| suffix | Appends characters.                        | string  | `…`     |

_Examples:_

```twig
{{ variable|excerpt }}
{{ variable|excerpt(250,'...') }}
```

### excerpt_html

Reads characters before `<!-- excerpt -->` or `<!-- break -->`.

```twig
{{ string|excerpt_html }}
```

### preg_split

Split a string into an array using a regular expression.

```twig
{{ string|preg_split(pattern, limit) }}
```

_Example:_

```twig
{% set headers = page.content|preg_split('/<h3[^>]*>/') %}
```

### preg_match_all

Perform a regular expression match and return the group for all matches.

```twig
{{ string|preg_match_all(pattern, group) }}
```

_Example:_

```twig
{% set tags = page.content|preg_match_all('/<[^>]+>(.*)<\\/[^>]+>/') %}
```

### to_css

Compiles a [Sass](https://sass-lang.com) file (to CSS).

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

Turns an asset into an HTML element.

```twig
{{ asset(path)|html({attributes, options}) }}
```

| Option     | Description                                     | Type  | Default |
| ---------- | ----------------------------------------------- | ----- | ------- |
| attributes | Adds `name="value"` couple to the HTML element. | array |         |
| options    | Rendering format. Preload: `{preload:true}`.    | array |         |

Available for CSS, JavaScript and image file.

_Examples:_

```twig
{{ asset('image.png')|html({title:'Title',alt:'Alternative'}) }}
```

```twig
{{ asset('styles.css')|html({},{preload:true}) }}
```

### minify_css

Minifying CSS.

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

Minifying JavaScript.

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

Compiles a [Sass](https://sass-lang.com) (to CSS).

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

> *Experimental*

```twig
{{ <image_path>|resize(integer) }}
```

Ratio is preserved, the original file is not altered and the resized version is stored in `/images/thumbs/<resize>/image.ext`.

_Example:_

```twig
{{ page.image|resize(300) }}
```

## Localization

Cecil support **text translation** and **date localization** through [*Twig Extensions*](https://twig-extensions.readthedocs.io).

### Text translation

Uses the `trans` tag or filter to translate a text in templates.

```twig
{% trans "Publication date:" %}
```

```twig
{% trans variable %}
```

```twig
{% trans %}
    Hey {{ name }}, I have one apple.
{% plural apple_count %}
    Hey {{ name }}, I have {{ count }} apples.
{% endtrans %}
```

```twig
{{ variable|default(default_value|trans) }}
```

Translation files (`.mo`) must be stored in the right directory of your project:

```plaintext
<mywebsite>
└─ locale
   └─ fr_FR              <- Language locale
      └─ LC_MESSAGES
         ├─ messages.po  <- Translation file
         └─ messages.mo  <- Compiled translation file
```

I recommends [*Poedit Pro*](https://poedit.net/pro) to easily translate your templates.

[*Gettext PHP extension*](https://www.php.net/gettext) is required. See the [i18n Extension documentation](https://twig-extensions.readthedocs.io/en/latest/i18n.html).

### Date localization

Uses the `localizeddate` filter to localize a date in templates.

```twig
{{ page.date|localizeddate('long', 'none') }}
```

[*Intl PHP extension*](https://www.php.net/manual/book.intl.php) is required. See the [Intl Extension documentation](https://twig-extensions.readthedocs.io/en/latest/intl.html).

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

### Component templates

[`partials/pagination.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/pagination.html.twig)
:   A simple pagination component for list templates with "Older" and "Newer" links.

[`partials/metatags.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/metatags.html.twig)
:   All metatags in one component: title, description, canonical, open-graph, twitter card, etc. See [configuration](4.Configuration.md#metatags).

[`partials/googleanalytics.js.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/googleanalytics.js.twig)
:   Google Analytics traking script. See [configuration](4.Configuration.md#googleanalytics).

[`partials/languages.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/languages.html.twig)
:   Switcher between [languages](4.Configuration.md#languages).
