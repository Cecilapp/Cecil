<!--
title: "Extend templates"
description: "Add custom functions and filters, or use a theme."
date: 2021-05-07
updated: 2026-11-13
-->
# Extend templates

Templates can be extended with custom functions and filters, or packaged as a reusable theme.

## Functions and filters

You can add custom [functions](reference/1-functions.md) and custom [filters](reference/3-filters.md) with a [**_Twig extension_**](../developers/1-extend.md#twig-extension).

## Theme

It’s easy to build a theme: create a folder `<theme>` with the following structure (like a website but without pages):

```plaintext
<my-site>
└─ themes
   └─ <theme>
      ├─ config.yml
      ├─ assets
      ├─ layouts
      ├─ static
      └─ translations
```

Then enable it with the [`theme`](../configuration/1-site.md#theme) configuration option.

The theme `config.yml` overrides the [default configuration](../configuration/index.md#priority) and is merged with the site configuration, which takes precedence, except for [lists of values](../configuration/index.md#lists): the site values are added to the theme ones, and a theme value can only be removed with `false` (e.g. `homepage: {atom: false}`).

### Required themes

A theme can require other themes (e.g. a component theme providing a search box, or a [plugin](../developers/1-extend.md#plugins)) with the `theme` option of its `config.yml`:

_/themes/&lt;theme&gt;/config.yml_

```yaml
theme:
  - <required-theme>
```

Required themes are enabled with the theme, with no other configuration of the site: their layouts, assets, static files, translations, extensions and `config.yml` are loaded. They are added after the themes of the site, so the theme (like the site) overrides the themes it requires.

:::important
Required themes must be installed too, in the `themes` directory of the site: declare them as dependencies in the `composer.json` of the theme.
:::
