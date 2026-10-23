# Configuration

Structure of the most used `cecil.yml` options. The merge and priority rules are in [SKILL.md](../SKILL.md#configuration); all options and their defaults are in [`config/default.php`](https://github.com/Cecilapp/Cecil/blob/main/config/default.php) (`php cecil.phar show:config` displays the effective configuration).

## Contents

- [Site](#site)
- [Menus](#menus)
- [Taxonomies](#taxonomies)
- [Environment Variables](#environment-variables)
- [Pages](#pages)
- [Data and Static Files](#data-and-static-files)
- [Output Formats](#output-formats)
- [Cache](#cache)
- [Server Headers](#server-headers)
- [Optimization](#optimization)

## Site

```yaml
title: "My site"
baseline: "A short tagline"     # homepage <title>: title + baseline
baseurl: https://example.com/   # must end with /
canonicalurl: false             # `true`: url() returns absolute URLs
description: "Site description"
author:                         # or a plain string
  name: Jane Doe
  url: https://jane.tld
  email: jane@jane.tld
social:                         # used by metatags; each `url` also adds a rel=me link
  twitter:  {url: https://x.com/jane, site: jane, creator: jane}
  mastodon: {url: https://mastodon.social/@jane, creator: "@jane@mastodon.social"}
  facebook: {url: https://facebook.com/jane, id: 123456789, username: jane}
image: images/social.png        # default social image
date:
  format: 'j F, Y'              # PHP date format, default format of the `date` filter (`F j, Y` by default)
  timezone: 'Europe/Paris'
theme: [my-theme]               # or a single name; the first theme overrides the next ones
debug: false                    # Twig dump()/d(), Sass source maps
```

`metatags` options (title divider, robots, Open Graph, JSON-LD, etc.) are described in [templates.md](templates.md#metatags-partialsmetatagshtmltwig); languages in [multilingual.md](multilingual.md).

## Menus

Entries not linked to a page (pages join a menu with their `menu` front matter variable, see [content.md](content.md#menus)):

```yaml
menus:
  main:
    - id: about          # required and unique (an entry without id is skipped)
      name: "About"
      url: /about/       # relative or absolute URL
      weight: 1          # lighter first
  footer:
    - id: author
      name: The author
      url: https://jane.tld
      weight: 99
```

Override or disable an automatic entry (homepage, top-level sections, pages) with its page ID as `id` (`index` for the homepage); the ID has no language prefix, so it targets the entry in every language:

```yaml
menus:
  main:
    - id: index
      name: "Home"
      weight: 1
    - id: blog
      enabled: false
```

Localize menus under `languages[].config.menus` (see [multilingual.md](multilingual.md#languages)).

## Taxonomies

```yaml
taxonomies:
  categories: category   # plural: singular
  tags: tag
```

There are no default vocabularies: declare them before using them in front matter. A vocabulary declared by a theme can be disabled with `tags: disabled`.

## Environment Variables

Any option can be set with a `CECIL_` environment variable, typically in CI or for a deployment target:

- Uppercase key, nested keys separated with `_`: `CECIL_BASEURL`, `CECIL_PAGES_DIR` (`pages.dir`), `CECIL_OPTIMIZE_ENABLED`. Keys containing an `_` can't be set this way.
- Boolean-like values (`true`/`false`, `1`/`0`, `yes`/`no`, `on`/`off`) are converted to booleans.
- A `.env` file at the site root is loaded at startup; variables already defined by the shell are preserved.

```dotenv
CECIL_BASEURL="https://example.com/"
CECIL_TITLE="My Cecil site"
```

`--config=a.yml,b.yml` merges files after `cecil.yml`, from left to right: scalars are replaced, maps merged, lists of values replaced, output formats merged by `name`, and other lists of entries (e.g. menus) concatenated.

## Pages

```yaml
pages:
  dir: pages
  ext: [md, markdown, mdown, mkdn, mkd, text, txt]
  exclude: [vendor, node_modules]  # directories, paths and files (globs, strings and regexes)
  sortby: date                     # date, updated, title or weight
  pagination: {max: 5, path: page} # `false` to disable
  paths:                           # permalinks, see content.md
    - section: blog
      path: :section/:year/:slug
  default:                         # default pages, indexed by ID
    sitemap: {published: false}    # disable a built-in default page
    about:                         # page without content file
      path: about
      title: About
      layout: about
  subsets:                         # pages subsets, rendered with `build --render-subset=<name>`
    blog_en:
      path: blog                   # glob or path
      language: en
      output: html
```

- Built-in default pages: `index` (homepage), `404`, `robots` (`robots.txt`), `sitemap` (`sitemap.xml`) and `xsl/*` (feeds and sitemap styles).
- Customize `robots.txt` with a `pages/robots.md` page (`layout: robots`, `output: txt`): its body is appended to the default rules (see [ai.md](ai.md#block-ai-crawlers)). Don't add a `static/robots.txt`.
- `pages.body.*` options (images, links, highlight, toc, excerpt) are described in [content.md](content.md).

## Data and Static Files

```yaml
data:
  ext: [yaml, yml, json, xml, csv]
  load: true            # `site.data`
static:
  exclude: [sass, scss, '*.scss', 'package*.json', node_modules] # a site list replaces this default: keep these values
  load: false           # `true` to expose `site.static` in templates
  target: ''            # output subdirectory
  mounts:               # copy a file or directory of `static/` to an output path
    vendor/icons/fonts: fonts
```

Static files used by `asset()` belong in `assets/`, not in `static/` (they are published only when used).

## Output Formats

Default formats: `html`, `atom`, `rss`, `json`, `xml`, `txt`, `amp`, `js`, `webmanifest`, `xsl`, `jsonfeed`, `oembed`, `embed`, `markdown`, `llms`. Use these names (e.g. `rss`, not `feed`).

Default formats per page type: `page: [html]`, `homepage: [html, atom]`, `section: [html, atom]`, `vocabulary: [html]`, `term: [html, atom]`.

```yaml
output:
  dir: _site
  formats:
    - name: rss                       # override a default format: name + changed properties
      filename: feed                  # feed.xml
    - name: csv                       # new format: name, mediatype and extension are required
      mediatype: text/csv
      extension: csv
      exclude: [redirect, paginated]  # skip pages with these variables
  pagetypeformats:
    section: [html, rss]              # a list replaces the defaults (Atom is removed here)
    homepage: {rss: true}             # a map of booleans adds or removes formats
```

A page can also set its formats with the `output` front matter variable (`output: [html, json]`). Each format is rendered with the matching template (`<layout>.<format>.twig`, e.g. `list.rss.twig`).

## Cache

```yaml
cache:
  enabled: true
  dir: .cache          # can be absolute (e.g. shared in CI)
  templates: true      # `false` while developing templates
  translations: true
  assets:
    ttl: ~             # no expiration
    remote:
      ttl: 604800      # remote assets: 7 days
```

## Server Headers

HTTP headers applied **only by the local `serve` server** (not by the hosting platform):

```yaml
server:
  headers:
    - path: /*
      headers:
        - key: Content-Security-Policy
          value: "default-src 'self'"
```

Each rule requires a `path`, and each header a `key` and a `value` (under `server.headers`, not a top-level `headers` key). To deploy them, use the `headers` component theme, which generates a `_headers` file (Netlify, statichost, Cloudflare Pages).

## Optimization

```yaml
optimize:
  enabled: true        # or `optimize: true`, or `build --optimize`
  html: {enabled: true}
  css: {enabled: true}
  js: {enabled: true}
  images: {enabled: false}
```

Images optimization uses the binaries available on the system: JpegOptim, Optipng, Pngquant, SVGO, Gifsicle, cwebp and avifenc.
