<!--
description: "Configure your website."
date: 2021-05-07
updated: 2023-03-21
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
canonicalurl: <bool> # false by default
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
A vocabulary can be disabled with the special value `disabled`. Example: `tags: disabled`.
:::

### menus

A menu is made up of a unique name and entry’s properties.

```yaml
menus:
  <name>:
    - id: <unique id> # unique identifier (required)
      name: "<name>"  # name usable in templates
      url: <url>      # relative or absolute URL
      weight: <int>   # integer value used to sort entries (lighter first)
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

- Page title + site title or site title + site baseline
- Page/site description
- Page/site keywords
- Page/site author
- Search engine crawler directives
- Favicon links
- Previous and next page links
- Pagination links (first, previous, next, last)
- Canonical URL
- Links to alternates (e.g.: RSS feed, others languages)
- Open Graph
- Twitter Card
- JSON-LD site and article metadata

#### metatags options and variables

Cecil uses page’s front matter to feed meta tags, and fallbacks to the site configuration if needed.

```yaml
title: "Page/site title"
description: "Page/site description"
tags: [tag1, tag2] # feeds keywords meta
keywords: [keyword1, keyword2] # obsolete
author: "Author name"
image: image.jpg # used by OpenGraph and Twitter Card
canonical: # used to override the generated canonical URL
  url: <URL>
  title: "<URL title>" # optional
social:
  twitter:
    site: username
    creator: username
  facebook:
    id: 123456789
    firstname: Firstname
    lastname: Lastname
    username: username
```

:::tip
If needed the title can be overridden:

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
      - "shortcut icon": [196]                  # Android
      - "apple-touch-icon": [120, 152, 180]     # iOS
```

### language

The main language, defined by its code.

```yaml
language: <language code> # unique code (`en` by default)
```

### languages

List of available languages, used for [pages](2-Content.md#multilingual) and [templates](3-Templates.md#localization) localization.

```yaml
languages:
  - code: <code>     # unique code (e.g.: `en`, `fr`, 'en-US', `fr-CA`)
    name: <name>     # human readable name (e.g.: `Français`)
    locale: <locale> # locale code (`language_COUNTRY`, e.g.: `en_US`, `fr_FR`, `fr_CA`)
```

:::info
The language code is used to define the path to pages in a different language of the default one (e.g.: `/fr/a-propos/`).  
:::

:::info
A list of [locales code](configuration/locale-codes.md) is available.
:::

#### Localize configuration options

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
If an option is not available in the current language (e.g.: `fr`) it fallback to the global one.
:::

### theme

The theme name or an array of themes name.

```yaml
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
See [themes on GitHub](https://github.com/Cecilapp?q=theme#org-repositories) or website [themes section](https://cecil.app/themes/).
:::

### pagination

Pagination is available for list pages (_type_ is `homepage`, `section` or `term`).

```yaml
pagination:
  max: <int>   # maximum number of entries per page (`5` by default)
  path: <path> # path to the paginated page (`page` by default)
```

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

### googleanalytics

[Google Analytics](https://wikipedia.org/wiki/Google_Analytics) user identifier:

```yaml
googleanalytics: UA-XXXXX
```

The _Universal Analytics_ ID is used by the built-in partial template [`googleanalytics.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/googleanalytics.js.twig).

### virtualpages

Virtual pages is the best way to create pages without content (**front matter only**).

It consists of a list of pages with a `path` and some front matter variables.

_Example:_

```yaml
virtualpages:
  - path: code
    redirect: https://github.com/ArnaudLigny
```

### output

Defines where and in what format(s) content is rendered.

#### dir

Directory where rendered pages’ files are saved.

```yaml
output:
  dir: <directory> # `_site` by default
```

#### formats

List of output formats, in which of them pages’ content is rendered (e.g. HTML, JSON, XML, RSS, Atom, etc.).

```yaml
output:
  formats:
    - name: <name>            # name of the format, e.g.: `html` (required)
      mediatype: <media type> # media type (MIME), ie: 'text/html' (optional)
      subpath: <sub path>     # sub path, e.g.: `amp` in `path/amp/index.html` (optional)
      filename: <file name>   # file name, e.g.: `index` in `path/index.html` (optional)
      extension: <extension>  # file extension, e.g.: `html` in `path/index.html` (required)
      exclude: [<variable>]   # don’t apply this format to pages identified by listed variables, e.g.: `[redirect]` (optional)
```

Those formats are used by `pagetypeformats` (see below) and by the [`output` page’s variable](2-Content.md#output).

:::info
To render a page, [Cecil searches a template](3-Templates.md#lookup-rules) named `<layout>.<format>.twig` (e.g. `page.html.twig`)
:::

#### pagetypeformats

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

### paths

Defines a custom [`path`](2-Content.md#variables) for all pages of a _Section_.

```yaml
paths:
  - section: <section’s name>
    language: <language> # optional
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
    path: :section/:year/:month/:day/:slug # e.g.: /blog/2020/12/01/my-post/
  - section: Blog
    language: fr
    path: blogue/:year/:month/:day/:slug # e.g.: /blogue/2020/12/01/mon-billet/
```

### debug

Enables the _debug mode_, used to display debug information like Twig dump, Twig profiler, SCSS sourcemap, etc.

```yaml
debug: <true|false>
```

There is 2 others way to enable the _debug mode_:

1. Run a command with the `-vvv` option
2. Set the `CECIL_DEBUG` environment variable to `true`

## Default configuration

The website configuration (`config.yml`) overrides the [default configuration](https://github.com/Cecilapp/Cecil/blob/master/config/default.php).

### defaultpages

Default pages are pages created automatically by Cecil, from built-in templates:

- _index.html_ (home page)
- _404.html_
- _robots.txt_
- _sitemaps.xml_
- _atom.xsl_
- _rss.xsl_

:::info
The structure is almost identical of [`virtualpages`](#virtualpages), except the named key.
:::

```yaml
defaultpages:
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

Each one can be:

1. disabled: `published: false`
2. excluded from list pages: `exclude: true`
3. excluded from localization: `multilingual: false`

### pages

Where pages’ files (Markdown or plain text) are stored.

```yaml
pages:
  dir: pages                                       # pages files directory (`pages` by default, previously `content`)
  ext: [md, markdown, mdown, mkdn, mkd, text, txt] # supported files formats, by extension
  exclude: [vendor, node_modules]                  # directories, paths and files name to exclude (accepts globs, strings and regexes)
```

#### frontmatter

Pages’ variables format (YAML by default).

```yaml
frontmatter:
  format: yaml # front matter format: `yaml`, `ini`, `toml` or `json` (`yaml` by default)
```

#### body

Pages’ content format and converter’s options.

```yaml
body:
  format: md          # page body format (only `md`, Markdown, is supported)
  toc: [h2, h3]       # headers used to build the table of contents
  highlight:
    enabled: false    # enables code syntax highlighting (`false` by default)
  images:             # how to handle images
    lazy:
      enabled: true   # adds `loading="lazy"` attribute (`true` by default)
    decoding:
      enabled: true   # adds `decoding="async"` attribute (`true` by default)
    resize:
      enabled: false  # enables image resizing by using the `width` extra attribute (`false` by default)
    webp:
      enabled: false  # adds a WebP image as a `source` (`false` by default)
    responsive:
      enabled: false  # creates responsive images and add them to the `srcset` attribute (`false` by default)
    class: ''         # put default class to each image (empty by default)
    caption:
      enabled: false  # puts the image in a <figure> element and adds a <figcaption> containing the title (`false` by default)
    remote:
      enabled: true   # enables remote image handling (`true` by default)
      fallback:
        enabled: false # enables a fallback if image is not found (`false` by default)
        path: ''       # path to the fallback image, stored in assets dir (empty by default)
  links:
    embed:
      enabled: false # turns links in embedded content if possible (`false` by default)
      video:
        ext: [mp4, 'webm'] # video files extensions
      audio:
        ext: [mp3] # audio files extensions
  excerpt:
    separator: excerpt|break # string to use as separator (`excerpt|break` by default)
    capture: before          # part to capture, `before` or `after` the separator (`before` by default)
```

To know how those options impacts your content see _[Content > Markdown](2-Content.md#markdown)_ documentation.

:::info
Remote images are downloaded (and converted into _Assets_ to be manipulated). You can disable this behavior by setting the option `body.images.remote.enabled` to `false`.
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
You should put your assets files, used by [`asset()`](3-Templates.md#asset), in the [`assets` directory](4-Configuration.md#assets) to avoid unnecessary files copy.
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

### translations

Where and in what format translations files are stored.

```yaml
translations:
  dir: translations       # translations directory
  formats: ['yaml', 'mo'] # translations files format (`yaml` and `mo` by default)
```

### assets

Assets handling options.

```yml
assets:
  dir: assets            # assets files directory (`assets` by default)
  target: assets         # where remote and resized assets are saved
  fingerprint:
    enabled: true        # enables fingerprinting (`true` by default)
  compile:
    enabled: true        # enables Sass files compilation (`true` by default)
    style: expanded      # compilation style (`expanded` or `compressed`. `expanded` by default)
    import: [sass, scss] # list of imported paths (`[sass, scss, node_modules]` by default)
    sourcemap: false     # enables sourcemap in debug mode (`false` by default)
    variables: []        # list of preset variables (empty by default)
  minify:
    enabled: true        # enables CSS et JS minification (`true` by default)
  images:
    resize:
      dir: thumbnails    # where resized images are stored (`thumbnails` by default)
    optimize:
      enabled: false     # enables images optimization with JpegOptim, Optipng, Pngquant 2, SVGO 1, Gifsicle, cwebp (`false` by default)
    quality: 75          # image quality after optimization or resize (`75` by default)
    responsive:
      widths: []         # `srcset` widths (`[480, 640, 768, 1024, 1366, 1600, 1920]` by default)
      sizes:
        default: '100vw' # default `sizes` attribute (`100vw` by default)
      enabled: false     # `html` filter: creates responsive images (`false` by default)
    webp:
      enabled: false     # `html` filter: creates and adds a WebP image as a `source` (`false` by default)
```

:::
**Notes:**

- See [documentation of scssphp](https://scssphp.github.io/scssphp/docs/#output-formatting) for details about `style` compilation and `variables`
- `minify` is available for file with a `text/css` or `text/javascript` MIME Type
- Enables sourcemap output requires [debug mode](#debug) is enabled
:::

#### Image CDN

If the option `assets.images.cdn` is enabled then URL of assets will be replaced by the provided CDN `url`.

```yaml
assets:
  images:
    cdn:
      enabled: false  # enables Image CDN (`false` by default)
      canonical: true # is `image_url` must be canonical or not (`true` by default)
      remote: true    # includes remote images (`true` by default)
      account: 'xxxx' # provider account
      url: 'https://provider.tld/%account%/%image_url%?w=%width%&q=%quality%&format=%format%'
```

`url` is a pattern that contains variables:

- `%account%` replaced by the `assets.images.cdn.account` option
- `%image_url%` replaced by the asset `path` or the URL of the remote image
- `%width%` replaced by the image width
- `%quality%` replaced by the `assets.images.quality` option
- `%format%` replaced by the image format

##### CDN provider examples

###### [Cloudinary](https://cloudinary.com)

```yaml
assets:
  images:
    cdn:
      enabled: true
      account: 'xxxx'
      url: 'https://res.cloudinary.com/%account%/image/fetch/c_limit,w_%width%,q_%quality%,f_%format%,d_default/%image_url%'
```

###### [Cloudimage](https://www.cloudimage.io)

```yaml
assets:
  images:
    cdn:
      enabled: true
      account: 'xxxx'
      url: 'https://%account%.cloudimg.io/%image_url%?w=%width%&q=%quality%&force_format=%format%'
```

###### [TwicPics](https://www.twicpics.com)

```yaml
assets:
  images:
    cdn:
      enabled: true
      account: 'xxxx'
      canonical: false
      remote: false
      url: 'https://%account%.twic.pics/%image_url%?twic=v1/resize=%width%/quality=%quality%/output=%format%'
```

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

Images compressor will use these binaries if they are present in the system: [JpegOptim](https://github.com/tjko/jpegoptim), [Optipng](http://optipng.sourceforge.net/), [Pngquant 2](https://pngquant.org/), [SVGO](https://github.com/svg/svgo), [Gifsicle](http://www.lcdf.org/gifsicle/) and [cwebp](https://developers.google.com/speed/webp/docs/cwebp).

### cache

Cache options.

```yaml
cache:
  dir: '.cache' # cache directory
  enabled: true # enables cache
  templates:    # Twig templates cache
    dir: templates # templates cache directory
    enabled: true  # enables templates cache
  assets:       # assets cache
    dir: 'assets/remote' # the subdirectory of remote assets cache
  translations:
    dir: 'translations' # translations cache directory
    enabled: true       # enables translations cache
```

### generators

Generators are used by Cecil to create additional pages (e.g.: sitemap, feed, pagination, etc.) from existing pages, or from other sources like the configuration file or external sources.

Below the list of Generators provided by Cecil, in a defined order:

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

#### Custom generator

It is possible to create a custom Generator, just add it to the list above, and create a new class in the `Cecil\Generator` namespace.

**Example:**

_/generators/Cecil/Generator/MyGenerator.php_

```php
<?php
namespace Cecil\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;

class MyGenerator extends AbstractGenerator implements GeneratorInterface
{
    public function generate(): void
    {
        // create a new page $page, then add it to the site collection

        $page = (new Page('my-page'))
          ->setType(Type::PAGE)
          ->setPath('mypage')
          ->setBodyHtml('<p>My page body</p>')
          ->setVariable('language', 'en')
          ->setVariable('title', 'My page')
          ->setVariable('date', now())
          ->setVariable('menu', ['main' => ['weight' => 99]]);

        $this->generatedPages->add($page);
    }
}
```

## Override configuration

### Environment variables

The configuration can be overridden through [environment variables](https://en.wikipedia.org/wiki/Environment_variable).

Each environment variable name must be prefixed with `CECIL_` and the configuration key must be set in uppercase.

For example, the following command set the website’s `baseurl`:

```bash
export CECIL_BASEURL="https://example.com/"
```

:::important
Only existing configuration options can be overridden: you can’t create new configuration options with environment variables.
:::
