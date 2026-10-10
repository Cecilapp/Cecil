<!--
title: "Site options"
description: "title, baseurl, menus, taxonomies, theme, date, metatags, debug, etc."
date: 2021-05-07
updated: 2026-10-10
-->
# Site options

These options define the main settings of the site: title, URL, description, menus, taxonomies, theme, metatags, etc.

## title

Main title of the site.

```yaml
title: "<site title>"
```

## baseline

Short description (~ 20 characters).

```yaml
baseline: "<baseline>"
```

## baseurl

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

## canonicalurl

If set to `true` the [`url()`](../templates/reference/1-functions.md#url) function will return the absolute URL (`false` by default).

```yaml
canonicalurl: <true|false> # false by default
```

## description

Site description (~ 250 characters).

```yaml
description: "<description>"
```

## menus

Menus are used to create [navigation links in templates](../templates/2-variables.md#site-menus).

A menu is made up of a unique ID and entry properties (name, URL, weight).

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
A `main` menu is automatically created with the home page entry and all sections entries ([See content management](../content/index.md))
:::

:::tip
A page can be added to a menu by setting the [`menu` variable](../content/2-front-matter.md#menu) in its front matter.
:::

### Override an entry

A page menu entry can be overridden: use the page ID as `id`.

_Example:_

```yaml
menus:
  main:
    - id: index
      name: "My amazing homepage!"
      weight: 1
```

### Disable an entry

A menu entry can be disabled with `enabled: false`.

_Example:_

```yaml
menus:
  main:
    - id: about
      enabled: false
```

## taxonomies

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

Then you can use those vocabularies in your content’s [front matter](../content/2-front-matter.md#taxonomy).

:::warning
Since ++version 8.37.0++, default vocabularies `category` and `tag` have been removed. You must define them in the configuration file if you want to use them.
:::

:::tip
A vocabulary can be disabled with the special value `disabled`. Example: `tags: disabled`.
:::

## theme

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

## date

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

## metatags

_metatags_ are SEO and social helpers that can be automatically injected in the `<head>`, with the template [`partials/metatags.html.twig`](https://github.com/Cecilapp/Cecil/blob/main/resources/layouts/partials/metatags.html.twig).

*[SEO]: Search Engine Optimization

This template adds the following meta tags:

- Page title + Site title, or Site title + Site baseline
- Page/Site description
- Page/Site keywords
- Page/Site author
- Search engine crawler directives (_robots_)
- Favicon links
- Navigation links (first, previous, next, last)
- Canonical URL
- Alternate links (i.e.: RSS feed, others languages)
- [`rel=me`](https://developer.mozilla.org/docs/Web/HTML/Reference/Attributes/rel/me) links
- [Open Graph](https://ogp.me)
- Facebook profile ID
- [Twitter/X Card](https://developer.x.com/docs/x-for-websites/cards/guides/getting-started)
- [Fediverse tag](https://blog.joinmastodon.org/2024/07/highlighting-journalism-on-mastodon/)
- [Dublin Core](https://www.dublincore.org/specifications/dublin-core/dcmi-terms/)
- [Structured data](https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data) (JSON-LD)

### metatags options

Cecil uses page front matter to feed meta tags, and falls back to site options when needed.

```yaml
title: "Page/Site title"              # used by title meta
description: "Page/Site description"  # used by description meta
tags: [tag1, tag2]                    # used by keywords meta
keywords: [keyword1, keyword2]        # obsolete
author:                               # used by author meta
  name: <name>                          # author name
  url: <url>                            # author URL
  email: <email>                        # author email
image: image.jpg                      # used by Open Graph and social networks cards
canonical:                            # used to override the generated canonical URL
  url: <URL>                            # absolute URL
  title: "<URL title>"                  # optional canonical title
social:                               # used by social networks meta
  twitter:                              # used by Twitter/X Card
    url: <URL>                            # used for `rel=me` link
    site: username                        # site username
    creator: username                     # page author username
  mastodon:                             # used by Mastodon meta
    url: <URL>                            # used for `rel=me` link
    creator: handle                       # page author account
  facebook:                             # used by Facebook meta
    url: <URL>                            # used for `rel=me` link
    id: 123456789                         # Facebook profile ID
    username: username                    # page author username
```

:::tip
If needed, `title` and `image` can be overridden:

```twig
{{ include('partials/metatags.html.twig', {title: 'Custom title', image: og_image}) }}
```

:::

### metatags configuration

```yaml
metatags:
  title:                   # title options
    divider: " &middot; "    # string between page title and site title
    only: false              # displays page title only (`false` by default)
    pagination:              # pagination options
      shownumber: true         # displays page number in title (`true` by default)
      label: "Page %s"         # how to display page number (`Page %s` by default)
  robots: "index,follow"   # web crawlers directives (`index,follow` by default)
  favicon:                 # favicon options
    enabled: true            # includes favicon (`true` by default)
    image: favicon.png       # path to favicon image
    sizes:                   # sizes by device
      - "icon": [32, 57, 76, 96, 128, 192, 228]  # web browsers
      - "shortcut icon": [196]                   # Android
      - "apple-touch-icon": [120, 152, 180]      # iOS
  navigation: true         # includes previous and next links (`true` by default)
  image: true              # includes image (`true` by default)
  og: true                 # includes Open Graph meta tags (`true` by default)
  articles: "blog"         # articles' section (`blog` by default)
  twitter: true            # includes Twitter/X Card meta tags (`true` by default)
  mastodon: true           # includes Mastodon meta tags (`true` by default)
  dc: false                # includes Dublin Core meta tags (`false` by default)
  data: false              # includes JSON-LD structured data (`false` by default)
```

## debug

Enables the _debug mode_, used to display debug information like very verbose logs, Twig dump, Twig profiler, SCSS sourcemap, etc.

```yaml
debug: true
```

There are two other ways to enable _debug mode_:

1. Run a command with the `-vvv` option
2. Set the `CECIL_DEBUG` environment variable to `true`

---
