<!--
title: "Sorts"
description: "Sort collections of pages, menus or taxonomies."
date: 2021-05-07
updated: 2026-10-16
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

- `variable`: the date variable to sort by (`date` by default)
- `desc_title`: sorts items with the same date by title, in descending order (`false` by default)

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
{% set files = site.static|sort((a, b) => b.date <=> a.date) %}
```

## Collection methods

A pages collection (e.g.: `site.pages`, `page.pages`) also provides sorting methods, which return a pages collection:

- `sortBy(options)`: sorts by `date`, `updated`, `title` or `weight`
- `sortByDate(options)`, `sortByTitle(options)`, `sortByWeight(options)`

`options` can be the name of the variable (e.g.: `'title'`) or an object with the keys `variable`, `descTitle` (sort by date only) and `reverse`.

_Examples:_

```twig
{% for p in site.pages.showable.sortBy('title') %}…{% endfor %}
{% for p in site.pages.showable.sortBy({variable: 'updated', reverse: true}) %}…{% endfor %}
```
