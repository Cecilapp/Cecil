<!--
title: "Organization and lookup rules"
description: "Kinds of templates, naming convention, built-in templates and how a template is chosen for a page."
date: 2021-05-07
updated: 2026-10-10
-->
# Organization and lookup rules

Cecil renders pages with Twig templates: this page explains how templates are organized and how the right one is chosen for each page.

## Files organization

### Kinds of templates

There are three kinds of templates: **_layouts_**, **_components_**, and **_other templates_**. _Layouts_ are used to render [pages](../content/1-pages.md), and each layout can [include templates](https://twig.symfony.com/doc/templates.html#including-other-templates) and [components](4-components.md).

### Naming convention

Template files are stored in the `layouts/` directory and must be named according to the following convention:

```plaintext
layouts/(<section>/)<type>|<layout>.<format>(.<language>).twig
```

`<section>` (_optional_)
:  The section of the page (e.g.: `blog`).

`<type>`
:  The page type: `home` (or `index`) for _homepage_, `list` for _list_, `page` for _page_, etc. (See [_Lookup rules_](#lookup-rules) for details).

`<layout>` (_optional_)
:  The custom layout name defined in the [front matter](../content/1-pages.md#front-matter) of the page (e.g.: `layout: my-layout`).

`<format>`
:  The [output format](../configuration/8-output.md#output-formats) of the rendered page (e.g.: `html`, `rss`, `json`, `xml`, etc.).

`<language>` (_optional_)
:  The language of the page (e.g.: `fr`).

_Examples:_

```plaintext
layouts/home.html.twig       # `type` is "homepage"
layouts/page.html.twig       # `type` is "page"
layouts/page.html.fr.twig    # `type` is "page" and `language` is "fr"
layouts/my-layout.html.twig  # `layout` is "my-layout"
layouts/blog/list.html.twig  # `section` is "blog"
layouts/blog/list.rss.twig   # `section` is "blog" and `format` is "rss"
```

```plaintext
<my-site>
├─ ...
├─ layouts
|  ├─ index.html.twig      # Used by type "homepage"
|  ├─ list.html.twig       # Used by types "homepage" and "section"
|  ├─ list.rss.twig        # Used by types "homepage" and "section", for RSS output format
|  ├─ page.html.twig       # Used by type "page"
|  ├─ taxonomy
|  |  ├─ tags.html.twig    # Used by type "vocabulary" of `tags` (list of terms)
|  |  └─ tag.html.twig     # Used by type "term" of `tags` (list of pages)
|  ├─ my-layout.html.twig  # Used by pages with `layout: my-layout` in the front matter
|  ├─ ...
|  └─ partials             # Included templates
|     ├─ footer.html.twig
|     └─ ...
└─ themes                  # Themes layouts and templates
   └─ ...
```

### Built-in templates

Cecil comes with a set of [built-in templates](https://github.com/Cecilapp/Cecil/tree/main/resources/layouts).

:::tip
If you need to modify built-in templates, you can easily extract them via the following command: they will be copied in the `layouts` directory of your site.

```bash
php cecil.phar util:templates:extract
```

:::

## Lookup rules

In most of cases **you don’t need to specify the layout**: Cecil selects the most appropriate layout, according to the **page type**.

For example, the HTML output of **home page** (`index.md`) will be rendered:

1. with `my-layout.html.twig` if the `layout` variable is set to "my-layout" (in the front matter)
2. if not, with `index.html.twig` if the file exists
3. if not, with `home.html.twig` if the file exists
4. if not, with `list.html.twig` if the file exists

All rules are detailed below, for each page type, in the priority order.

### Type _homepage_

1. `<layout>.<format>.twig`
2. `index.<format>.twig`
3. `home.<format>.twig`
4. `list.<format>.twig`
5. `_default/<layout>.<format>.twig`
6. `_default/index.<format>.twig`
7. `_default/home.<format>.twig`
8. `_default/list.<format>.twig`
9. `_default/page.<format>.twig`

### Type _page_

1. `<section>/<layout>.<format>.twig`
2. `<layout>.<format>.twig`
3. `<section>/page.<format>.twig`
4. `_default/<layout>.<format>.twig`
5. `page.<format>.twig`
6. `_default/page.<format>.twig`

### Type _section_

1. `<layout>.<format>.twig`
2. `<section>/index.<format>.twig`
3. `<section>/list.<format>.twig`
4. `section/<section>.<format>.twig`
5. `<parent>/index.<format>.twig`, `<parent>/list.<format>.twig` and `section/<parent>.<format>.twig`, for each parent section of a sub-section (nearest first)
6. `_default/section.<format>.twig`
7. `list.<format>.twig`
8. `_default/list.<format>.twig`

:::tip
The `<section>` of a [sub-section](../content/1-pages.md#sub-section) is its full path (e.g.: `blog/2024`), and a sub-section falls back to the templates of its parent sections: if `blog/2024/list.html.twig` doesn’t exist, the sub-section `blog/2024` is rendered with `blog/list.html.twig`.
:::

### Type _vocabulary_

1. `taxonomy/<plural>.<format>.twig`
2. `vocabulary.<format>.twig`
3. `_default/vocabulary.<format>.twig`

### Type _term_

1. `taxonomy/<plural>/<term>.<format>.twig`
2. `taxonomy/<singular>.<format>.twig`
3. `term.<format>.twig`
4. `_default/term.<format>.twig`
5. `_default/list.<format>.twig`

:::important
The **vocabulary** template is named after the **plural** (e.g.: `taxonomy/categories.html.twig` for `/categories/`), whereas the **term** template is named after the **singular** (e.g.: `taxonomy/category.html.twig` for `/categories/data-sovereignty/`).
:::

:::tip
`<term>` is the slugified term name: a dedicated template for the term "Data Sovereignty" of the `categories` vocabulary is `taxonomy/categories/data-sovereignty.html.twig`.
:::

:::info
Most of those layouts are available by default, see [built-in templates](https://github.com/Cecilapp/Cecil/tree/main/resources/layouts).
:::
