<!--
title: "Languages"
description: "Main language and additional languages of a multilingual website."
date: 2021-05-07
updated: 2026-10-16
-->
# Languages

These options define the main language of the site and, for a multilingual website, its additional languages.

## language

The main language, defined by its code.

```yaml
language: <code> # unique code (`en` by default)
```

By default, only the paths of the other [languages](#languages) pages are prefixed with their language code, but you can also prefix the paths of the main language pages with the following option:

```yaml
#language: <code>
language:
  code: <code>
  prefix: true
```

:::info
When `prefix` is set to `true`, an alias is automatically created for the home page that redirects from `/` to `/<code>/`.
:::

## languages

Options of available languages, used for [pages](../content/4-multilingual.md) and [templates](../templates/5-localization.md) localization.

```yaml
languages:
  - code: <code>          # unique code, `xx` or `xx-XX` (required, e.g.: `en`, `fr`, `en-US`, `fr-CA`)
    name: <name>          # human readable name (e.g.: `Français`)
    locale: <locale>      # locale code (required, `language_COUNTRY`, e.g.: `en_US`, `fr_FR`, `fr_CA`)
    enabled: <true|false> # enabled or not (`true` by default)
```

_Example:_

```yaml
language: en
languages:
  - code: en
    name: English
    locale: en_US
  - code: fr
    name: Français
    locale: fr_FR
```

:::info
A [locale code list](3-locale-codes.md) is available if needed.
:::

### Localize

To localize configuration options you must store them under the `config` key of the language.

_Example:_

```yaml
title: "Cecil in English"
languages:
  - code: en
    name: English
    locale: en_US
  - code: fr
    name: Français
    locale: fr_FR
    config:
      title: "Cecil en français"
```

:::info
In [templates](../templates/index.md) you can access an option with `{{ site.<option> }}`, for example `{{ site.title }}`.  
If an option is not available in the current language (e.g.: `fr`), it falls back to the global one (e.g.: `en`).
:::

---
