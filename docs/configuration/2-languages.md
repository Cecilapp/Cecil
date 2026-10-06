<!--
title: "Languages"
description: "Main language and additional languages of a multilingual website."
date: 2021-05-07
updated: 2026-10-05
-->
# Languages

## language

The main language, defined by its code.

```yaml
language: <code> # unique code (`en` by default)
```

By default only others [languages](#languages) pages path are prefixed with its language code, but you can prefix the path of the main language pages with the following option:

```yaml
#language: <code>
language:
  code: <code>
  prefix: true
```

:::info
When `prefix` is set to `true`, an alias is automatically created for the home page that redirect from`/` to `/<code>/`.
:::

## languages

Options of available languages, used for [pages](../content/4-multilingual.md) and [templates](../templates/5-localization.md) localization.

```yaml
languages:
  - code: <code>          # unique code (e.g.: `en`, `fr`, 'en-US', `fr-CA`)
    name: <name>          # human readable name (e.g.: `Français`)
    locale: <locale>      # locale code (`language_COUNTRY`, e.g.: `en_US`, `fr_FR`, `fr_CA`)
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
title: "Cecil in english"
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
In [templates](../templates/index.md) you can access to an option with `{{ site.<option> }}`, for example `{{ site.title }}`.  
If an option is not available in the current language (e.g.: `fr`) it fallback to the global one (e.g.: `en`).
:::
