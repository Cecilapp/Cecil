<!--
description: "Configure your website."
date: 2021-05-07
updated: 2025-03-26
-->

# Configuration

The website configuration is defined in a [YAML](https://en.wikipedia.org/wiki/YAML) file named `cecil.yml` by default and stored at the root:

```plaintext
<mywebsite>
└─ cecil.yml
```

_Example:_

```yaml
title: "Cecil"
baseline: "Your content driven static site generator."
baseurl: https://cecil.local/
language: en
```

:::info
Your site configuration overrides the following [default configuration](https://github.com/Cecilapp/Cecil/blob/master/config/default.php).
:::

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
Since ++version 8.35.0++, default vocabularies `category` and `tag` have been removed.
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

### Debug

Enables the _debug mode_, used to display debug information like Twig dump, Twig profiler, SCSS sourcemap, etc.

```yaml
debug: <true|false>
```

There is 2 others way to enable the _debug mode_:

1. Run a command with the `-vvv` option
2. Set the `CECIL_DEBUG` environment variable to `true`

When `debug` is enabled, you can easily [dump a variable in your templates](3-Templates.md#dump) using:

```twig
{{ dump(variable) }}
# or
{{ d(variable) }} # HTML dump
```

---

## Pages

### pages.dir

**Type:** `string`
**Default:** `pages`

Directory where pages files are stored.

### pages.ext

**Type:** `array`
**Default:** `['md', 'markdown', 'mdown', 'mkdn', 'mkd', 'text', 'txt']`

Supported files formats, by extension.

### pages.exclude

**Type:** `array`
**Default:** `['vendor', 'node_modules']`

Directories, paths and files name to exclude (accepts globs, strings and regexes).

### pages.sortby

**Type:** `string` | `array`
**Default:** `date`

Default collections sort method.

#### pages.sortby.variable

**Type:** `string`
**Default:** `date`

Sort method: `date`, `updated`, `title` or `weight`.

#### pages.sortby.desc_title

**Type:** `boolean`
**Default:** `false`

Sort by title in descending order.

#### pages.sortby.reverse

**Type:** `boolean`
**Default:** `false`

Reverse the sort order.

### pages.pagination

Pagination is available for list pages (_type_ is `homepage`, `section` or `term`).

#### pages.pagination.max

**Type:** `integer`
**Default:** `5`

Maximum number of entries per page.

#### pages.pagination.path

**Type:** `string`
**Default:** `page`

Path to the paginated page.

_Example:_

```yaml
pagination:
  max: 10
  path: page
```

#### Disable pagination

Pagination can be disabled:

```yaml
pagination:
  enabled: false
```

### pages.paths

Defines a custom [`path`](2-Content.md#variables) for all pages of a **_Section_**.

```yaml
paths:
  - section: <section’s ID>
    language: <language code> # optional
    path: <path of pages> # with optional placeholders
```

#### Path placeholders

- `:year`
- `:month`
- `:day`
- `:section`
- `:slug`

_Example:_

```yaml
paths:
  - section: Blog
    path: :section/:year/:month/:day/:slug # e.g.: /blog/2020/12/01/my-post/
  - section: Blog
    language: fr
    path: blogue/:year/:month/:day/:slug # e.g.: /blogue/2020/12/01/mon-billet/
```

### pages.frontmatter

**Type:** `string`
**Default:** `yaml`

Front matter format: `yaml`, `ini`, `toml` or `json`. `yaml` is the default format.

### pages.body

Pages’ body options.

#### pages.body.toc

**Type:** `array`
**Default:** `['h2', 'h3']`

Headers used to build the table of contents.

#### pages.body.highlight

**Type:** `boolean`
**Default:** `false`

Enables code syntax highlighting.

```yaml
pages:
  body:
    toc: [h2, h3]       # headers used to build the table of contents
    highlight: false    # enables code syntax highlighting (`false` by default)
    images:             # how to handle images
      formats: []         # creates and adds formats images as `source` (e.g. `webp`, empty by default)
      resize: false       # enables image resizing by using the `width` extra attribute (`false` by default)
      responsive: false   # creates responsive images and add them to the `srcset` attribute (`false` by default)
      lazy: true          # adds `loading="lazy"` attribute (`true` by default)
      decoding: true      # adds `decoding="async"` attribute (`true` by default)
      caption: false      # puts the image in a <figure> element and adds a <figcaption> containing the title (`false` by default)
      placeholder: ''     # fill <img> background before loading ('color' or 'lqip', empty by default)
      class: ''           # put default class to each image (empty by default)
      remote:             # remote image handling (set to `false` to disable)
        fallback:           # path to the fallback image, stored in assets dir (empty by default)
      
    links:
      embed:
        enabled: false # turns links in embedded content if possible (`false` by default)
        video:
          ext: [mp4, 'webm'] # video files extensions
        audio:
          ext: [mp3]         # audio files extensions
      external:
        blank: false     # if true open external link in new tab
        noopener: true   # add "noopener" to `rel`  attribute
        noreferrer: true # add "noreferrer" to `rel`  attribute
        nofollow: true   # add "nofollow" to `rel`  attribute
    excerpt:
      separator: excerpt|break # string to use as separator (`excerpt|break` by default)
      capture: before          # part to capture, `before` or `after` the separator (`before` by default)
```

To know how those options impacts your content see _[Content > Markdown](2-Content.md#markdown)_ documentation.

:::info
Remote images are downloaded (and converted into _Assets_ to be manipulated). You can disable this behavior by setting the option `pages.body.images.remote.enabled` to `false`.
:::

### pages.virtual

**Type:** `array`
**Default:** `[]`

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
    atom:
      path: atom
      layout: feed
      output: xsl
      uglyurl: true
      published: true
      exclude: true
    rss:
      path: rss
      layout: feed
      output: xsl
      uglyurl: true
      published: true
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

---

## Data

Where data files are stored and what extensions are handled.

```yaml
data:
  dir: data                        # data directory
  ext: [yaml, yml, json, xml, csv] # array of files extensions.
  load: true                       # enables `site.data` collection
```

Supported formats: YAML, JSON, XML and CSV.

---

## Static

Where static files are stored (PDF, fonts, etc.).

```yaml
static:
  dir: static # files directory
  target: ''  # target directory
  exclude: ['sass', 'scss', '*.scss', 'package*.json', 'node_modules'] # list of excluded files (accepts globs, strings and regexes)
  load: false # enables `site.static` collection (`false` by default)
  mounts: [] # allows to copy specific files or directories to a specific destination
```

:::important
You should put your assets files, used by [`asset()`](3-Templates.md#asset), in the [`assets` directory](4-Configuration.md#assets) to avoid unnecessary files copy.
:::

:::tip
If you use [Bootstrap Icons](https://icons.getbootstrap.com) you can exclude the `node_modules` except `node_modules/bootstrap-icons` with a regular expression:

```yaml
exclude: ['sass', 'scss', '*.scss', 'package*.json', '#node_modules/(?!bootstrap-icons)#']
```

:::

_Example:_

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

Assets handling options.

```yml
assets:
  dir: assets            # assets files directory (`assets` by default)
  target: assets         # where remote and resized assets are saved
  fingerprint:
    enabled: true        # enables fingerprinting (`true` by default)
  compile:
    enabled: true        # enables Sass files compilation (`true` by default)
    style: expanded        # compilation style (`expanded` or `compressed`. `expanded` by default)
    import: [sass, scss]   # list of imported paths (`[sass, scss, node_modules]` by default)
    sourcemap: false       # enables sourcemap in debug mode (`false` by default)
    variables: []          # list of preset variables (empty by default)
  minify:
    enabled: true        # enables CSS et JS minification (`true` by default)
  images:
    resize:
      dir: thumbnails      # where resized images are stored (`thumbnails` by default)
    optimize:
      enabled: false       # enables images optimization with JpegOptim, Optipng, Pngquant 2, SVGO 1, Gifsicle, cwebp, avifenc (`false` by default)
    quality: 75            # image quality after optimization or resize (`75` by default)
    responsive:
      widths: []           # `srcset` widths (`[480, 640, 768, 1024, 1366, 1600, 1920]` by default)
      sizes:
        default: '100vw'   # default `sizes` attribute (`100vw` by default)
      enabled: false       # used by `html` filter: creates responsive images by default (`false` by default)
    formats: []            # used by `html` filter: creates and adds formats images as `source` (empty by default)
```

:::
**Notes:**

- `compile` is used for [SCSS](https://sass-lang.com) compilation. See the [documentation of scssphp](https://scssphp.github.io/scssphp/docs/#output-formatting) for options details
- `sourcemap` is used to debug SCSS compilation ([debug mode](#debug) must be enabled
- `minify` is available for file with a `text/css` or `text/javascript` [MIME Type](https://developer.mozilla.org/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types)
- Generated `responsive` images default widths are: 480, 640, 768, 1024, 1366, 1600 and 1920
:::

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

## Layouts

Where templates and translations files are stored.

```yaml
layouts:
  dir: layouts # layouts directory
  translations:
    dir: translations       # translations directory
    formats: ['yaml', 'mo'] # translations files format (`yaml` and `mo` by default)
```

## Themes

Where themes are stored.

```yaml
themes:
  dir: themes # themes directory
```

---

## Output

Defines where and in what format(s) content is rendered.

### output.dir

Directory where rendered pages’ files are saved.

```yaml
output:
  dir: <directory> # `_site` by default
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

Those formats are used by `pagetypeformats` (see below) and by the [`output` page’s variable](2-Content.md#output).

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

Several formats can be defined for the same type of page. For example the `section` page type can be automatically rendred in HTML and Atom.

_Example:_

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

```yaml
cache:
  enabled: true         # enables cache support (`true` by default)
  dir: '.cache'         # cache files directory (`.cache` by default)
  templates:
    enabled: true       # enables cache for Twig templates
    dir: templates      # templates files cache directory (`templates` by default)
  assets:
    dir: 'assets'       # assets files cache directory (`assets` by default)
    remote:
      dir: remote       # remote files cache directory (`remote` by default)
  translations:
    enabled: true       # enables cache for translations dictionary
    dir: 'translations' # translations files cache directory (`assets` by default)
```

---

## Headers

You can define custom [HTTP headers](https://developer.mozilla.org/docs/Glossary/Response_header), used by the local preview server.

:::warning
Should be move to `server.headers` in the future.
:::

```yaml
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
optimize:
  enabled: true
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
