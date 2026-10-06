<!--
title: "Pages and sections"
description: "Anatomy of a page, file prefix, sections, sub-sections and home page."
date: 2021-05-07
updated: 2026-10-06
-->
# Pages and sections

A page is a file made up of a [**front matter**](#front-matter) and a [**body**](#body).

## Front matter

The _front matter_ is a collection of [variables](2-front-matter.md) (in _key/value_ format) surrounded by `---`.

_Example:_

```yaml
---
title: "The title"
date: 2019-02-21
tags: [tag 1, tag 2]
customvar: "Value of customvar"
---
```

:::info
You can also use `<!-- -->` or `+++` as separator.
:::

## Body

_Body_ is the main content of a page, it could be written in [Markdown](3-markdown.md) or in plain text.

_Example:_

```markdown
# Header

[toc]

## Sub-Header 1

Lorem ipsum dolor [sit amet](https://example.com), consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
<!-- excerpt -->
Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.

## Sub-Header 2

![Description](/image.jpg "Title")

## Sub-Header 3

:::tip
This is advice.
:::
```

## File prefix

The filename can contain a prefix to define `date` or `weight` variables of the page (used by [`sortby`](../templates/reference/2-sorts.md#sort-by-date)).

:::info
Default prefix separators: `_` and `-`.

You can customize them with the [`pages.prefix.separator`](../configuration/4-pages.md#pages-prefix-separator) option.

:::

### date

The _date prefix_ is used to set the `date` of the page, and must be a valid date format (i.e.: « YYYY-MM-DD »).

_Example:_

In « 2019-04-23_My blog post.md »:

- the prefix is « 2019-04-23 »
- the `date` of the page is « 2019-04-23 »
- the `title` of the page is « My blog post »

### weight

The _weight prefix_ is used to set the sort order of the page, and must be a valid integer value.

_Example:_

In « 1_The first project.md »:

- the prefix is « 1 »
- the `weight` of the page is « 1 »
- the `title` of the page is « The first project »

## Section

Some dedicated variables can be used in a custom _Section_ (i.e.: `<section>/index.md`).

### sortby

The order of pages in a _Section_ can be changed.

Available values are:

- `date`: more recent first
- `title`: alphabetic order
- `weight`: lightest first

_Example:_

```yaml
---
sortby: title
---
```

**More options:**

```yaml
---
sortby:
  variable: date    # "date", "updated", "title" or "weight"
  desc_title: false # used with "date" or "updated" variable value to sort by desc title order if items have the same date
  reverse: false    # reversed if true
---
```

### pagination

The global [pagination configuration](../configuration/4-pages.md#pages-pagination) is used by default, but you can change it for a specific _Section_.

_Example:_

```yaml
---
pagination:
  max: 5
  path: "page"
---
```

Pagination can be disabled for a _Section_:

```yaml
---
pagination: false
---
```

### cascade

Any variables in `cascade` are added to the front matter of all _sub pages_.

_Example:_

```yaml
---
cascade:
  banner: image.jpg
---
```

:::info
Existing variables are not overridden.
:::

### circular

Set `circular` to `true` to enable circular navigation with [_page.<prev/next>_](../templates/2-variables.md#page-prev-next).

:::info
With [sub-sections](#sub-section), only the `circular` value of the top level _Section_ is used.
:::

_Example:_

```yaml
---
circular: true
---
```

### Sub-section

A nested folder that explicitly contains an `index.md` file is turned into a _sub-section_ of its parent _Section_.

```plaintext
<mywebsite>
└─ pages
   └─ blog                 <- Section
      ├─ index.md
      ├─ post-1.md         <- Page in Section "blog"
      └─ 2024              <- Sub-section (contains an "index.md")
         ├─ index.md
         └─ post-2.md      <- Page in Section "blog" *and* sub-section "blog/2024"
```

A _sub-section_:

- is a _Section_ (same type, variables and [layout](../templates/1-lookup-rules.md#type-section) resolution) available at its own URL (e.g.: `/blog/2024/`)
- is rendered with the layouts of its parent _Sections_ if it doesn't have its own (e.g.: `blog/list.html.twig`)
- can be nested at any depth (e.g.: `blog/2024/06/`)
- lists its own pages, and its pages also belong to each of their parent _Sections_
- is **not** listed in its parent _Section_
- is placed in the [_page.<prev/next>_](../templates/2-variables.md#page-prev-next) navigation of its parent _Section_ (according to its `sortby`), followed by its own pages

:::info
A nested folder **without** an `index.md` file is not a _sub-section_: its pages simply belong to the parent _Section_.
:::

## Home page

Like another section, _Home page_ support `sortby` and `pagination` configuration.

### pagesfrom

Set a valid _Section_ name in `pagesfrom` to use pages collection from this _Section_ in _Home page_.

_Example:_

```yaml
---
pagesfrom: blog
---
```
