<!--
title: "Front matter"
description: "Custom and predefined page variables: menu, taxonomy, schedule, redirect, alias, output, etc."
date: 2021-05-07
updated: 2026-10-03
-->
# Front matter

The _front matter_ can contains custom variables applied to the current page.

It must be the first thing in the file and must be a valid [YAML](https://en.wikipedia.org/wiki/YAML).

## Predefined variables

| Variable    | Description       | Default value                                      | Example       |
| ----------- | ----------------- | -------------------------------------------------- | ------------- |
| `title`     | Title             | File name without extension.                       | `Post 1`      |
| `layout`    | Template          | See [_Lookup rules_](../templates/1-lookup-rules.md#lookup-rules). | `404`         |
| `date`      | Creation date     | File creation date (PHP _DateTime_ object).        | `2019/04/15`  |
| `section`   | Section           | Page's _Section_.                                  | `blog`        |
| `path`      | Path              | Page's _path_.                                     | `blog/post-1` |
| `slug`      | Slug              | Page's _slug_.                                     | `post-1`      |
| `published` | Published or not  | `true`.                                            | `false`       |
| `draft`     | Published or not  | `false`.                                           | `true`        |

:::info
All the predefined variables can be overridden except `section`.
:::

## updated

The `updated` variable is used to define the last modification date of a page.

_Example:_

```yaml
---
updated: 2026-02-02
---
```

:::warning
Before version 8.80.1, the `updated` variable was a predefined variable. It is now an optional variable (and must be defined in the front matter to be used).
:::

## menu

A page can be added to a [menu](../configuration/1-site.md#menus).

The entry name is the page `title` and the URL is the page `path`.

The same page can be added to multiple menus, and each entry's position can be set with the `weight` key (lowest first). The `name` key can be used to override the default entry name per menu.

_Examples:_

```yaml
---
menu: main
---
```

```yaml
---
menu: [main, navigation] # same page in multiple menus
---
```

```yaml
---
menu:
  main:
    weight: 10
  navigation:
    weight: 20
---
```

```yaml
---
title: 'Our Expertise'
menu:
  main:
    weight: 15
  footer:
    weight: 15
    name: "Expertise" # override the entry name in this menu
---
```

## Taxonomy

Taxonomy allows you to connect, relate and classify your website’s content.  
In Cecil, these terms are gathered within vocabularies.

Vocabularies are declared in the [_Configuration_](../configuration/1-site.md#taxonomies).

Vocabulary
: A categorization of content (e.g.: `tags`, `categories`, etc.).

Term
: A term is an item of a vocabulary (e.g.: `Development`, `PHP`, etc.).

_Example:_

```yaml
---
tags: ["Development", "PHP"]
---
```

Cecil then generates, for each vocabulary:

- a page listing its terms, e.g.: `/tags/`
- a page per term listing its pages, e.g.: `/tags/development/` and `/tags/php/`

See [templates lookup rules](../templates/1-lookup-rules.md#type-vocabulary) and [taxonomy variables](../templates/2-variables.md#taxonomy) to customize those pages.

## Schedule

Schedules pages’ publication.

_Example:_

The page will be published if current date is >= 2023-02-07:

```yaml
schedule:
  publish: 2023-02-07
```

This page is published if current date is <= 2022-04-28:

```yaml
schedule:
  expiry: 2022-04-28
```

## redirect

As indicated by its name, the `redirect` variable is used to redirect a page to a dedicated URL.

_Example:_

```yaml
---
redirect: "https://arnaudligny.fr"
---
```

:::info
Redirect works with the [`redirect.html.twig`](https://github.com/Cecilapp/Cecil/blob/main/resources/layouts/_default/redirect.html.twig) template.
:::

## alias

Alias is a redirection to the current page

_Example:_

```yaml
---
title: "About"
alias:
  - contact
---
```

In the previous example `contact/` redirects to `about/`.

## output

Defines the output format of the page.

Available formats are: `html`, `atom`, `rss`, `json`, `xml`, etc.  
You can define one or more formats in an array.

I’s not required to define an output format, but if you do, it must be one of the available formats defined in the [_Configuration_](../configuration/8-output.md#output-formats).

_Example:_

```yaml
---
output: [html, atom]
---
```

## external

A page with an `external` variable try to fetch the content of the pointed resource.

_Example:_

```yaml
---
external: "https://raw.githubusercontent.com/Cecilapp/Cecil/main/README.md"
---
```

## excluded

Set `excluded` to `true` to hide a page from list pages (i.e.: _Home page_, _Section_, _Sitemap_, etc.).

_Example:_

```yaml
---
excluded: true
---
```

:::info
`excluded` is different from [`published`](#predefined-variables): an excluded page is published but hidden from list pages.
:::

:::warning
Since version 8.49.0, the previous `exclude` variable have been changed to `excluded`.
:::
