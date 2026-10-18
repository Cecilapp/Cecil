<!--
title: "Layouts"
description: "Templates directory, auto-escaping, images, highlight, translations and components."
date: 2021-05-07
updated: 2026-10-18
-->
# Layouts

Templates options.

## layouts.dir

Templates source directory (`layouts` by default).

```yaml
layouts:
  dir: layouts
```

## layouts.autoescape

Overrides Twig `autoescape` option (`false` by default).

If set to `null`, Cecil uses an extension-based strategy:

- `*.js.twig` -> `js`
- `*.css.twig` -> `css`
- `*.html.twig` and `*.twig` -> `html`
- any other extension -> `false`

```yaml
layouts:
  autoescape: false  # disables automatic escaping (default)
  #autoescape: null  # use Cecil automatic strategy by template filename extension
  #autoescape: html
  #autoescape: js
```

## layouts.images

Images handling options.

```yaml
layouts:
  images:
    formats: []       # used by `html` function: adds alternative image formats as `source` (e.g. `[avif, webp]`, empty array by default)
    responsive: false # used by `html` function: adds responsive images ('width' or 'density', `false` by default)
    placeholder: ''   # used by `html` function: fills image background before loading (`color` or `lqip`, disabled by default)
    dark_suffix: ~    # suffix of the dark variant image (e.g. `.dark`), disabled by default
    mobile_suffix: ~  # suffix of the mobile variant image (e.g. `.mobile`), disabled by default
    mobile_media_query: '(max-width: 767px)' # media query of the mobile variant `<source>`
```

## layouts.highlight

Code syntax highlighting options of templates.

```yaml
layouts:
  highlight:
    engine: highlightjs # used by `highlight` filter: `highlightjs` (default) or `tempest`
```

See the [`highlight`](../templates/reference/3-filters.md#highlight) filter.

:::info
This option only applies to templates: Markdown content uses the [`pages.body.highlight.engine`](4-pages.md#pages-body-highlight) option.
:::

## layouts.translations

Translations handling options.

```yaml
layouts:
  translations:
    dir: translations # translations source directory (`translations` by default)
    formats:          # translations supported formats
      yaml:
        loader: Symfony\Component\Translation\Loader\YamlFileLoader
        ext: [yml, yaml]
      mo:
        loader: Symfony\Component\Translation\Loader\MoFileLoader
        ext: [mo]
```

Each translation format defines:

- `loader`: Symfony translation loader class
- `ext`: one or more file extensions associated with this format

## layouts.extensions

List of [Twig extensions](../developers/1-extend.md#twig-extension) classes to load (empty by default).

```yaml
layouts:
  extensions:
    <name>: <class>
```

## layouts.components

[Templates Components](../templates/4-components.md) options.

```yaml
layouts:
  components:
    dir: components # components source directory (`components` by default)
    ext: twig       # components files extension (`twig` by default)
```

## layouts.sections

Maps a section to the layouts of another section: the mapped name is used in place of `<section>` by the [lookup rules](../templates/1-lookup-rules.md#lookup-rules) of the section and of its pages.

```yaml
layouts:
  sections:
    news: blog # the "news" section is rendered with `blog/list.html.twig` and its pages with `blog/page.html.twig`
```

:::tip
A [sub-section](../content/1-pages.md#sub-section) already falls back to the layouts of its parent sections: no mapping is needed for that.
:::

---
