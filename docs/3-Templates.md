<!--
description: "Working with layouts, templates and components."
date: 2021-05-07
updated: 2026-01-14
alias: documentation/layouts
-->
# Templates

Cecil is powered by the [Twig](https://twig.symfony.com) template engine, so please refer to the **[official documentation](https://twig.symfony.com/doc/templates.html)** to learn how to use it.

## Example

```twig
{# this is a template example #}
<h1>{{ page.title }} - {{ site.title }}</h1>
<span>{{ page.date|date('j M Y') }}</span>
<p>{{ page.content }}</p>
<ul>
{% for tag in page.tags %}
  <li>{{ tag }}</li>
{% endfor %}
</ul>
```

- `{# #}`: adds comments
- `{{ }}`: outputs content of variables or expressions
- `{% %}`: executes statements, like loop (`for`), condition (`if`), etc.
- `|filter()`: filters or formats content

## Files organization

### Kinds of templates

There is three kinds of templates, **_layouts_**, **_components_** and **_others templates_**: _layouts_ are used to render [pages](2-Content.md#pages), and each of them can [include templates](https://twig.symfony.com/doc/templates.html#including-other-templates) and [components](#components).

### Naming convention

Templates files are stored in the `layouts/` directory and must be named according to the following convention:

```plaintext
layouts/(<section>/)<type>|<layout>.<format>(.<language>).twig
```

`<section>` (optional)
:  The section of the page (e.g.: `blog`).

`<type>`
:  The page type: `home` (or `index`) for _homepage_, `list` for _list_, `page` for _page_, etc. (See [_Lookup rules_](#lookup-rules) for details).

`<layout>` (optional)
:  The custom layout name defined in the [front matter](2-Content.md#front-matter) of the page (e.g.: `layout: my-layout`).

`<language>` (optional)
:  The language of the page (e.g.: `fr`).

`<format>`
:  The [output format](4-Configuration.md#output-formats) of the rendered page (e.g.: `html`, `rss`, `json`, `xml`, etc.).

`.twig`
:  The mandatory Twig file extension.

_Examples:_

```plaintext
layouts/home.html.twig       # `type` is "homepage"
layouts/page.html.twig       # `type` is "page"
layouts/page.html.fr.twig    # `type` is "page" and `language` is "fr"
layouts/my-layout.html.twig  # `layout` is "my-layout"
layouts/blog/list.html.twig  # `section` is "blog"
layouts/blog/list.rss.twig   # `section` is "blog" and `format` is "rss"
```

```plaintext
<mywebsite>
├─ ...
├─ layouts                  <- Layouts and templates
|  ├─ my-layout.html.twig
|  ├─ index.html.twig       <- Used by type "homepage"
|  ├─ list.html.twig        <- Used by types "homepage", "section" and "term"
|  ├─ list.rss.twig         <- Used by types "homepage", "section" and "term", for RSS output format
|  ├─ page.html.twig        <- Used by type "page"
|  ├─ ...
|  ├─ _default              <- Default layouts, that can be easily extended
|  |  ├─ list.html.twig
|  |  ├─ page.html.twig
|  |  └─ ...
|  └─ partials
|     ├─ footer.html.twig   <- Included template
|     └─ ...
└─ themes
   └─ <theme>
      └─ layouts            <- Theme layouts and templates
         └─ ...
```

### Built-in templates

Cecil comes with a set of [built-in templates](https://github.com/Cecilapp/Cecil/tree/master/resources/layouts).

:::tip
If you need to modify built-in templates, you can easily extract them via the following command: they will be copied in the `layouts` directory of your site.

```bash
php cecil.phar util:templates:extract
```

:::

#### Default templates

[`_default/page.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/page.html.twig)
:   A simple main template with a clean CSS.

[`_default/list.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/list.html.twig)
:   A pages list with (an optional) pagination.

[`_default/list.atom.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/list.atom.twig)
:   An Atom feed.

[`_default/list.rss.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/list.rss.twig)
:   A RSS feed.

[`_default/vocabulary.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/vocabulary.html.twig)
:   A simple list of all terms of a vocabulary.

[`_default/404.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/404.html.twig)
:   A basic error 404 ("Page not found") template.

[`_default/sitemap.xml.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/sitemap.xml.twig)
:   The [`sitemap.xml`](https://www.sitemaps.org) template: list of all pages sorted by date.

[`_default/robots.txt.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/robots.txt.twig)
:   The [`robots.txt`](https://en.wikipedia.org/wiki/Robots.txt) template: allow all pages except 404, and add a reference to the sitemap.

[`_default/redirect.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/redirect.html.twig)
:   The redirect template.

#### Partial templates

[`partials/navigation.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/navigation.html.twig)
:   A main menu navigation.

[`partials/paginator.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/paginator.html.twig)
:   A simple paginated navigation for list templates with "Previous" and "Next" links.

[`partials/metatags.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/metatags.html.twig)
:   All metatags in one template: title, description, canonical, open-graph, twitter card, etc. See [_metatags_ configuration](4-Configuration.md#metatags).

[`partials/languages.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/languages.html.twig)
:   A basic [languages](4-Configuration.md#languages) switcher.

## Lookup rules

In most of cases **you don’t need to specify the layout**: Cecil selects the most appropriate layout, according to the page _type_.

### Homepage template lookup

For example, the HTML output of _home page_ (`index.md`) will be rendered:

1. with `my-layout.html.twig` if the `layout` variable is set to "my-layout" (in the front matter)
2. if not, with `home.html.twig` if the file exists
3. if not, with `index.html.twig` if the file exists
4. if not, with `list.html.twig` if the file exists
5. etc.

All rules are detailed below, for each page type, in the priority order.

### Type _homepage_

1. `<layout>.<format>.twig`
2. `index.<format>.twig`
3. `home.<format>.twig`
4. `list.<format>.twig`
5. `_default/<layout>.<format>.twig`
6. `_default/index.<format>.twig`
7. `_default/home.<format>.twig`
8. `_default/list.<format>.twig`
9. `_default/page.<format>.twig`

### Type _page_

1. `<section>/<layout>.<format>.twig`
2. `<layout>.<format>.twig`
3. `<section>/page.<format>.twig`
4. `_default/<layout>.<format>.twig`
5. `page.<format>.twig`
6. `_default/page.<format>.twig`

### Type _section_

1. `<layout>.<format>.twig`
2. `<section>/index.<format>.twig`
3. `<section>/list.<format>.twig`
4. `section/<section>.<format>.twig`
5. `_default/section.<format>.twig`
6. `list.<format>.twig`
7. `_default/list.<format>.twig`

### Type _vocabulary_

1. `taxonomy/<plural>.<format>.twig`
2. `vocabulary.<format>.twig`
3. `_default/vocabulary.<format>.twig`

### Type _term_

1. `taxonomy/<term>.<format>.twig`
2. `taxonomy/<singular>.<format>.twig`
3. `term.<format>.twig`
4. `_default/term.<format>.twig`
5. `_default/list.<format>.twig`

:::info
Most of those layouts are available by default, see [built-in templates](#built-in-templates).
:::

## Variables

> The application passes variables to the templates for manipulation in the template. Variables may have attributes or elements you can access, too.  
> Use a dot (.) to access attributes of a variable: `{{ foo.bar }}`

You can use variables from different scopes: [`site`](#site), [`page`](#page), [`cecil`](#cecil).

### site

The `site` variable contains all variables from the configuration and built-in variables.

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
| `site.page('id')`     | A page with the given ID.                              |
| `site.allpages`       | Collection of all pages, regardless of their language. |
| `site.taxonomies`     | Collection of vocabularies.                            |
| `site.time`           | [_Timestamp_](https://wikipedia.org/wiki/Unix_time) of the last generation. |
| `site.debug`          | Debug mode: `true` or `false`.                         |

:::important
In some case you can encounter conflicts between configuration and built-in variables (e.g.: `pages.default` configuration), so you can use `config.<variable>` (with `<variable>` is the name/path of the variable) to access directly to the raw configuration.

Example:

```twig
{{ config.pages.default.sitemap.priority }}
```

:::

#### site.menus

Loop on `site.menus.<menu>` to get each entry of the `<menu>` collection (e.g.: `main`).

| Variable         | Description                                 |
| ---------------- | ------------------------------------------- |
| `<entry>.name`   | Entry name.                                 |
| `<entry>.url`    | Entry URL.                                  |
| `<entry>.weight` | Entry weight (useful to sort menu entries). |

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

The static files collection can be accessed via `site.static` if the [_static load_](4-configuration.md#static-load) is enabled.

Each file exposes the following properties:

- `path`: relative path (e.g.: `/images/img-1.jpg`)
- `date`: creation date (_timestamp_)
- `updated`: modification date (_timestamp_)
- `name`: name (e.g.: `img-1.jpg`)
- `basename`: name without extension (e.g.: `img-1`)
- `ext`: extension (e.g.: `jpg`)
- `type`: media type (e.g.: `image`)
- `subtype`: media sub type (e.g.: `image/jpeg`)
- `exif`: image EXIF data (_array_)
- `audio`: [Mp3Info](https://github.com/wapmorgan/Mp3Info#audio-information) object
- `video`: array of basic video information (duration in seconds, width and height)

#### site.data

A data collection can be accessed via `site.data.<filename>` (without file extension).

_Examples:_

- `data/authors.yml` : `site.data.authors`
- `data/authors.fr.yml` : `site.data.authors` (if `site.language` = "fr")
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

_Paginator_ help you to build a navigation for list's pages: homepage, sections, and taxonomies.

| Variable                     | Description                         |
| ---------------------------- | ----------------------------------- |
| `page.paginator.pages`       | Pages Collection.                   |
| `page.paginator.pages_total` | Number total of pages.              |
| `page.paginator.count`       | Number of paginator's pages.        |
| `page.paginator.current`     | Position index of the current page. |
| `page.paginator.links.first` | Page ID of the first page.          |
| `page.paginator.links.prev`  | Page ID of the previous page.       |
| `page.paginator.links.self`  | Page ID of the current page.        |
| `page.paginator.links.next`  | Page ID of the next page.           |
| `page.paginator.links.last`  | Page ID of the last page.           |
| `page.paginator.links.path`  | Page ID without the position index. |

:::important
Because links entries are Page ID you must use the `url()` function to create working links.  
e.g: `{{ url(page.paginator.links.next) }}`
:::

_Example:_

```twig
{% if page.paginator %}
<div>
  {% if page.paginator.links.prev is defined %}
  <a href="{{ url(page.paginator.links.prev) }}">Previous</a>
  {% endif %}
  {% if page.paginator.links.next is defined %}
  <a href="{{ url(page.paginator.links.next) }}">Next</a>
  {% endif %}
</div>
{% endif %}
```

_Example:_

```twig
{% if page.paginator %}
<div>
  {% for paginator_index in 1..page.paginator.count %}
    {% if paginator_index != page.paginator.current %}
      {% if paginator_index == 1 %}
  <a href="{{ url(page.paginator.links.first) }}">{{ paginator_index }}</a>
      {% else %}
  <a href="{{ url(page.paginator.links.path ~ '/' ~ paginator_index) }}">{{ paginator_index }}</a>
      {% endif %}
    {% else %}
  {{ paginator_index }}
    {% endif %}
  {% endfor %}
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

Creates a valid URL for a page, a menu entry, an asset, a page ID or a path.

```twig
{{ url(value, {options}) }}
```

| Option    | Description                                                                | Type    | Default |
| --------- | -------------------------------------------------------------------------- | ------- | ------- |
| canonical | Prefix URL with [`baseurl`](4-Configuration.md#baseurl) or use [`canonical.url`](4-Configuration.md#metatags-options) if exists. | boolean | `false` |
| format    | Defines page [output format](4-Configuration.md#output-formats) (e.g.: `json`).   | string  | `html`  |
| language  | Defines page [language](4-Configuration.md#language) (e.g.: `fr`). | string  | null    |

_Examples:_

```twig
{# page #}
{{ url(page) }}
{{ url(page, {canonical: true}) }}
{{ url(page, {format: json}) }}
{{ url(page, {language: fr}) }}
{# menu entry #}
{{ url(site.menus.main.about) }}
{# asset #}
{{ url(asset('styles.css')) }}
{# page ID #}
{{ url('page-id') }}
{# path #}
{{ url('about-me/') }}
{{ url('tags/' ~ tag) }}
```

:::info
For convenience the `url` function is also available as a filter:

```twig
{# page #}
{{ page|url }}
{{ page|url({canonical: true, format: json, language: fr}) }}
{# asset #}
{{ asset('styles.css')|url }}
```

:::

### asset

An asset is a resource useable in templates, like CSS, JavaScript, image, audio, video, etc.

The `asset()` function creates an _asset_ object from a file path, an array of files path (bundle) or an URL (remote file), and are processed (minified, fingerprinted, etc.) according to the [configuration](4-Configuration.md#assets).

Resource files must be stored in the `assets/` (or `static/`)  directory.

```twig
{{ asset(path, {options}) }}
```

| Option         | Description                                     | Type    | Default  |
| -------------- | ----------------------------------------------- | ------- | -------- |
| filename       | Save bundle to a custom file name.              | string  | `styles.css` or `scripts.js` |
| leading_slash  | Add a leading slash to the $path.               | string  | `true`   |
| ignore_missing | Do not stop build if file is not found.         | boolean | `false`  |
| fingerprint    | Add content hash to the file name.              | boolean | `true`   |
| minify         | Compress CSS or JavaScript.                     | boolean | `true`   |
| optimize       | Compress image.                                 | boolean | `false`  |
| fallback       | Load a local asset if remote file is not found. | string  | ``       |
| useragent      | User agent key (from [Assets configuration](4-Configuration.md#assets-remote-useragent)). | string | `default`|

:::tip
You can use [filters](#filters) to manipulate assets.
:::

:::info
You don't need to clear the [cache](#cache) after modifying an asset: the cache is automatically cleared when the file is modified or when the file name is changed.
:::

_Examples:_

```twig
{# CSS #}
{{ asset('styles.css') }}
{# CSS bundle #}
{{ asset(['poole.css', 'hyde.css'], {filename: styles.css}) }}
{# JavaScript #}
{{ asset('scripts.js') }}
{# image #}
{{ asset('image.jpeg') }}
{# audio #}
{{ asset('audio.mp3') }}
{# video #}
{{ asset('video.mp4') }}
{# remote file #}
{{ asset('https://cdnjs.cloudflare.com/ajax/libs/anchor-js/4.3.1/anchor.min.js', {minify: false}) }}
{# with filter #}
{{ asset('styles.css')|minify }}
{{ asset('styles.scss')|to_css|minify }}
```

#### Asset attributes

Assets created with the `asset()` function expose some useful attributes.

Common:

- `file`: filesystem path
- `missing`: `true` if file is not found but missing is allowed
- `path`: public path
- `ext`: file extension
- `type`: media type (e.g.: `image`)
- `subtype`: media sub type (e.g.: `image/jpeg`)
- `size`: size in octets
- `content`: file content
- `hash`: file content hash (md5)
- `dataurl`: data URL encoded in Base64
- `integrity`: integrity hash

Remote:

- `url`: URL of the remote file

Bundle:

- `files`: array of filesystem path in case of a bundle

Image:

- `width`: image width in pixels
- `height`: image height in pixels
- `exif`: image EXIF data as array

Audio:

- `duration`: duration in seconds.microseconds
- `bitrate`: bitrate in bps
- `channel`: 'stereo', 'dual_mono', 'joint_stereo' or 'mono'

Video:

- `duration`: duration in seconds
- `width`: width in pixels
- `height`: height in pixels

_Examples:_

```twig
{# image width in pixels #}
{{ asset('image.png').width }}px
{# photo's date in seconds #}
{{ asset('photo.jpeg').exif.EXIF.DateTimeOriginal|date('U') }}
{# audio duration in seconds #}
{{ asset('song.mp3').duration|round }} s
{# video duration in seconds #}
{{ asset('movie.mp4').duration|round }} s
{# file integrity hash #}
{% set integrity = asset('styles.scss').integrity %}
```

### integrity

Creates the hash (`sha384`) of a file (from an asset or a path).

```twig
{{ integrity(asset) }}
```

Used for SRI ([Subresource Integrity](https://developer.mozilla.org/fr/docs/Web/Security/Subresource_Integrity)).

_Example:_

```twig
{{ integrity('styles.css') }}
{# sha384-oGDH3qCjzMm/vI+jF4U5kdQW0eAydL8ZqXjHaLLGduOsvhPRED9v3el/sbiLa/9g #}
```

### html

Creates an HTML element from an asset (or an array of assets with custom attributes).

```twig
{{ html(asset, {attributes}, {options}) }}
{# dedicated functions for each common type of asset #}
{{ css(asset) }}
{{ js(asset) }}
{{ image(asset) }}
{{ audio(asset) }}
{{ video(asset) }}
```

| Option     | Description                                     | Type  |
| ---------- | ----------------------------------------------- | ----- |
| attributes | Adds `name="value"` couple to the HTML element. | array |
| options    | `{preload: boolean}`: preloads.<br>For images:<br>`{formats: array}`: adds alternative formats.<br>`{responsive: bool|string}`: adds responsive images (based on `width` or pixels `density`). | array |

:::warning
Since version ++8.42.0++, the `html` function replace the deprecated `html` filter.
:::

:::tip
You can define a global default behavior of images options (`formats` and `responsive`) through the [layouts configuration](4-Configuration.md#layouts-images).
:::

_Examples:_

```twig
{# CSS with an attribute #}
{{ html(asset('print.css'), {media: 'print'}) }}
{# CSS with an attribute and an option #}
{{ html(asset('styles.css'), {title: 'Main theme'}, {preload: true}) }}
{# Array of assets with media query #}
{{ html([
  {asset: asset('css/style.css')},
  {asset: asset('css/style-dark.css'), attributes: {media: '(prefers-color-scheme: dark)'}}
]) }}
{# JavaScript #}
{{ html(asset('script.js')) }}
{# image without specific attributes nor options #}
{{ html(asset('image.png')) }}
{# image with specific attributes, responsive images and alternative formats #}
{{ html(asset('image.jpg'), {alt: 'Description', loading: 'lazy'}, {responsive: true, formats: ['avif', 'webp']}) }}
{# image with responsive pixels density images #}
{{ html(asset('image.jpg'), options={responsive: 'density'}, attributes={width: 256}) }}
{# Audio #}
{{ html(asset('audio.mp3')) }}
{# Video #}
{{ html(asset('video.mp4')) }}
```

:::info
For convenience the `html` function stay available as a filter (but is considered as deprecated):

```twig
{{ asset|html({attributes}, {options}) }}
```

:::

### image_srcset

Builds the HTML img `srcset` (responsive) attribute of an image Asset.

```twig
{{ image_srcset(asset) }}
```

_Examples:_

```twig
{% set asset = asset(image_path) %}
<img src="{{ url(asset) }}" width="{{ asset.width }}" height="{{ asset.height }}" alt="" class="asset" srcset="{{ image_srcset(asset) }}" sizes="{{ image_sizes('asset') }}">
```

### image_sizes

Returns the HTML img `sizes` attribute based on a CSS class name.  
It should be use in conjunction with the [`image_srcset`](3-Templates.md#image-srcset) function.

```twig
{{ image_sizes('class') }}
```

_Examples:_

```twig
{% set asset = asset(image_path) %}
<img src="{{ url(asset) }}" width="{{ asset.width }}" height="{{ asset.height }}" alt="" class="asset" srcset="{{ image_srcset(asset) }}" sizes="{{ image_sizes('asset') }}">
```

### image_from_website

Builds the HTML img element from a website URL by extracting the image from meta tags.

```twig
image_from_website('<url>')
```

_Examples:_

```twig
{{ image_from_website('https://example.com/page-with-image.html') }}
```

### readtime

Determines read time of a text, in minutes.

```twig
{{ readtime(value) }}
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

### dump

> The `dump` function dumps information about a template variable. This is mostly useful to debug a template that does not behave as expected by introspecting its variables:

```twig
{{ dump(user) }}
```

:::important
The [_debug mode_](4-Configuration.md#debug) must be enabled.
:::

### d

The `d()` function is the HTML version of [`dump()`](#dump) and use the [Symfony VarDumper Component](https://symfony.com/doc/5.4/components/var_dumper.html) behind the scenes.

```twig
{{ d(variable, {theme: light}) }}
```

- If _variable_ is not provided then the function returns the current Twig context
- Available themes are « light » (default) and « dark »

:::important
The [_debug mode_](4-Configuration.md#debug) must be enabled.
:::

## Sorts

Sorting collections (of pages, menus or taxonomies).

### sort_by_title

Sorts a collection by title (with [natural sort](https://en.wikipedia.org/wiki/Natural_sort_order)).

```twig
{{ <collection>|sort_by_title }}
```

_Example:_

```twig
{{ site.pages|sort_by_title }}
```

### sort_by_date

Sorts a collection by date (most recent first).

```twig
{{ <collection>|sort_by_date(variable='date', desc_title=false) }}
```

_Example:_

```twig
{# sort by date #}
{{ site.pages|sort_by_date }}
{# sort by updated variable instead of date #}
{{ site.pages|sort_by_date(variable='updated') }}
{# sort items with the same date by desc title #}
{{ site.pages|sort_by_date(desc_title=true) }}
{# reverse sort #}
{{ site.pages|sort_by_date|reverse }}
```

### sort_by_weight

Sorts a collection by weight (lighter first).

```twig
{{ <collection>|sort_by_weight }}
```

_Example:_

```twig
{{ site.menus.main|sort_by_weight }}
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

### toc

Extracts only headings matching the given `selectors` (h2, h3, etc.), or those defined in config `pages.body.toc` if not specified.  
The `format` parameter defines the output format: `html` or `json`.  
The `url` parameter is used to build links to headings.

```twig
{{ markdown|toc(format, selectors, url) }}
```

_Examples:_

```twig
{{ page.body|toc }}
{{ page.body|toc('html') }}
{{ page.body|toc(selectors=['h2']) }}
{{ page.body|toc(url=url(page)) }}
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

### u

Wraps a text in a `UnicodeString` object to give access to [methods of the class](https://symfony.com/doc/current/components/string.html).

_Example:_

```twig
{{ 'cecil_string with twig'|u.camel.title }}
```

> CecilStringWithTwig

### singular

The `singular` filter transforms a given noun in its plural form into its singular version:

```twig
{{ 'partitions'|singular() }}
```

> partition

### plural

The `plural` filter transforms a given noun in its singular form into its plural version:

```twig
{{ 'animal'|plural() }}
```

> animals

```twig
{# English (en) rules are used by default #}
{{ 'animal'|plural('fr') }}
```

> animaux

### excerpt

Truncates a string and appends suffix.

```twig
{{ string|excerpt(length, suffix) }}
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

Reads characters before or after `<!-- excerpt -->` or `<!-- break -->` tag.  
See [Content documentation](2-Content.md#excerpt) for details.

```twig
{{ string|excerpt_html({separator, capture}) }}
```

| Option    | Description                                           | Type    | Default         |
| --------- | ----------------------------------------------------- | ------- | --------------- |
| separator | String to use as separator.                           | string  | `excerpt|break` |
| capture   | Part to capture, `before` or `after` the separator.   | string  | `before`        |

_Examples:_

```twig
{{ variable|excerpt_html }}
{{ variable|excerpt_html({separator: 'excerpt|break', capture: 'before'}) }}
{{ variable|excerpt_html({capture: 'after'}) }}
```

### highlight

Highlights a code string with [highlight.php](https://github.com/scrivo/highlight.php).

```twig
{{ code|highlight(language) }}
```

_Examples:_

```twig
{{ '<?php echo $highlighted->value; ?>'|highlight('php') }}
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
```

### minify

Minifying a CSS or a JavaScript file.

```twig
{{ asset(path)|minify }}
```

_Examples:_

```twig
{{ asset('styles.css')|minify }}
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

Resizes an image to a specified width (in pixels) or/and height (in pixels).

- If only the width is specified, the height is calculated to preserve the aspect ratio
- If only the height is specified, the width is calculated to preserve the aspect ratio
- If both width and height are specified, the image is resized to fit within the given dimensions, image is cropped and centered if necessary

```twig
{{ asset(image_path)|resize(width: width, height: height) }}
```

:::info
The original file is not altered and the resized version is saved at `/thumbnails/<width>x<height>/image.jpg`.
:::

_Examples:_

```twig
{{ asset(page.image)|resize(300) }}
{# equivalent to: #}
{{ asset(page.image)|resize(width: 300) }}
{# resizes to 300px width, height auto-calculated to preserve aspect ratio #}
{{ asset(page.image)|resize(height: 200) }}
{# resiszes to 300px width and 200px height, and crops if necessary #}
{{ asset(page.image)|resize(300, 200) }}
```

### cover

Resizes an image to a specified width and height, cropping it if necessary.

:::warning
The `cover` filter is deprecated since version ++8.77++ and will be removed in future versions. Use the [`resize`](#resize) filter instead, with both width and height parameters.
:::

```twig
{{ asset(image_path)|cover(width, height) }}
```

_Example:_

```twig
{{ asset(page.image)|cover(1200, 630) }}
```

### maskable

Adds padding, in pourcentages, to an image to make it maskable.

```twig
{{ asset(image_path)|maskable(padding) }}
```

_Example:_

```twig
{{ asset('icon.png')|maskable }}
```

### webp

Converts an image to [WebP](https://developers.google.com/speed/webp) format.

_Example:_

```twig
<picture>
    <source type="image/webp" srcset="{{ asset(image_path)|webp }}">
    <img src="{{ url(asset(image_path)) }}" width="{{ asset(image_path).width }}" height="{{ asset(image_path).height }}" alt="">
</picture>
```

### avif

Converts an image to [AVIF](https://github.com/AOMediaCodec/libavif) format.

_Example:_

```twig
<picture>
    <source type="image/avif" srcset="{{ asset(image_path)|avif }}">
    <img src="{{ url(asset(image_path)) }}" width="{{ asset(image_path).width }}" height="{{ asset(image_path).height }}" alt="">
</picture>
```

### dataurl

Returns the [data URL](https://developer.mozilla.org/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) of an asset.

```twig
{{ asset(path)|dataurl }}
{{ asset(image_path)|dataurl }}
```

### lqip

Returns a [Low Quality Image Placeholder](https://www.guypo.com/introducing-lqip-low-quality-image-placeholders) (100x100 px, 50% blurred) as data URL.

```twig
{{ asset(image_path)|lqip }}
```

### dominant_color

Returns the dominant [hexadecimal color](https://developer.mozilla.org/en-US/docs/Web/CSS/hex-color) of an image.

```twig
{{ asset(image_path)|dominant_color }}
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

### preg_split

Splits a string into an array using a regular expression.

```twig
{{ string|preg_split(pattern, limit) }}
```

_Example:_

```twig
{% set headers = page.content|preg_split('/<br[^>]*>/') %}
```

### preg_match_all

Performs a regular expression match and return the group for all matches.

```twig
{{ string|preg_match_all(pattern, group) }}
```

_Example:_

```twig
{% set tags = page.content|preg_match_all('/<[^>]+>(.*)<\/[^>]+>/') %}
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

### Translation files

Translation files must be named `messages.<locale>.<format>` and stored in the [`translations`](4-Configuration.md#layouts) directory.  
Cecil supports `yaml` and `mo` (Gettext) file [formats by default](4-Configuration.md#layouts).

The locale code (e.g.: `fr_FR`) of a language is defined in the [`languages`](4-Configuration.md#languages) entries of the configuration.

_Example:_

```plaintext
<mywebsite>
└─ translations
   ├─ messages.fr_FR.mo   <- Machine Object format
   └─ messages.fr_FR.yaml <- Yaml format
```

:::info
You can easily extract translations from your templates with the following command:

```bash
php cecil.phar util:translations:extract
```

:::

:::tip
[_Poedit_](https://poedit.net) is a simple and cross platform translation editor for gettext (PO), and [_Poedit Pro_](https://poedit.net/pro) supports extraction of translation strings from templates out of the box.
:::

:::important
Be careful about the [cache](#cache) when you update translations files.

Cache can be cleared with with the following command:

```bash
php cecil.phar cache:clear:translations`
```

:::

### Date localization

Uses the Twig [`format_date`](https://twig.symfony.com/doc/3.x/filters/format_date.html) filter to localize a date in templates.

```twig
{{ page.date|format_date('long') }}
{# September 30, 2022 #}
```

Supported values are: `short`, `medium`, `long`, and `full`.

:::important
If you want to use the `format_date` filter **with other locales than "en"**, you should [install the intl PHP extension](https://php.net/intl.setup).
:::

## Components

Cecil provides a components logic to give you the power making reusable template "units".

:::info
The components feature is provided by the [_Twig components extension_](https://github.com/giorgiopogliani/twig-components) created by Giorgio Pogliani.
:::

### Components syntax

Components are just Twig templates stored in the `components/` subdirectory and can be used anywhere in your templates:

```twig
{# /components/button.twig #}
<button {{ attributes.merge({class: 'rounded px-4'}) }}>
    {{ slot }}
</button>
```

> The slot variable is any content you will add between the opening and the close tag.

To reach a component you need to use the dedicated tag `x` followed by `:` and the filename of your component without extension:

```twig
{# /index.twig #}
{% x:button with {class: 'text-white'} %}
    <strong>Click me</strong>
{% endx %}
```

It will render:

```twig
<button class="text-white rounded px-4">
    <strong>Click me</strong>
</button>
```

## Cache

Cecil uses a cache system to speed up the generation process, it can be disabled or cleared.

There is three types of cache in the case of templates rendering: templates themselves, [assets](#asset) and [translations](#translation-files).

### Clear cache

You can clear the cache with the following commands:

```bash
php cecil.phar cache:clear               # clear all caches
php cecil.phar cache:clear:assets        # clear assets cache
php cecil.phar cache:clear:templates     # clear templates cache
php cecil.phar cache:clear:translations  # clear translations cache
```

:::important
In practice you don't need to clear the cache manually, Cecil does it for you when needed (e.g. when files change).
:::

### Fragments cache

Cecil can cache templates _fragments_ to avoid re-rendering the same partial content multiple times.

To use _fragments_ cache, you must wrap the content you want to cache with the `cache` tag.

```twig
{% cache 'unique-key' %}
{# content #}
{% endcache %}
```

:::info
More details on the official [_Twig cache extension_ documentation](https://twig.symfony.com/doc/tags/cache.html).
:::

Fragments cache is persistent, so during development you may need to clear it, with the following command:

```bash
php cecil.phar cache:clear:templates --fragments
```

### Disable cache

You can disable cache with the [configuration](4-Configuration.md#cache).

:::warning
Disabling cache can slow down the generation process, so it's not recommended.

During local development, if you need to clear cache before each generation, you can use the following option:

```bash
php cecil.phar serve --clear-cache          # clear all caches
php cecil.phar serve --clear-cache=<regex>  # clear cache for cache key matches with the regular expression <regex>
```

Example:

```bash
php cecil.phar serve --clear-cache=css  # clear cache for all CSS files
```

:::

## Extend

### Functions and filters

You can add custom [functions](3-Templates.md#functions) and custom [filters](3-Templates.md#filters) with a [**_Twig extension_**](7-Extend.md#twig-extension).

### Theme

It’s easy to build a theme, you just have to create a folder `<theme>` with the following structure (like a website but without pages):

```plaintext
<mywebsite>
└─ themes
   └─ <theme>
      ├─ config.yml
      ├─ assets
      ├─ layouts
      ├─ static
      └─ translations
```
