<!--
title: "Multilingual"
description: "Translate pages through file name or front matter and link translated pages."
date: 2021-05-07
updated: 2026-10-03
-->
# Multilingual

If your pages are available in multiple [languages](../configuration/2-languages.md#languages) there is 2 different ways to define it:

## Through file name

This is the common way to translate a page from the main [language](../configuration/2-languages.md#language) to another language.

You just need to duplicate the reference page and suffix it with the target language `code` (e.g.: `fr`).

_Example:_

```plaintext
├─ about.md    # the reference page
└─ about.fr.md # the french version (`fr`)
```

:::tip
You can change the URL of the translated page with the `slug` variable in the front matter. For example:

```yml
---
slug: a-propos
---
# about.md    -> /about/
# about.fr.md -> /fr/a-propos/
```

:::

## Through front matter

If you want to create a page in a language other than the main language, without it being a translation of an existing page, you can use the `language` variable in its front matter.

_Example:_

```yml
---
language: fr
---
```

## Link translated pages

Each translated page reference the pages in others languages.

Those pages collection is available in [templates](../templates/2-variables.md#page) with the following variable:

```twig
{{ page.translations }}
```

:::info
The `langref` variable is provided by default, but you can change it in the front matter:

```yml
---
langref: my-page-ref
---
```

:::
