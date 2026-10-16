<!--
title: "Extend templates"
description: "Add custom functions and filters, or use a theme."
date: 2021-05-07
updated: 2026-10-16
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
