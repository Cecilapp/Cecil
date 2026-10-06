<!--
title: "Dynamic content"
description: "Use variables and Twig expressions inside page content."
date: 2021-05-07
updated: 2026-10-03
-->
# Dynamic content

You can create dynamic content in a page by using the [`template_from_string`](https://twig.symfony.com/doc/3.x/functions/template_from_string.html) Twig function.

```twig
{{ include(template_from_string(page.content, "dynamic content for page " ~ page.id)) }}
```

With this, you can use any page variable in the _body_ of the page.

```twig
--
var: 'value'
---
The value of `var` is {{ page.var }}.
```
