<!--
title: "Localization"
description: "Translate texts and localize dates in templates."
date: 2021-05-07
updated: 2026-10-16
-->
# Localization

Cecil supports [text translation](#text-translation) and [date localization](#date-localization).

## Text translation

Use the `trans` _tag_ or _filter_ to translate texts in templates.

```twig
{% trans with variables into locale %}{% endtrans %}
```

```twig
{{ message|trans(variables = []) }}
```

### Examples

```twig
{% trans %}Hello World!{% endtrans %}
```

```twig
{{ message|trans }}
```

Include variables:

```twig
{% trans with {'%name%': 'Arnaud'} %}Hello %name%!{% endtrans %}
```

```twig
{{ message|trans({'%name%': 'Arnaud'}) }}
```

Force locale:

```twig
{% trans into 'fr_FR' %}Hello World!{% endtrans %}
```

Pluralize:

```twig
{% trans with {'%count%': 42} %}{0}I don't have apples|{1}I have one apple|]1,Inf[I have %count% apples{% endtrans %}
```

## Translation files

Translation files must be named `messages.<locale>.<extension>` and stored in the [`translations`](../configuration/7-layouts.md#layouts-translations) directory.  
Supported file extensions are defined by each translation format in [`layouts.translations.formats`](../configuration/7-layouts.md#layouts-translations).

The locale code (e.g.: `fr_FR`) of a language is defined in the [`languages`](../configuration/2-languages.md#languages) entries of the configuration. A file named with the language code only (e.g.: `messages.fr.yaml`) is also loaded, and the translations of the [themes](7-extend.md#theme) are merged with those of the site.

_Example:_

```plaintext
<my-site>
└─ translations
   ├─ messages.fr_FR.mo   <- Machine Object format
   └─ messages.fr_FR.yaml <- YAML format
```

:::info
You can easily extract translations from your templates with the following command:

```bash
php cecil.phar util:translations:extract --locale=<code> --show
```

Use `--save` instead of (or in addition to) `--show` to save the translations to a file. The `--locale` option is required. The default output format is `yaml` (use `--format=po` for gettext PO format).

:::

:::tip
[_Poedit_](https://poedit.net) is a simple and cross-platform translation editor for gettext (PO), and [_Poedit Pro_](https://poedit.net/pro) supports extraction of translation strings from templates out of the box.
:::

:::important
Translations are [cached](6-cache.md): the cache is refreshed when a translation file is updated or removed, but not when a new one is added.

Cache can be cleared with the following command:

```bash
php cecil.phar cache:clear:translations
```

:::

## Date localization

Use the Twig [`format_date`](https://twig.symfony.com/doc/3.x/filters/format_date.html) filter to localize a date in templates.

```twig
{{ page.date|format_date('long') }}
{# September 30, 2022 #}
```

Supported values are: `none`, `short`, `medium`, `long`, and `full`.

The [`format_datetime`](https://twig.symfony.com/doc/3.x/filters/format_datetime.html) and [`format_time`](https://twig.symfony.com/doc/3.x/filters/format_time.html) filters are also available.

:::important
If you want to use these filters **with locales other than "en"**, you should [install the intl PHP extension](https://php.net/intl.setup).
:::
