<!--
title: "Sorts"
description: "Sort collections of pages, menus or taxonomies."
date: 2021-05-07
updated: 2026-10-05
-->
# Sorts

Sorting collections (of pages, menus or taxonomies).

## sort_by_title

Sorts a collection by title (with [natural sort](https://en.wikipedia.org/wiki/Natural_sort_order)).

```twig
{{ collection|sort_by_title }}
```

_Example:_

```twig
{{ site.pages|sort_by_title }}
```

## sort_by_date

Sorts a collection by date (most recent first).

```twig
{{ collection|sort_by_date(variable='date', desc_title=false) }}
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

## sort_by_weight

Sorts a collection by weight (lighter first).

```twig
{{ collection|sort_by_weight }}
```

_Example:_

```twig
{{ site.menus.main|sort_by_weight }}
```

## sort

For more complex cases, you should use [Twig’s native `sort`](https://twig.symfony.com/doc/filters/sort.html).

_Example:_

```twig
{% set files = site.static|sort((a, b) => a.date|date('U') < b.date|date('U')) %}
```
