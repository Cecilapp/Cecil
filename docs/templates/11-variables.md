<!--
title: "Variables"
description: "Variables available in templates: site, page and cecil."
date: 2021-05-07
updated: 2026-10-06
-->
# Variables

> The application passes variables to the templates for manipulation in the template. Variables may have attributes or elements you can access, too.  
> Use a dot (.) to access attributes of a variable: `{{ foo.bar }}`

You can use variables from different scopes: [`site`](#site), [`page`](#page), [`cecil`](#cecil).

## site

The `site` variable contains built-in variables **and** those set in the [configuration](../configuration/index.md).

| Variable              | Description                                                  |
| --------------------- | ------------------------------------------------------------ |
| `site.pages`          | Collection of all pages, in the current language.            |
| `site.allpages`       | Collection of all pages, in all languages.                   |
| `site.page(id)`       | A page with the given ID.                                    |
| `site.taxonomies`     | Collection of vocabularies.                                  |
| `site.home`           | ID of the home page.                                         |
| `site.time`           | Current [_Timestamp_](https://wikipedia.org/wiki/Unix_time). |
| `site.debug`          | Debug mode status (`true` or `false`).                       |
| `site.build`          | Current build ID.                                            |

_Example:_

```yaml
title: "My amazing website!"
```

Can be displayed in a template with:

```twig
{{ site.title }}
```

:::important
Use `showable` method on pages collection to return only published and not _virtual/redirect/excluded_ pages.

_Example:_

```twig
{% for page in site.pages.showable %}
  <a href="{{ url(page) }}">{{ page.title }}</a>
{% endfor %}
```

:::

:::warning
In some cases, you can encounter conflicts between configuration and built-in variables (e.g. `pages.default` configuration). In that case, you can use `config.<variable>` (where `<variable>` is the variable name/path) to access the raw configuration directly.

Example:

```twig
{{ config.pages.default.sitemap.priority }}
```

:::

### site.menus

Loop on `site.menus.<menu>` to get each entry of the `<menu>` collection (e.g.: `main`).

| Variable         | Description                                 |
| ---------------- | ------------------------------------------- |
| `<entry>.name`   | Entry name.                                 |
| `<entry>.url`    | Entry URL.                                  |
| `<entry>.weight` | Entry weight (useful to sort menu entries). |

_Example:_

```twig
<nav>
  <ol>
  {% for entry in site.menus.main|sort_by_weight %}
    <li><a href="{{ url(entry.url) }}" data-weight="{{ entry.weight }}">{{ entry.name }}</a></li>
  {% endfor %}
  </ol>
</nav>
```

### site.language

Information about the current language.

| Variable               | Description                                                            |
| ---------------------- | ---------------------------------------------------------------------- |
| `site.language`        | Language code (e.g.: `en`).                                            |
| `site.language.name`   | Language name (e.g.: `English`).                                       |
| `site.language.locale` | Language [locale code](../configuration/24-locale-codes.md) (e.g.: `en_US`). |
| `site.language.weight` | Language position in the `languages` list.                             |

:::tip
You can retrieve `name`, `locale` and `weight` of a specific language by passing its code as a parameter.  
e.g.: `site.language.name('fr')`.
:::

### site.static

The static files collection can be accessed via `site.static` if the [_static load_](../configuration/26-data-static.md#static-load) is enabled.

Each file exposes the following properties:

- `path`: relative path (e.g.: `/images/img-1.jpg`)
- `date`: creation date (_timestamp_)
- `updated`: modification date (_timestamp_)
- `name`: name (e.g.: `img-1.jpg`)
- `basename`: name without extension (e.g.: `img-1`)
- `ext`: extension (e.g.: `jpg`)
- `type`: media type (e.g.: `image`)
- `subtype`: media sub type (e.g.: `image/jpeg`)
- `exif`: image EXIF data (_array_)
- `audio`: [Mp3Info](https://github.com/wapmorgan/Mp3Info#audio-information) object
- `video`: array of basic video information (duration in seconds, width and height)

### site.data

A data collection can be accessed via `site.data.<filename>` (without file extension).

_Examples:_

- `data/authors.yml` : `site.data.authors`
- `data/authors.fr.yml` : `site.data.authors` (if `site.language` = "fr")
- `data/galleries/gallery-1.json` : `site.data.galleries['gallery-1']`

## page

The `page` variable contains built-in variables of a page **and** those set in the [front matter](../content/5-pages.md#front-matter).

| Variable            | Description                                            | Example          |
| ------------------- | ------------------------------------------------------ | ---------------- |
| `page.id`           | Unique identifier.                                     | `blog/post-1`    |
| `page.title`        | File name (without extension).                         | `Post 1`         |
| `page.date`         | File creation date.                                    | _DateTime_       |
| `page.body`         | File body.                                             | _Markdown_       |
| `page.content`      | File body converted in HTML.                           | _HTML_           |
| `page.section`      | File root folder (_slugified_).                        | `blog`           |
| `page.path`         | File path (_slugified_).                               | `blog/post-1`    |
| `page.slug`         | File name (_slugified_).                               | `post-1`         |
| `page.filepath`     | File system path.                                      | `Blog/Post 1.md` |
| `page.type`         | `homepage`, `page`, `section`, `vocabulary` or `term`. | `page`           |
| `page.pages`        | Collection of all sub pages.                           | _Collection_     |
| `page.translations` | Collection of translated pages.                        | _Collection_     |

:::important
Use `showable` method on pages collection to return only published and not _virtual/redirect/excluded_ pages.

_Example:_

```twig
{% for page in page.pages.showable %}
  <a href="{{ url(page) }}">{{ page.title }}</a>
{% endfor %}
```

:::

### Nested sections

In a [nested sections](../content/5-pages.md#sub-section) context, `page.parent`, `page.ancestors`, `page.sections` and `page.toplevel` help you build navigation.

| Variable         | Description                                        | Example      |
| ---------------- | -------------------------------------------------- | ------------ |
| `page.parent`    | Parent _section_'s page (`null` if none).          | _Page_       |
| `page.ancestors` | Collection of ancestor _sections_ (nearest first). | _Collection_ |
| `page.sections`  | Collection of immediate descendant _sections_.     | _Collection_ |
| `page.toplevel`  | `true` if the page is a top level _section_.       | _Boolean_    |

_Breadcrumb (from the home page to the current page):_

```twig
<nav aria-label="breadcrumb">
  <ul>
    <li><a href="{{ url(site.home) }}">{{ site.title }}</a></li>
    {% for section in page.ancestors|reverse %}
    <li><a href="{{ url(section) }}">{{ section.title }}</a></li>
    {% endfor %}
    {% if page.id != site.home %}
    <li><a href="{{ url(page) }}" aria-current="page">{{ page.title }}</a></li>
    {% endif %}
  </ul>
</nav>
```

:::tip
A ready-to-use [`breadcrumb.html.twig`](https://github.com/Cecilapp/Cecil/blob/main/resources/layouts/partials/breadcrumb.html.twig) partial is available:

```twig
{{ include('partials/breadcrumb.html.twig') }}
```

:::

_Sub-sections menu (immediate descendant sections of the current section):_

```twig
{% if page.sections|length %}
<ul>
  {% for section in page.sections|sort_by_title %}
  <li><a href="{{ url(section) }}">{{ section.title }}</a></li>
  {% endfor %}
</ul>
{% endif %}
```

_Main navigation limited to top level sections (from any page):_

```twig
<nav>
  {% for section in site.page(site.home).sections|sort_by_title %}
  <a href="{{ url(section) }}">{{ section.title }}</a>
  {% endfor %}
</nav>
```

_Link to the parent section:_

```twig
{% if page.parent %}
<a href="{{ url(page.parent) }}">← {{ page.parent.title }}</a>
{% endif %}
```

### page.<prev/next>

Navigation between pages within the same _section_, sorted according to the section's `sortby` (chronological order for dates).

With [sub-sections](../content/5-pages.md#sub-section), navigation follows the sections tree: the pages of a top level _Section_ and of all its sub-sections are chained, each sub-section (its index page) being placed among the pages of its parent _Section_ and followed by its own pages.

| Variable    | Description    | Example |
| ----------- | -------------- | ------- |
| `page.prev` | Previous page. | _Page_  |
| `page.next` | Next page.     | _Page_  |

_Example:_

```twig
<a href="{{ url(page.prev) }}">{{ page.prev.title }}</a>
```

### page.paginator

_Paginator_ helps you build navigation for list pages: homepage, sections, and taxonomies.

| Variable                     | Description                         |
| ---------------------------- | ----------------------------------- |
| `page.paginator.pages`       | Pages Collection.                   |
| `page.paginator.pages_total` | Number total of pages.              |
| `page.paginator.count`       | Number of paginator's pages.        |
| `page.paginator.current`     | Position index of the current page. |
| `page.paginator.links.first` | Page ID of the first page.          |
| `page.paginator.links.prev`  | Page ID of the previous page.       |
| `page.paginator.links.self`  | Page ID of the current page.        |
| `page.paginator.links.next`  | Page ID of the next page.           |
| `page.paginator.links.last`  | Page ID of the last page.           |
| `page.paginator.links.path`  | Page ID without the position index. |

:::important
Because links entries are Page ID you must use the `url()` function to create working links.  
e.g: `{{ url(page.paginator.links.next) }}`
:::

_Example:_

```twig
{% if page.paginator %}
<div>
  {% if page.paginator.links.prev is defined %}
  <a href="{{ url(page.paginator.links.prev) }}">Previous</a>
  {% endif %}
  {% if page.paginator.links.next is defined %}
  <a href="{{ url(page.paginator.links.next) }}">Next</a>
  {% endif %}
</div>
{% endif %}
```

_Example:_

```twig
{% if page.paginator %}
<div>
  {% for paginator_index in 1..page.paginator.count %}
    {% if paginator_index != page.paginator.current %}
      {% if paginator_index == 1 %}
  <a href="{{ url(page.paginator.links.first) }}">{{ paginator_index }}</a>
      {% else %}
  <a href="{{ url(page.paginator.links.path ~ '/' ~ paginator_index) }}">{{ paginator_index }}</a>
      {% endif %}
    {% else %}
  {{ paginator_index }}
    {% endif %}
  {% endfor %}
</div>
{% endif %}
```

### Taxonomy

Variables available in _vocabulary_ and _term_ templates.

#### Vocabulary

Page `/<plural>/` (e.g.: `/categories/`).

| Variable        | Description                       |
| --------------- | --------------------------------- |
| `page.plural`   | Vocabulary name in plural form.   |
| `page.singular` | Vocabulary name in singular form. |
| `page.terms`    | List of terms (_Collection_).     |

Each term of `page.terms` provides `term.id` (term ID, e.g.: `categories/php`), `term.name` (term name, e.g.: `PHP`) and the number of its pages with `term|length`.

#### Term

Page `/<plural>/<term>/` (e.g.: `/categories/php/`).

| Variable        | Description                                                |
| --------------- | ---------------------------------------------------------- |
| `page.title`    | Term name.                                                 |
| `page.term`     | Term ID (e.g.: `categories/php`).                          |
| `page.plural`   | Vocabulary name in plural form.                            |
| `page.singular` | Vocabulary name in singular form.                          |
| `page.pages`    | List of pages in this term, sorted by date (_Collection_). |

#### Taxonomy example

Configuration:

```yaml
taxonomies:
  categories: category
```

Page front matter:

```yaml
---
categories: ["Data Sovereignty"]
---
```

List of terms (`/categories/`), in `layouts/taxonomy/categories.html.twig`:

```twig
{% extends 'page.html.twig' %}

{% block content %}
  <h1>{{ page.title }}</h1>
  <ul>
  {% for term in page.terms %}
    <li><a href="{{ url(term.id) }}">{{ term.name }}</a> ({{ term|length }})</li>
  {% endfor %}
  </ul>
{% endblock %}
```

List of pages of a term (`/categories/data-sovereignty/`), in `layouts/taxonomy/category.html.twig`:

```twig
{% extends 'page.html.twig' %}

{% block content %}
  <h1>{{ page.title }}</h1>
  {% for p in page.paginator.pages ?? page.pages %}
    <article>
      <h2><a href="{{ url(p) }}">{{ p.title }}</a></h2>
    </article>
  {% endfor %}
  <a href="{{ url(page.plural) }}">All {{ page.plural }}</a>
{% endblock %}
```

Links to the terms of the current page, in a page template:

```twig
{% for category in page.categories ?? [] %}
  <a href="{{ url('categories/' ~ category) }}">{{ category }}</a>
{% endfor %}
```

:::tip
The [`url()`](reference/12-functions.md#url) function slugifies the given string to find the matching page: `url('categories/Data Sovereignty')` returns `/categories/data-sovereignty/`.

You can also use the built-in partial `{{ include('partials/terms-list.html.twig', {vocabulary: 'categories'}) }}`.
:::

## cecil

| Variable          | Description                                          |
| ----------------- | ---------------------------------------------------- |
| `cecil.url`       | URL of the Cecil website.                            |
| `cecil.version`   | Cecil current version.                               |
| `cecil.poweredby` | Print `Cecil v%s`, with `%s` is the current version. |
