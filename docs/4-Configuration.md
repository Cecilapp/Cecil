<!--
description: "Configure your website."
date: 2021-05-07
updated: 2022-04-02
-->

# Configuration

The website configuration is defined in a [YAML](https://en.wikipedia.org/wiki/YAML) file named `config.yml` by default and stored at the root:

```plaintext
<mywebsite>
└─ config.yml
```

_Example:_

```yaml
title: "Cecil"
baseline: "Your content driven static site generator."
baseurl: https://cecil.local/
language: en
```

## Variables

### title

Main title of the site.

```yaml
title: "<title>"
```

### baseline

Short description (~ 20 characters).

```yaml
baseline: "<baseline>"
```

### baseurl

The base URL.

```yaml
baseurl: <baseurl>
```

_Example:_

```yaml
baseurl: http://localhost:8000/
```

:::important
**Important:** `baseurl` must end with a trailing slash (`/`).
:::

### canonicalurl

If set to `true` the [`url()`](3-Templates.md#url) function will return the absolute URL (`false` by default).

```yaml
canonicalurl: false
```

### description

Site description (~ 250 characters).

```yaml
description: "<description>"
```

### date

Date format and timezone.

```yaml
date:
  format: <format>     # date format (`j F Y` by default)
  timezone: <timezone> # date timezone (`Europe/Paris` by default)
```

- `format`: [PHP date](https://php.net/date) format specifier
- `timezone`: see [timezones](https://php.net/timezones)

_Example:_

```yaml
date:
  format: 'Y-m-d'
  timezone: 'UTC'
```

### taxonomies

List of vocabularies, paired by plural and singular value.

```yaml
taxonomies:
  <plural>: <singular>
```

_Default taxonomies:_

```yaml
taxonomies:
  categories: category
  tags: tag
```

:::tip
**Tip:** A vocabulary can be disabled with the special value `disabled`. Example: `tags: disabled`.
:::

### menus

A menu is made up of a unique name and entry’s properties.

```yaml
menus:
  <name>:
    - id: <unique_id> # unique identifier (required)
      name: "<name>"  # name used in templates
      url: <url>      # relative or absolute URL
      weight: <XX>    # used to sort entries (lighter first)
```

By default a `main` menu is created and contains the home page and sections entries.

_Example:_

```yaml
menus:
  footer:
    - id: author
      name: "The author"
      url: https://arnaudligny.fr
      weight: 99
```

#### Override entry properties

A page menu entry can be overridden: use the _Page_ ID as `id`.

_Example:_

```yaml
menus:
  main:
    - id: index
      name: "My amazing homepage!"
      weight: 1
```

#### Disable an entry

A menu entry can be disabled with `enabled: false`.

_Example:_

```yaml
menus:
  main:
    - id: about
      enabled: false
```

### metatags

_metatags_ are SEO helpers that can be injected automatically in the `<head>` by including the [`partials/metatags.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/metatags.html.twig) template.

*[SEO]: Search Engine Optimization

_Example:_

```twig
<html lang="{{ site.language }}">
  <head>
    <meta charset="utf-8">
    {%~ include 'partials/metatags.html.twig' ~%}
    [... other head elements ...]
  </head>
  [...]
</html>
```

This template adds the following meta tags to your site:

- Page title with site title or site title with site baseline
- Page description or site description
- Page tags or site keywords
- Page author or site author
- Search engine crawler directives
- Favicon links
- Previous and next page links
- Pagination links (first, previous, next, last)
- Canonical URL
- Links to alternates formats (e.g.: RSS feed)
- Open Graph
- Twitter Card
- JSON-LD site and article metadata

#### metatags keys

Cecil uses page’s front matter to feed meta tags, and fallbacks to the site configuration if needed.

```yaml
title: "Page or site title"
description: "Page or site description"
keywords: [keyword1, keyword2] # use `tags` key in page front matter
author: "Author name"
image: image.jpg
social:
  twitter:
    site: @username
    creator: @username
  facebook:
    id: 123456789
    firstname: "Firstname"
    lastname: "Lastname"
    username: username
```

:::tip
**Tip:** If needed the title can be overridden:

```twig
{% include 'partials/metatags.html.twig' with {title: 'Custom title'} %}
```
:::

#### metatags configuration

```yaml
metatags:
  title:                  # title options
    divider: " &middot; "   # string between page title and site title
    only: false             # displays page title only (`false` by default)
    pagination:
      shownumber: true      # displays page number in title (`true` by default)
      label: "Page %s"      # how to display page number (`Page %s` by default)
  robots: "index,follow"  # web crawlers directives (`index,follow` by default)
  articles: "blog"        # articles' section (`blog` by default)
  jsonld:
    enabled: true         # injects JSON-LD meta tags (`false` by default)
  favicon:
    enabled: true         # injects favicon (`true` by default)
    image: favicon.png    # path to favicon image
    sizes:
      - "icon": [32, 57, 76, 96, 128, 192, 228] # web browsers
      - "shortcut icon": [196]                  # Adnroid
      - "apple-touch-icon": [120, 152, 180]     # iOS
```

### language

Main language, defined by its code.

```yaml
language: en # `en` by default
```

### languages

List of available languages, used for [content](2-Content.md#multilingual) and [templates](3-Templates.md#localization) localization.

```yaml
languages:
  - code: <code>     # language unique code
    name: <name>     # human readable name of the language
    locale: <locale> # locale code of the language
```

#### Localize configuration variables 

To localize configuration variables you must store them under the `config` key.

_Example:_

```yaml
title: "Cecil in english"
languages:
  - code: en
    name: English
    locale: en_US
  - code: fr
    name: Français
    locale: fr_FR
    config:
      title: "Cecil en français"
```

:::info
**Info:** A list of [locales code](configuration/locale-codes.md) is avalaible.
:::

### theme

The theme name or an array of themes name.

```yaml
theme:
  - <theme1> # theme name
  - <theme2>
```

The first theme overrides the others, and so on.

_Examples:_

```yaml
theme: hyde
```

```yaml
theme:
  - serviceworker
  - hyde
```

:::info
**Info:** See [officials themes](https://github.com/Cecilapp?q=theme).
:::

### pagination

Pagination is available for list pages (_type_ is `homepage`, `section` or `term`).

```yaml
pagination:
  max: 10      # maximum number of entries per page (`5` by default)
  path: "page" # path to the paginated page (`page` by default)
```

_Example:_

```yaml
pagination:
  max: 5
  path: "p"
```

#### Disable pagination

Pagination can be disabled:

```yaml
pagination:
  enabled: false
```

### googleanalytics

[Google Analytics](https://wikipedia.org/wiki/Google_Analytics) user identifier:

```yaml
googleanalytics: UA-XXXXX
```

Used by the built-in partial template [`googleanalytics.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/googleanalytics.js.twig).

### virtualpages

Virtual pages is the best way to create pages without content (**front matter only**).

It consists of a list of pages with a `path` and front matter variables.

_Example:_

```yaml
virtualpages:
  - path: code
    redirect: https://github.com/ArnaudLigny
```

### output

Defines where and how files are generated.

#### dir

Directory where rendered pages’ files are saved.

```yaml
output:
  dir: _site # `_site` by default
```

#### formats

List of output formats.

```yaml
output:
  formats:
    <name>:
      name: <name>            # name of the format (e.g.: `html`)
      mediatype: <media type> # media type (MIME). ie: 'text/html'
      subpath: <sub path>     # sub path (e.g.: `/amp` in `path/amp/index.html`)
      suffix: <suffix>        # file name (e.g.: `/index` in `path/index.html`)
      extension: <extension>  # file extension (e.g.: `html` in `path/index.html`)
      exclude: [<variable>]   # don’t apply this format to pages identified by listed variables (e.g.: `[redirect]`)
```

#### pagetypeformats

Array of generated output formats for each page type (`homepage`, `page`, `section`, `vocabulary` and `term`).

```yaml
output:
  pagetypeformats:
    page: [<format>]
    homepage: [<format>]
    section: [<format>]
    vocabulary: [<format>]
    term: [<format>]
```

_Example:_

```yaml
output:
  dir: _site
  formats:
    - name: html
      mediatype: text/html
      suffix: index
      extension: html
    - name: rss
      mediatype: application/rss+xml
      suffix: rss
      extension: xml
      exclude: [redirect, paginated]
  pagetypeformats:
    page: [html]
    homepage: [html, rss]
    section: [html, rss]
    vocabulary: [html]
    term: [html, rss]
```

### paths

Define a custom [`path`](2-Content.md#variables) for all pages of a _Section_.

```yaml
paths:
  - section: <section’s name>
    path: <path of pages, with palceholders>
```

#### Placeholders

- `:year`
- `:month`
- `:day`
- `:section`
- `:slug`

_Example:_

```yaml
paths:
  - section: Blog
    path: :section/:year/:month/:day/:slug/
```

### debug

Enables the _debug mode_, used to display debug information like Twig dump, Twig profiler, SCSS sourcemap, etc.

```yaml
debug: true
```

There is 2 others way to enable the _debug mode_:

1. Run a command with the `-vvv` option
2. Set the `CECIL_DEBUG` environment variable to `true`

## Default values

The configuration website (`config.yml`) overrides the [default configuration](https://github.com/Cecilapp/Cecil/blob/master/config/default.php#L11).

### defaultpages

Default pages are pages created automatically by Cecil, from built-in templates:

- _index.html_ (home page)
- _404.html_
- _robots.txt_
- _sitemaps.xml_

:::info
**Info:** The structure is almost identical of [`virtualpages`](#virtualpages), except the named key.
:::

```yaml
defaultpages:
  index:
    path: ''
    title: 'Home'
    published: true
  404:
    path: '404'
    title: 'Page not found'
    layout: '404'
    uglyurl: true
    published: true
    exclude: true
  robots:
    path: robots
    title: 'Robots.txt'
    layout: 'robots'
    output: 'txt'
    published: true
    exclude: true
    multilingual: false
  sitemap:
    path: sitemap
    title: 'XML sitemap'
    layout: 'sitemap'
    output: 'xml'
    changefreq: 'monthly'
    priority: '0.5'
    published: true
    exclude: true
    multilingual: false
```

Each one can be:

1. disabled: `published: false`
2. excluded from list pages: `exclude: true`
3. excluded from localization: `multilingual: false`

### content

Where content files are stored and loaded file extensions (Markdown and plain text files).

```yaml
content:
  dir: content                                     # content directory
  ext: [md, markdown, mdown, mkdn, mkd, text, txt] # array of content files extensions
  exclude: [vendor, node_modules]                  # array of directories, paths and files name to exclude (accepts globs, strings and regexes)
```

### frontmatter

Pages’ variables format (YAML by default).

```yaml
frontmatter:
  format: yaml # front matter format (`yaml` by default)
```

### body

Pages’ content format and converter’s options.

```yaml
body:
  format: md             # page body format (only Markdown is supported)
  toc: [h2, h3]          # headers used to build the table of contents
  images:                # how to handle images
    lazy:
      enabled: true      # enables lazy loading (`true` by default)
    caption:
      enabled: true      # adds <figcaption> to images with a title (`false` by default)
    remote:
      enabled: true      # enables remote image handling (`true` by default)
    resize:
      enabled: false     # enables image resizing by using the `width` extra attribute (`false` by default)
    responsive:
      enabled: false     # creates responsive images (`false` by default)
    webp:
      enabled: false     # creates WebP images (`false` by default)
  notes:
    enabled: false       # enables Notes blocks (`false` by default)
  highlight:
    enabled: false       # enables syntax highlighting (`false` by default)
```

To know how those options impacts your content see _[Content > Page > Body](2-Content.md#body)_ documentation.

:::info

**Info:** Remote images are downloaded (and converted into _Assets_ to be manipulated). You can disable this behavior by setting the option `body.images.remote.enabled` to `false`.
:::

### data

Where data files are stored and what extensions are handled.

```yaml
data:
  dir: data                        # data directory
  ext: [yaml, yml, json, xml, csv] # array of files extensions.
  load: true                       # enables `site.data` collection
```

Supported formats: YAML, JSON, XML and CSV.

### static

Where static files are stored (CSS, images, PDF, etc.).

```yaml
static:
  dir: static # files directory
  target: ''  # target directory
  exclude: [sass, scss, '*.scss', 'package*.json', 'node_modules'] # list of excluded files (accepts globs, strings and regexes)
  load: false # enables `site.static` collection (`false` by default)
```

:::important
**Important:** You should put your assets files, used by [`asset()`](3-Templates.md#asset), in [`assets` directory](4-Configuration.md#assets) to avoid unnecessary files copy.
:::

_Example:_

```yaml
static:
  dir: docs
  target: docs
  exclude:
    - 'sass'
    - '*.scss'
    - '/\.bck$/'
  load: true
```

### layouts

Where templates files are stored.

```yaml
layouts:
  dir: layouts # layouts directory
```

### themes

Where themes are stored.

```yaml
themes:
  dir: themes # themes directory
```

### assets

Assets handling options.

```yml
assets:
  dir: assets            # files directory
  fingerprint:
    enabled: true        # enables fingerprinting (`true` by default)
  compile:
    enabled: true        # enables Sass asset compilation (`true` by default)
    style: expanded      # style of compilation (`expanded` or `compressed`. `expanded` by default)
    import: [sass, scss] # list of imported paths (`[sass, scss, node_modules]` by default)
    sourcemap: false     # enables sourcemap in debug mode (`false` by default)
    variables: []        # list of preset variables (empty by default)
  minify:
    enabled: true        # enables asset minification (`true` by default)
  target: assets         # where remote and resized assets are saved
  images:
    optimize:
      enabled: false     # enables images optimization with JpegOptim, Optipng, Pngquant 2, SVGO 1, Gifsicle, cwebp (`false` by default)
    quality: 75          # image quality after optimization or resize (`75` by default)
    responsive:
      enabled: false     # creates responsive images with `html` filter (`false` by default)
      widths: [480, 640, 768, 1024, 1366, 1600, 1920] # `srcset` widths (`[480, 640, 768, 1024, 1366, 1600, 1920]` by default)
      sizes:
        default: '100vw' # `sizes` attribute (`100vw` by default)
    webp:
      enabled: false     # creates a WebP version of images with `html` filter (`false` by default)
```

Notes:

- See [documentation of scssphp](https://scssphp.github.io/scssphp/docs/#output-formatting) for details about `style` compilation and `variables`
- `minify` is available for file with a `text/css` or `text/javascript` MIME Type)
- Enables sourcemap output requires [debug mode](#debug) is enabled

### postprocess

Options of files optimizations after build step (post process).

```yaml
postprocess:
  enabled: false     # enables (`false` by default)
  html:
    ext: [html, htm] # list of files extensions
    enabled: true    # enables HTML post processing
  css:
    ext: [css]       # list of files extensions
    enabled: true    # enables CSS post processing
  js:
    ext: [js]        # list of files extensions
    enabled: true    # enables JS post processing
  images:
    ext: [jpeg, jpg, png, gif, webp, svg] # list of files extensions
    enabled: true                         # enables images post processing
```

Images compressor will use these binaries if they are present in the system: [JpegOptim](http://freecode.com/projects/jpegoptim), [Optipng](http://optipng.sourceforge.net/), [Pngquant 2](https://pngquant.org/), [SVGO](https://github.com/svg/svgo), [Gifsicle](http://www.lcdf.org/gifsicle/) and [cwebp](https://developers.google.com/speed/webp/docs/cwebp).

### cache

Cache options.

```yaml
cache:
  dir: '.cache' # cache directory
  enabled: true # enables cache
  templates:    # Twig templates cache
    dir: templates
    enabled: true
  assets:       # Assets cache
    dir: 'assets/remote' # the subdirectory of remote assets cache
```

### generators

Generators creates pages automictically (sitemap, feed, pagination, etc.) in a definite order:

```yaml
generators:
  10: 'Cecil\Generator\DefaultPages'
  20: 'Cecil\Generator\VirtualPages'
  30: 'Cecil\Generator\ExternalBody'
  40: 'Cecil\Generator\Section'
  50: 'Cecil\Generator\Taxonomy'
  60: 'Cecil\Generator\Homepage'
  70: 'Cecil\Generator\Pagination'
  80: 'Cecil\Generator\Alias'
  90: 'Cecil\Generator\Redirect'
```

## Alternative to config file

### Environment variables

The configuration can be defined through [environment variables](https://en.wikipedia.org/wiki/Environment_variable).

Each environment variable name must be prefixed with `CECIL_` and the configuration key must be set in uppercase.

For example, the following command set the website’s `baseurl`:

```bash
export CECIL_BASEURL="https://example.com/"
```
