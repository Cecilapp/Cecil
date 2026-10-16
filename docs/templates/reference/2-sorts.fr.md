<!--
title: "Tris"
description: "Triez des collections de pages, menus ou taxonomies."
date: 2026-05-26
updated: 2026-10-16
path: documentation/templates/reference/tris
-->
# Tris

Tri des collections (de pages, menus ou taxonomies).

## sort_by_title

Trie une collection par titre (avec un [tri naturel](https://en.wikipedia.org/wiki/Natural_sort_order)).

```twig
{{ collection|sort_by_title }}
```

_Exemple :_

```twig
{{ site.pages|sort_by_title }}
```

## sort_by_date

Trie une collection par date (la plus récente en premier).

```twig
{{ collection|sort_by_date(variable='date', desc_title=false) }}
```

- `variable` : la variable de date utilisée pour le tri (`date` par défaut)
- `desc_title` : trie les éléments ayant la même date par titre, dans l’ordre décroissant (`false` par défaut)

_Exemple :_

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

_Exemple :_

```twig
{{ site.menus.main|sort_by_weight }}
```

## sort

Pour les cas plus complexes, il est préférable d’utiliser le filtre natif [`sort`](https://twig.symfony.com/doc/filters/sort.html) de Twig.

_Exemple :_

```twig
{% set files = site.static|sort((a, b) => b.date <=> a.date) %}
```

## Méthodes de collection

Une collection de pages (ex. : `site.pages`, `page.pages`) fournit également des méthodes de tri, qui renvoient une collection de pages :

- `sortBy(options)` : trie par `date`, `updated`, `title` ou `weight`
- `sortByDate(options)`, `sortByTitle(options)`, `sortByWeight(options)`

`options` peut être le nom de la variable (ex. : `'title'`) ou un objet avec les clés `variable`, `descTitle` (tri par date uniquement) et `reverse`.

_Exemples :_

```twig
{% for p in site.pages.showable.sortBy('title') %}…{% endfor %}
{% for p in site.pages.showable.sortBy({variable: 'updated', reverse: true}) %}…{% endfor %}
```
