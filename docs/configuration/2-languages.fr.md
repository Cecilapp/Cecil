<!--
title: "Langues"
description: "Langue principale et langues supplémentaires d’un site multilingue."
date: 2026-03-27
updated: 2026-10-05
path: documentation/configuration/langues
-->
# Langues

## language

Langue principale, définie par son code.

```yaml
language: <code> # unique code (`en` by default)
```

Par défaut, seuls les chemins des pages des autres [langues](#languages) sont préfixés par leur code de langue, mais vous pouvez préfixer le chemin des pages de la langue principale avec l’option suivante :

```yaml
#language: <code>
language:
  code: <code>
  prefix: true
```

:::info
Quand `prefix` est défini à `true`, un alias est automatiquement créé pour la page d’accueil afin de rediriger de `/` vers `/<code>/`.
:::

## languages

Options des langues disponibles, utilisées pour la localisation des [pages](../content/4-multilingual.fr.md) et des [templates](../templates/5-localization.fr.md).

```yaml
languages:
  - code: <code>          # unique code (e.g.: `en`, `fr`, 'en-US', `fr-CA`)
    name: <name>          # human readable name (e.g.: `Français`)
    locale: <locale>      # locale code (`language_COUNTRY`, e.g.: `en_US`, `fr_FR`, `fr_CA`)
    enabled: <true|false> # enabled or not (`true` by default)
```

_Exemple :_

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
Une [liste des codes de locale](3-locale-codes.fr.md) est disponible si nécessaire.
:::

### Localiser

Pour localiser des options de configuration, vous devez les stocker sous la clé `config` de la langue.

_Exemple :_

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
Dans les [templates](../templates/index.fr.md), vous pouvez accéder à une option avec `{{ site.<option> }}`, par exemple `{{ site.title }}`.  
Si une option n’est pas disponible dans la langue actuelle (ex. : `fr`), elle revient à la valeur globale (ex. : `en`).
:::
