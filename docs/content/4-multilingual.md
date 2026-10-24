<!--
title: "Multilingual"
description: "Translate pages through file name or front matter and link translated pages."
date: 2021-05-07
updated: 2026-10-24
-->
# Multilingual

If your pages are available in multiple [languages](../configuration/2-languages.md#languages), there are two ways to define them:

## Through file name

This is the most common way to translate a page from the main [language](../configuration/2-languages.md#language) to another language.

You just need to duplicate the reference page and add the target language `code` as a suffix, before the extension (e.g.: `about.fr.md`). The code must be one of the declared [languages](../configuration/2-languages.md#languages) (format `fr` or `fr-FR`).

_Example:_

```plaintext
├─ about.md    # the reference page
└─ about.fr.md # the French version (`fr`)
```

:::tip
You can change the URL of the translated page with the `slug` variable in its front matter. For example, in `about.fr.md`:

```yaml
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

```yaml
---
language: fr
---
```

## Link translated pages

Each translated page references its versions in other languages.

This collection of pages is available in [templates](../templates/2-variables.md#page) with the following variable:

```twig
{{ page.translations }}
```

:::info
Translations are linked by their `langref` variable: by default, the page path without language (e.g.: `about` for `about.md` and `about.fr.md`).

You can change it in the front matter, for example to link translated pages with different file names:

```yaml
---
langref: my-page-ref
---
```

:::

## Search engines

The built-in templates declare translated pages to search engines with `hreflang` alternates, in the HTML `<head>` and in the XML sitemap:

- each page lists itself and its translations
- `x-default` points to the version in the default language, and is omitted when the page has no version in the default language

The XML sitemap lists the pages of all languages, including pages that are not translated into the default language.
