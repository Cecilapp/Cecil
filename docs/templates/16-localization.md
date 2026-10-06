<!--
title: "Localization"
description: "Translate texts and localize dates in templates."
date: 2021-05-07
updated: 2026-10-05
-->
# Localization

Cecil support [text translation](#text-translation) and [date localization](#date-localization).

## Text translation

Uses the `trans` _tag_ or _filter_ to translate texts in templates.

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
{% trans with {'%count%': 42}%}{0}I don't have apples|{1}I have one apple|]1,Inf[I have %count% apples{% endtrans %}
```

## Translation files

Translation files must be named `messages.<locale>.<extension>` and stored in the [`translations`](../configuration/28-layouts.md) directory.  
Supported file extensions are defined by each translation format in [`layouts.translations.formats`](../configuration/28-layouts.md#layouts-translations).

The locale code (e.g.: `fr_FR`) of a language is defined in the [`languages`](../configuration/23-languages.md#languages) entries of the configuration.

_Example:_

```plaintext
<mywebsite>
└─ translations
   ├─ messages.fr_FR.mo   <- Machine Object format
   └─ messages.fr_FR.yaml <- Yaml format
```

:::info
You can easily extract translations from your templates with the following command:

```bash
php cecil.phar util:translations:extract --locale=<code> --show
```

Use `--save` instead of (or in addition to) `--show` to save the translations to a file. The `--locale` option is required. The default output format is `yaml` (use `--format=po` for gettext PO format).

:::

:::tip
[_Poedit_](https://poedit.net) is a simple and cross platform translation editor for gettext (PO), and [_Poedit Pro_](https://poedit.net/pro) supports extraction of translation strings from templates out of the box.
:::

:::important
Be careful about the [cache](17-cache.md) when you update translations files.

Cache can be cleared with with the following command:

```bash
php cecil.phar cache:clear:translations`
```

:::

## Date localization

Uses the Twig [`format_date`](https://twig.symfony.com/doc/3.x/filters/format_date.html) filter to localize a date in templates.

```twig
{{ page.date|format_date('long') }}
{# September 30, 2022 #}
```

Supported values are: `short`, `medium`, `long`, and `full`.

:::important
If you want to use the `format_date` filter **with other locales than "en"**, you should [install the intl PHP extension](https://php.net/intl.setup).
:::
