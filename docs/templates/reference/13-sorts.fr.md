<!--
title: "Tris"
description: "Triez des collections de pages, menus ou taxonomies."
date: 2026-05-26
updated: 2026-10-05
path: documentation/templates/reference/tris
-->
# Tris

Tri des collections (de pages, menus ou taxonomies).

## sort_by_title

Trie une collection par titre (avec [tri naturel](https://en.wikipedia.org/wiki/Natural_sort_order)).

```twig
{{ collection|sort_by_title }}
```

_Exemple:_

```twig
{{ site.pages|sort_by_title }}
```

## sort_by_date

Trie une collection par date (la plus récente en premier).

```twig
{{ collection|sort_by_date(variable='date', desc_title=false) }}
```

_Exemple:_

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

Trie une collection par poids (le plus léger en premier).

```twig
{{ collection|sort_by_weight }}
```

_Exemple:_

```twig
{{ site.menus.main|sort_by_weight }}
```

## sort

Pour les cas plus complexes, vous devez utiliser [le `sort`](https://twig.symfony.com/doc/filters/sort.html) natif de Twig.

_Exemple:_

```twig
{% set files = site.static|sort((a, b) => a.date|date('U') < b.date|date('U')) %}
```
