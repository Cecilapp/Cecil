<!--
title: "Extend"
description: "Add custom functions and filters, or use a theme."
date: 2021-05-07
updated: 2026-10-05
-->
# Extend

## Functions and filters

You can add custom [functions](reference/1-functions.md) and custom [filters](reference/3-filters.md) with a [**_Twig extension_**](../developers/1-extend.md#twig-extension).

## Theme

It’s easy to build a theme, you just have to create a folder `<theme>` with the following structure (like a website but without pages):

```plaintext
<mywebsite>
└─ themes
   └─ <theme>
      ├─ config.yml
      ├─ assets
      ├─ layouts
      ├─ static
      └─ translations
```
