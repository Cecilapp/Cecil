<!--
description: "Configure your website."
date: 2021-05-07
updated: 2025-05-11
-->
# Configuration

## Overview

The website configuration is defined in a [YAML](https://en.wikipedia.org/wiki/YAML) file named `cecil.yml` or `config.yml` stored at the root:

```plaintext
<mywebsite>
└─ cecil.yml
```

Cecil offers many configuration options, but its [defaults](https://github.com/Cecilapp/Cecil/blob/master/config/default.php) are often sufficient. A new site requires only these settings:

```yaml
title: "My new Cecil site"
baseurl: https://mywebsite.com/
description: "Site description"
```

The following documentation covers all supported configuration options in Cecil.

## Options

### title

Main title of the site.

```yaml
title: "<site title>"
```

### baseline

Short description (~ 20 characters).

```yaml
baseline: "<baseline>"
```

### baseurl

The base URL.

```yaml
baseurl: <url>
```

_Example:_

```yaml
baseurl: http://localhost:8000/
```

:::important
`baseurl` should end with a trailing slash (`/`).
:::

### canonicalurl

If set to `true` the [`url()`](3-Templates.md#url) function will return the absolute URL (`false` by default).

```yaml
canonicalurl: <true|false> # false by default
```

### description

Site description (~ 250 characters).

```yaml
description: "<description>"
```

### menus

Menus are used to create [navigation links in templates](3-Templates.md#site-menus).

A menu is made up of a unique ID and entry’s properties (name, URL, weight).

```yaml
menus:
  <name>:
    - id: <unique-id>   # unique identifier (required)
      name: "<name>"    # name displayed in templates
      url: <url>        # relative or absolute URL
      weight: <integer> # integer value used to sort entries (lighter first)
```

_Example:_

```yaml
menus:
  main:
    - id: about
      name: "About"
      url: /about/
      weight: 1
  footer:
    - id: author
      name: The author
      url: https://arnaudligny.fr
      weight: 99
```

:::info
A `main` menu is automatically created with the home page entry and all sections entries ([See content management](2-Content.md))
:::

:::tip
A page can be added to a menu by setting the [`menu` variable](2-Content.md#menu) in its front matter.
:::

#### Override an entry

A page menu entry can be overridden: use the page ID as `id`.

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

### taxonomies

List of vocabularies, paired by plural and singular value.

```yaml
taxonomies:
  <plural>: <singular>
```

_Example:_

```yaml
taxonomies:
  categories: category
  tags: tag
```

:::warning
Since ++version 8.37.0++, default vocabularies `category` and `tag` have been removed.
:::

:::tip
A vocabulary can be disabled with the special value `disabled`. Example: `tags: disabled`.
:::

### theme

The theme to use, or a list of themes.

```yaml
theme: <theme> # theme name
# or
theme:
  - <theme1> # theme name
  - <theme2>
```

:::info
The first theme overrides the others, and so on.
:::

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
See [themes on GitHub](https://github.com/Cecilapp?q=theme#org-repositories) or on website [themes section](https://cecil.app/themes/).
:::

### date

Date format and timezone.

```yaml
date:
  format: <format>     # date format (optional, `F j, Y` by default)
  timezone: <timezone> # date timezone (optional, local time zone by default)
```

- `format`: [PHP date](https://php.net/date) format specifier
- `timezone`: see [timezones](https://php.net/timezones)

_Example:_

```yaml
date:
  format: 'j F, Y'
  timezone: 'Europe/Paris'
```

### language

The main language, defined by its code.

```yaml
language: <code> # unique code (`en` by default)
```

By default only others [languages](#languages) pages path are prefixed with its language code, but you can prefix the path of the main language pages with the following option:

```yaml
#language: <code>
language:
  code: <code>
  prefix: true
```

:::info
When `prefix` is set to `true`, an alias is automatically created for the home page that redirect from`/` to `/<code>/`.
:::

### languages

Options of available languages, used for [pages](2-Content.md#multilingual) and [templates](3-Templates.md#localization) localization.

```yaml
languages:
  - code: <code>          # unique code (e.g.: `en`, `fr`, 'en-US', `fr-CA`)
    name: <name>          # human readable name (e.g.: `Français`)
    locale: <locale>      # locale code (`language_COUNTRY`, e.g.: `en_US`, `fr_FR`, `fr_CA`)
    enabled: <true|false> # enabled or not (`true` by default)
```

_Example:_

```yaml
language: en
languages:
  - code: en
    name: English
    locale: en_EN
  - code: fr
    name: Français
    locale: fr_FR
```

:::info
There is a [locales code list](configuration/locale-codes.md) if needed.
:::

#### Localize

To localize configuration options you must store them under the `config` key of the language.

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
In [templates](3-Templates.md) you can access to an option with `{{ site.<option> }}`, for example `{{ site.title }}`.  
If an option is not available in the current language (e.g.: `fr`) it fallback to the global one (e.g.: `en`).
:::

### metatags

_metatags_ are SEO and social helpers that can be automatically  injected in the `<head>`, with the _partial_ template [`metatags.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/metatags.html.twig).

*[SEO]: Search Engine Optimization

_Example:_

```twig
<html lang="{{ site.language }}">
  <head>
    <meta charset="utf-8">
    {{ include('partials/metatags.html.twig') }}
  </head>
  <body>
    ...
  </body>
</html>
```

This template adds the following meta tags:

- Page title + Site title, or Site title + Site baseline
- Page/Site description
- Page/Site keywords
- Page/Site author
- Search engine crawler directives
- Favicon links
- Previous and next page links
- Pagination links (first, previous, next, last)
- Canonical URL
- Links to alternate versions (i.e.: RSS feed, others languages)
- `rel=me` links
- Open Graph
- Facebook meta
- Twitter/X Card
- Mastodon meta
- Structured data (JSON-LD)

#### metatags options and front matter

Cecil uses page’s front matter to feed meta tags, and fallbacks to site options if needed.

```yaml
title: "Page/Site title"
description: "Page/Site description"
tags: [tag1, tag2]                   # feeds keywords meta
keywords: [keyword1, keyword2]       # obsolete
author:
  name: <name>
  url: <url>
  email: <email>
image: image.jpg                     # for OpenGraph and social networks cards
canonical:                           # to override the generated canonical URL
  url: <URL>
  title: "<URL title>"               # optional
social:
  twitter:
    url: <URL>                       # used for `rel=me` link
    site: username                   # main account
    creator: username                # content author account
  mastodon:
    url: <URL>
    creator: handle
  facebook:
    url: <URL>
    id: 123456789
    firstname: Firstname
    lastname: Lastname
    username: username
```

:::tip
If needed, `title` and `image` can be overridden:

```twig
{{ include('partials/metatags.html.twig', {title: 'Custom title', image: og_image}) }}
```

:::

#### metatags options

```yaml
metatags:
  title:                 # title options
    divider: " &middot; "  # string between page title and site title
    only: false            # displays page title only (`false` by default)
    pagination:
      shownumber: true     # displays page number in title (`true` by default)
      label: "Page %s"     # how to display page number (`Page %s` by default)
  image:
    enabled: true        # injects image (`true` by default)
  robots: "index,follow" # web crawlers directives (`index,follow` by default)
  articles: "blog"       # articles' section (`blog` by default)
  jsonld:
    enabled: false       # injects JSON-LD structured data (`false` by default)
  favicon:
    enabled: true        # injects favicon (`true` by default)
    image: favicon.png     # path to favicon image
    sizes:
      - "icon": [32, 57, 76, 96, 128, 192, 228] # web browsers
      - "shortcut icon": [196]                  # Android
      - "apple-touch-icon": [120, 152, 180]     # iOS
```

### debug

Enables the _debug mode_, used to display debug information like very verbose logs, Twig dump, Twig profiler, SCSS sourcemap, etc.

```yaml
debug: true
```

There is 2 others way to enable the _debug mode_:

1. Run a command with the `-vvv` option
2. Set the `CECIL_DEBUG` environment variable to `true`

---

## Pages

### pages.dir

Directory source of pages (`pages` by default).

```yaml
pages:
  dir: pages
```

### pages.ext

Extensions of pages files.

```yaml
pages:
  ext: [md, markdown, mdown, mkdn, mkd, text, txt]
```

### pages.exclude

Directories, paths and files name to exclude (accepts globs, strings and regexes).

```yaml
pages:
  exclude: ['vendor', 'node_modules', '*.scss', '/\.bck$/']
```

### pages.sortby

Default collections sort method.

```yaml
pages:
  sortby: date # `date`, `updated`, `title` or `weight`
  # or
  sortby:
    variable: date    # `date`, `updated`, `title` or `weight`
    desc_title: false # sort by title in descending order
    reverse: false    # reverse the sort order
```

### pages.pagination

Pagination is available for list pages (_type_ is `homepage`, `section` or `term`).

```yaml
pages:
  pagination:
    max: 5     # maximum number of entries per page
    path: page # path to the paginated page
```

#### Disable pagination

Pagination can be disabled:

```yaml
pages:
  pagination: false
```

### pages.paths

Apply a custom [`path`](2-Content.md#predefined-variables) for all pages of a **_Section_**.

```yaml
pages:
  paths:
    - section: <section’s ID>
      path: <path of pages>
```

#### Path placeholders

- `:year`
- `:month`
- `:day`
- `:section`
- `:slug`

_Example:_

```yaml
pages:
  paths:
    - section: Blog
      path: :section/:year/:month/:day/:slug # e.g.: /blog/2020/12/01/my-post/
# localized
languages:
  - code: fr
    name: Français
    locale: fr_FR
    config:
      pages:
        paths:
          - section: Blog
            path: blogue/:year/:month/:day/:slug # e.g.: /blogue/2020/12/01/mon-billet/
```

### pages.frontmatter

Pages’ front matter format (`yaml` by default, also accepts `ini`, `toml` and `json`).

```yaml
pages:
  frontmatter: yaml
```

### pages.body

Pages’ body options.

:::info
To know how those options impacts your content see _[Content > Markdown](2-Content.md#markdown)_ documentation.
:::

#### pages.body.toc

Headers used to build the table of contents (`[h2, h3]` by default).

```yaml
pages:
  body:
    toc: [h2, h3]
```

#### pages.body.highlight

Enables code syntax highlighting (`false` by default).

```yaml
pages:
  body:
    highlight: false
```

#### pages.body.images

Images handling options.

```yaml
pages:
  body:
    images:
      formats: []       # adds alternative image formats as `source` (e.g. `[webp, avif]`, empty array by default)
      resize: 0         # resizes all images to <width> (in pixels, `0` to disable)
      responsive: false # adds responsives images them to the `srcset` attribute (`false` by default)
      lazy: true        # adds `loading="lazy"` attribute (`true` by default)
      decoding: true    # adds `decoding="async"` attribute (`true` by default)
      caption: false    # puts the image in a <figure> element and adds a <figcaption> containing the title (`false` by default)
      placeholder: ''   # fill <img> background before loading ('color' or 'lqip', empty by default)
      class: ''         # put default class to each image (empty by default)
      remote:           # remote image handling (set to `false` to disable)
        fallback:         # path to the fallback image, stored in assets dir (empty by default)
```

:::warning
Since version ++8.41.0++, the `pages.body.images.resize` option is used to resize images to a specific width, no more to enable the resize feature (enabled systematically).
:::

:::important
Global options, like responsives images widths and sizes, are configurable in the [`assets.images`](#assets-images) section.
:::

:::info
Remote images are downloaded and converted into _Assets_ to be manipulated. You can disable this behavior by setting the option `pages.body.images.remote.enabled` to `false`.
:::

#### pages.body.links

Links handling options.

```yaml
pages:
  body:
    links:
      embed:
        enabled: false     # turns links in embedded content if possible (`false` by default)
        video: [mp4, webm] # video files extensions
        audio: [mp3]       # audio files extensions
      external:
        blank: false     # if true open external link in new tab
        noopener: true   # if true add "noopener" to `rel` attribute
        noreferrer: true # if true add "noreferrer" to `rel` attribute
        nofollow: false  # if true add "nofollow" to `rel` attribute
```

#### pages.body.excerpt

Excerpt handling options.

```yaml
pages:
  body:
    excerpt:
      separator: excerpt|break # string to use as separator (`excerpt|break` by default)
      capture: before          # part to capture, `before` or `after` the separator (`before` by default)
```

### pages.virtual

Virtual pages is the best way to create pages without content (**front matter only**).

It consists of a list of pages with a `path` and some front matter variables.

_Example:_

```yaml
pages:
  virtual:
    - path: code
      redirect: https://github.com/ArnaudLigny
```

### pages.default

Default pages are pages created automatically by Cecil (from built-in templates):

```yaml
pages:
  default:
    index:
      path: ''
      title: Home
      published: true
    404:
      path: 404
      title: Page not found
      layout: 404
      uglyurl: true
      published: true
      exclude: true
    robots:
      path: robots
      title: Robots.txt
      layout: robots
      output: txt
      published: true
      exclude: true
      multilingual: false
    sitemap:
      path: sitemap
      title: XML sitemap
      layout: sitemap
      output: xml
      changefreq: monthly
      priority: 0.5
      published: true
      exclude: true
      multilingual: false
    xsl/atom:
      path: xsl/atom
      layout: feed
      output: xsl
      uglyurl: true
      published: true
      exclude: true
    xsl/rss:
      path: xsl/rss
      layout: feed
      output: xsl
      uglyurl: true
      published: false
      exclude: true
```

:::info
The structure is almost identical of [`pages.virtual`](#pages-virtual), except the named key.
:::

Each one can be:

1. disabled: `published: false`
2. excluded from list pages: `exclude: true`
3. excluded from localization: `multilingual: false`

### pages.generators

Generators are used by Cecil to create additional pages (e.g.: sitemap, feed, pagination, etc.) from existing pages, or from other sources like the configuration file or external sources.

Below the list of Generators provided by Cecil, in a defined order:

```yaml
pages:
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

:::tip
You can extend Cecil with [Pages generator](7-Extend.md#pages-generator).
:::

### pages.subsets

Subsets are used to render a part of the pages collection, based on a specific path, language or output format, with the command:

```bash
cecil build --render-subset=<name>
```

```yaml
pages:
  subsets:
    <name>:
      path: <path> # glob or string path (e.g.: `blog/*`, `blog`, etc.)
      language: <language> # language code (e.g.: `en`, `fr`, etc.)
      output: <output> # output format (e.g.: `html`, `atom`, etc.)
```

_Example:_

```yaml
pages:
  subsets:
    blog:
      path: blog
      language: en
      output: html
    index:
      path: '*'
      output: json
```

---

## Data

Where data files are stored and what extensions are handled.

Supported formats: YAML, JSON, XML and CSV.

### data.dir

Data source directory (`data` by default).

```yaml
data:
  dir: data
```

### data.ext

Array of files extensions.

```yaml
data:
  ext: [yaml, yml, json, xml, csv]
```

### data.load

Enables `site.data` collection (`true` by default).

```yaml
data:
  load: true
```

---

## Static

Management of static files are copied (PDF, fonts, etc.).

:::important
You should put your assets files, used by [`asset()`](3-Templates.md#asset), in the [`assets` directory](4-Configuration.md#assets-dir) to avoid unnecessary files copy.
:::

### static.dir

Static files source directory (`static` by default).

```yaml
static:
  dir: static
```

### static.target

Directory where static files are copied (root by default).

```yaml
static:
  target: ''
```

### static.exclude

List of excluded files. Accepts globs, strings and regexes.

```yaml
static:
  exclude: ['sass', 'scss', '*.scss', 'package*.json', 'node_modules']
```

:::tip
If you use [Bootstrap Icons](https://icons.getbootstrap.com) you can exclude the `node_modules` except `node_modules/bootstrap-icons` with a regular expression:

```yaml
exclude: ['sass', 'scss', '*.scss', 'package*.json', '#node_modules/(?!bootstrap-icons)#']
```

:::

### static.load

Enables `site.static` collection (`false` by default).

```yaml
static:
  load: false
```

### static.mounts

Allows to copy specific files or directories to a specific destination.

```yaml
static:
  mounts: []
```

### static example

```yaml
static:
  dir: docs
  target: docs
  exclude: ['sass', '*.scss', '/\.bck$/']
  load: true
  mounts:
    - source/path/file.ext: dest/path/file.ext
    - node_modules/bootstrap-icons/font/fonts: fonts
```

## Assets

Assets management (images, CSS and JS files).

### assets.dir

Assets source directory (`assets` by default).

```yaml
assets:
  dir: assets
```

### assets.target

Directory where remote and resized assets files are saved (root by default).

```yaml
assets:
  target: ''
```

### assets.fingerprint

Enables fingerprinting (cache busting) for assets files (`true` by default).

```yaml
assets:
  fingerprint: true
```

### assets.compile

Enables [Sass](https://sass-lang.com) files compilation (`true` by default). See the [documentation of scssphp](https://scssphp.github.io/scssphp/docs/#output-formatting) for options details.

```yaml
assets:
  compile:
    style: expanded      # compilation style (`expanded` or `compressed`. `expanded` by default)
    import: [sass, scss] # list of imported paths (`[sass, scss, node_modules]` by default)
    sourcemap: false     # enables sourcemap in debug mode (`false` by default)
    variables: []        # list of preset variables (empty by default)
```

:::info
`sourcemap` is used to debug SCSS compilation ([debug mode](#debug) must be enabled).
:::

### assets.minify

Enables CSS and JS minification (`true` by default).

```yaml
assets:
  minify: true
```

### assets.images

Images management.

```yaml
assets:
  images:
    optimize: false # enables images optimization with JpegOptim, Optipng, Pngquant 2, SVGO 1, Gifsicle, cwebp, avifenc (`false` by default)
    quality: 75     # image quality of `optimize` and `resize` (`75` by default)
    responsive:
      widths: [480, 640, 768, 1024, 1366, 1600, 1920] # `srcset` attribute images widths
      sizes:
        default: '100vw' # default `sizes` attribute (`100vw` by default)
```

### assets.images.cdn

URL of image assets can be easily replaced by a provided CDN `url`.

```yaml
assets:
  images:
    cdn:
      enabled: false  # enables Image CDN (`false` by default)
      canonical: true # `image_url` is canonical (instead of a relative path) (`true` by default)
      remote: true    # handles not local images too (`true` by default)
      account: 'xxxx' # provider account
      url: 'https://provider.tld/%account%/%image_url%?w=%width%&q=%quality%&format=%format%'
```

`url` is a pattern that contains variables:

- `%account%` replaced by the `assets.images.cdn.account` option
- `%image_url%` replaced by the image canonical URL or `path`
- `%width%` replaced by the image width
- `%quality%` replaced by the `assets.images.quality` option
- `%format%` replaced by the image format

See [**CDN providers**](configuration/cdn-providers.md).

### assets.remote.useragent

User agent used to download remote assets.

```yaml
assets:
  remote:
    useragent:
      default: <string> # default user agent
      useragent1: <string>
      useragent2: <string>
```

## Layouts

Templates options.

### layouts.dir

Templates directory source (`layouts` by default).

```yaml
layouts:
  dir: layouts
```

### layouts.images

Images handling options.

```yaml
layouts:
  images:
    formats: []       # used by `html` function: adds alternatives image formats as `source` (empty array by default)
    responsive: false # used by `html` function: adds responsive images (`false` by default)
```

### layouts.translations

Translations handling options.

```yaml
layouts:
  translations:
    dir: translations       # translations source directory (`translations` by default)
    formats: ['yaml', 'mo'] # translations files format (`yaml` and `mo` by default)
```

### layouts.components

[Templates Components](3-Templates.md#components) options.

```yaml
layouts:
  components:
    dir: components # components source directory (`components` by default)
    ext: twig       # components files extension (`twig` by default)
```

---

## Output

Defines where and in what format(s) content is rendered.

### output.dir

Directory where rendered pages’ files are saved (`_site` by default).

```yaml
output:
  dir: _site
```

### output.formats

List of output formats, in which of them pages’ content is rendered (e.g. HTML, JSON, XML, RSS, Atom, etc.).

```yaml
output:
  formats:
    - name: <name>            # name of the format, e.g.: `html` (required)
      mediatype: <media type> # media type (MIME type), ie: 'text/html' (optional)
      subpath: <sub path>     # sub path, e.g.: `amp` in `path/amp/index.html` (optional)
      filename: <file name>   # file name, e.g.: `index` in `path/index.html` (optional)
      extension: <extension>  # file extension, e.g.: `html` in `path/index.html` (required)
      exclude: [<variable>]   # don’t apply this format to pages identified by listed variables, e.g.: `[redirect, paginated]` (optional)
```

Those formats are used by [`pagetypeformats`](#output-pagetypeformats) and by the [`output` page’s variable](2-Content.md#output).

:::info
To render a page, [Cecil lookup for a template](3-Templates.md#lookup-rules) named `<layout>.<format>.twig` (e.g. `page.html.twig`)
:::

### output.pagetypeformats

Array of output formats by each page type (`homepage`, `page`, `section`, `vocabulary` and `term`).

```yaml
output:
  pagetypeformats:
    page: [<format>]
    homepage: [<format>]
    section: [<format>]
    vocabulary: [<format>]
    term: [<format>]
```

Several formats can be defined for the same type of page. For example the `section` page type can be automatically rendered in HTML and Atom.

### output example

```yaml
output:
  dir: _site
  formats:
    - name: html
      mediatype: text/html
      filename: index
      extension: html
    - name: atom
      mediatype: application/xml
      filename: atom
      extension: xml
      exclude: [redirect, paginated]
  pagetypeformats:
    page: [html]
    homepage: [html, atom]
    section: [html, atom]
    vocabulary: [html]
    term: [html, atom]
```

:::tip
You can extend Cecil with [Output post processor](7-Extend.md#output-post-processor).
:::

---

## Cache

Cache options.

### cache.enabled

Cache is enabled by default (`true`), but you can disable it with:

```yaml
cache:
  enabled: false
```

:::warning
It’s not recommended to disable the cache for performance reasons.
:::

### cache.dir

Directory where cache files are stored (`.cache` by default).

```yaml
cache:
  dir: '.cache'
```

:::info
The cache directory is relative to the site directory, but you can use an absolute path: it can be useful to store the cache in a shared directory.
:::

### cache.assets

Assets cache options.

### cache.assets.ttl

Time to live of assets cache in seconds (`null` by default = no expiration).

```yaml
cache:
  assets:
    ttl: ~
```

### cache.assets.remote.ttl

Time to live of remote assets cache in seconds (7 days by default).

```yaml
cache:
  assets:
    remotes:
      ttl: 604800 # 7 days
```

### cache.templates

Disables templates cache with `false` (`true` by default).

```yaml
cache:
  templates: true
```

:::info
See [templates cache documentation](3-Templates.md#cache) for more details.
:::

### cache.translations

Disables translations cache  with `false` (`true` by default).

```yaml
cache:
  translations: true
```

---

## Server

### server.headers

You can define custom [HTTP headers](https://developer.mozilla.org/docs/Glossary/Response_header), used by the local preview server.

:::warning
Since version ++8.38.0++, the `headers` option has been moved to the `server.headers` section.
:::

```yaml
server:
  headers:
    - path: <path> # Relative path, prefixed with a slash. Support "*" wildcard.
      headers:
        - key: <key>
          value: "<value>"
```

:::tips
It's useful to test custom [Content Security Policy](https://developer.mozilla.org/docs/Web/HTTP/CSP) or [Cache-Control](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control).
:::

_Example:_

```yaml
server:
  headers:
    - path: /*
      headers:
        - key: X-Frame-Options
          value: "SAMEORIGIN"
        - key: X-XSS-Protection
          value: "1; mode=block"
        - key: X-Content-Type-Options
          value: "nosniff"
        - key: Content-Security-Policy
          value: "default-src 'self'; object-src 'self'; img-src 'self'"
        - key: Strict-Transport-Security
          value: "max-age=31536000; includeSubDomains; preload"
    - path: /assets/*
      headers:
        - key: Cache-Control
          value: "public, max-age=31536000"
    - path: /foo.html
      headers:
        - key: Foo
          value: "bar"
```

---

## Optimize

The optimization options allow to enable compression of output files: HTML, CSS, JavaScript and image.

```yaml
optimize:
  enabled: false     # enables files optimization (`false` by default)
  html:
    enabled: true    # enables HTML files optimization
    ext: [html, htm]   # supported files extensions
  css:
    enabled: true    # enables CSS files optimization
    ext: [css]         # supported files extensions
  js:
    enabled: true    # enables JavaScript files optimization
    ext: [js]          # supported files extensions
  images:
    enabled: true    # enables images files optimization
    ext: [jpeg, jpg, png, gif, webp, svg, avif] # supported files extensions
```

This option is disabled by default and can be enabled via:

```yaml
optimize: true
```

Once the global option is enabled, the 4 file types will be processed.  
It is possible to disable each of them via `enabled: false` and modify processed files extension via `ext`.

:::tips
It is also possible to enable this option through CLI when using the "build" and "serve" commands via the `--optimize` option.
:::

:::important
**Images** compressor will use these binaries if they are present in the system: [JpegOptim](https://github.com/tjko/jpegoptim), [Optipng](http://optipng.sourceforge.net/), [Pngquant 2](https://pngquant.org/), [SVGO](https://github.com/svg/svgo), [Gifsicle](http://www.lcdf.org/gifsicle/), [cwebp](https://developers.google.com/speed/webp/docs/cwebp) and [avifenc](https://github.com/AOMediaCodec/libavif).
:::

---

## Override configuration

### Environment variables

The configuration can be overridden through [environment variables](https://en.wikipedia.org/wiki/Environment_variable).

Each environment variable name must be prefixed with `CECIL_` and the configuration key must be set in uppercase.

For example, the following command set the website’s `baseurl`:

```bash
export CECIL_BASEURL="https://example.com/"
```

### CLI option

You can combine multiple configuration files, with the `--config` option (left-to-right precedence):

```bash
php cecil.phar --config config-1.yml,config-2.yml
```
