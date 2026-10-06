<!--
title: "Pages"
description: "Pages directory, sorting, pagination, paths, body, virtual pages, generators, etc."
date: 2021-05-07
updated: 2026-10-05
-->
# Pages

## pages.dir

Directory source of pages (`pages` by default).

```yaml
pages:
  dir: pages
```

## pages.ext

Extensions of pages files.

```yaml
pages:
  ext: [md, markdown, mdown, mkdn, mkd, text, txt]
```

## pages.exclude

Directories, paths and files name to exclude (accepts globs, strings and regexes).

```yaml
pages:
  exclude: ['vendor', 'node_modules', '*.scss', '/\.bck$/']
```

## pages.prefix.separator

List of characters used as separator between a filename prefix (`date` or `weight`) and the slug.

```yaml
pages:
  prefix:
    separator: ['-', '_']
```

## pages.sortby

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

## pages.pagination

Pagination is available for list pages (_type_ is `homepage`, `section` or `term`).

```yaml
pages:
  pagination:
    max: 5     # maximum number of entries per page
    path: page # path to the paginated page
```

### Disable pagination

Pagination can be disabled:

```yaml
pages:
  pagination: false
```

## pages.paths

Apply a custom [`path`](../content/6-front-matter.md#predefined-variables) for all pages of a **_Section_**.

```yaml
pages:
  paths:
    - section: <section’s ID>
      path: <path of pages>
```

### Path placeholders

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

## pages.frontmatter

Page front matter format (`yaml` by default, also accepts `ini`, `toml` and `json`).

```yaml
pages:
  frontmatter: yaml
```

## pages.body

Page body options.

:::info
To know how those options impacts your content see _[Content > Markdown](../content/7-markdown.md)_ documentation.
:::

### pages.body.toc

Headers used to build the table of contents (`[h2, h3]` by default).

```yaml
pages:
  body:
    toc: [h2, h3]
```

### pages.body.highlight

Enables code syntax highlighting (`true` by default).

```yaml
pages:
  body:
    highlight: false # set to false to disable syntax highlighting
```

### pages.body.images

Images handling options.

```yaml
pages:
  body:
    images:
      formats: []       # adds alternative image formats as `source` (e.g. `[avif, webp]`, empty array by default)
      resize: 0         # resizes all images to <width> (in pixels, `0` to disable)
      responsive: false # adds responsive image variants to the `srcset` attribute (`false` by default)
      lazy: true        # adds `loading="lazy"` attribute (`true` by default)
      decoding: true    # adds `decoding="async"` attribute (`true` by default)
      caption: false    # puts the image in a <figure> element and adds a <figcaption> containing the title (`false` by default)
      placeholder: ''   # fills the <img> background before loading ('color' or 'lqip', empty by default)
      class: ''         # sets a default class on each image (empty by default)
      dark_suffix: ''   # suffix of the dark variant image (e.g. `.dark`), disabled by default
      mobile_suffix: '' # suffix of the mobile variant image (e.g. `.mobile`), disabled by default
      mobile_media_query: '(max-width: 767px)' # media query of the mobile variant `<source>`
      remote:           # remote image handling (set to `false` to disable)
        fallback:         # path to the fallback image, stored in assets dir (empty by default)
```

:::warning
Since version ++8.41.0++, the `pages.body.images.resize` option is used to resize images to a specific width, no more to enable the resize feature (enabled systematically).
:::

:::important
Global options, like responsives images widths and sizes, are configurable in the [`assets.images`](27-assets.md#assets-images) section.
:::

:::info
Remote images are downloaded and converted into _Assets_ to be manipulated. You can disable this behavior by setting the option `pages.body.images.remote.enabled` to `false`.
:::

:::tip
When `dark_suffix` is set (e.g. `dark_suffix: .dark`), Cecil automatically looks for a dark variant of each image (e.g. `photo.dark.jpg` alongside `photo.jpg`). If found, the image is wrapped in a `<picture>` element with a `<source media="(prefers-color-scheme: dark)">` for automatic light/dark theme switching. Works in combination with `formats` and `responsive`.

In the same way, when `mobile_suffix` is set (e.g. `mobile_suffix: .mobile`), Cecil looks for a mobile variant of each image (e.g. `photo.mobile.jpg` alongside `photo.jpg`) and adds a `<source>` element with the `mobile_media_query` media query (`(max-width: 767px)` by default). If `dark_suffix` is also set, the dark variant of the mobile image (e.g. `photo.mobile.dark.jpg`) is used on mobile with dark color scheme. Mobile sources are placed before dark sources, so that a mobile variant takes precedence.
:::

### pages.body.links

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

### pages.body.excerpt

Excerpt handling options.

```yaml
pages:
  body:
    excerpt:
      separator: excerpt|break # string to use as separator (`excerpt|break` by default)
      capture: before          # part to capture, `before` or `after` the separator (`before` by default)
```

## pages.virtual

Virtual pages is the best way to create pages without content (**front matter only**).

It consists of a list of pages with a `path` and some front matter variables.

_Example:_

```yaml
pages:
  virtual:
    - path: code
      redirect: https://github.com/ArnaudLigny
```

## pages.default

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
      excluded: true
    robots:
      path: robots
      title: Robots.txt
      layout: robots
      output: txt
      published: true
      excluded: true
      multilingual: false
    sitemap:
      path: sitemap
      title: XML sitemap
      layout: sitemap
      output: xml
      changefreq: monthly
      priority: 0.5
      published: true
      excluded: true
      multilingual: false
    xsl/atom:
      path: xsl/atom
      layout: feed
      output: xsl
      uglyurl: true
      published: true
      excluded: true
    xsl/rss:
      path: xsl/rss
      layout: feed
      output: xsl
      uglyurl: true
      published: false
      excluded: true
```

:::info
The structure is almost identical of [`pages.virtual`](#pages-virtual), except the named key.
:::

Each one can be:

1. disabled: `published: false`
2. excluded from list pages: `excluded: true`
3. excluded from localization: `multilingual: false`

:::tip
Since version 8.68.0 you can override the default `robots.txt` page by creating a page with the same `path`:

_pages/robots.md_

```yaml
---
layout: robots
output: txt
---
User-agent: AI-bot
Disallow: /
```

:::

## pages.generators

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
You can extend Cecil with [Pages generator](../developers/41-extend.md#pages-generator).
:::

## pages.subsets

Subsets are used to render a part of the pages collection, based on a specific path, language or output format, with the command:

```bash
cecil build --render-subset=<name>
```

```yaml
pages:
  subsets:
    <name>:
      path: <path> # glob or string path (e.g.: `blog/*`, `blog`)
      language: <language> # language code (e.g.: `en`, `fr`)
      output: <output> # output format (e.g.: `html`, `atom`)
```

_Example:_

```yaml
pages:
  subsets:
    blog_en:
      path: blog
      language: en
      output: html
    search_index:
      path: '*'
      output: json
```

---
