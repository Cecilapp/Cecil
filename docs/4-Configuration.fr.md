<!--
title: "Configuration"
description: "Configurez votre site web."
date: 2026-03-27
slug: configuration
-->
# Configuration

## Aperçu

La configuration du site web est définie dans un fichier [YAML](https://en.wikipedia.org/wiki/YAML) nommé `cecil.yml` ou `config.yml`, stocké à la racine :

```plaintext
<mywebsite>
└─ cecil.yml
```

Cecil propose de nombreuses options de configuration, mais ses [valeurs par défaut](https://github.com/Cecilapp/Cecil/blob/master/config/default.php) sont souvent suffisantes. Un nouveau site ne nécessite que ces paramètres :

```yaml
title: "My new Cecil site"
baseurl: https://mywebsite.com/
description: "Site description"
```

La documentation ci-dessous couvre toutes les options de configuration prises en charge par Cecil.

## Options

### title

Titre principal du site.

```yaml
title: "<site title>"
```

### baseline

Description courte (~ 20 caractères).

```yaml
baseline: "<baseline>"
```

### baseurl

URL de base.

```yaml
baseurl: <url>
```

_Exemple :_

```yaml
baseurl: http://localhost:8000/
```

:::important
`baseurl` doit se terminer par un slash final (`/`).
:::

### canonicalurl

Si la valeur est `true`, la fonction [`url()`](3-Templates.md#url) renverra l’URL absolue (`false` par défaut).

```yaml
canonicalurl: <true|false> # false by default
```

### description

Description du site (~ 250 caractères).

```yaml
description: "<description>"
```

### menus

Les menus sont utilisés pour créer des [liens de navigation dans les templates](3-Templates.md#site-menus).

Un menu est composé d’un identifiant unique et des propriétés des entrées (nom, URL, poids).

```yaml
menus:
  <name>:
    - id: <unique-id>   # unique identifier (required)
      name: "<name>"    # name displayed in templates
      url: <url>        # relative or absolute URL
      weight: <integer> # integer value used to sort entries (lighter first)
```

_Exemple :_

```yaml
menus:
  main:
    - id: about
      name: "About"
      url: /about/
      weight: 1
  footer:
    - id: author
      name: The author
      url: https://arnaudligny.fr
      weight: 99
```

:::info
Un menu `main` est créé automatiquement avec l’entrée de la page d’accueil et toutes les entrées de sections ([Voir la gestion du contenu](2-Content.md)).
:::

:::tip
Une page peut être ajoutée à un menu en définissant la variable [`menu`](2-Content.md#menu) dans son front matter.
:::

#### Surcharger une entrée

Une entrée de menu de page peut être surchargée : utilisez l’ID de la page comme `id`.

_Exemple :_

```yaml
menus:
  main:
    - id: index
      name: "My amazing homepage!"
      weight: 1
```

#### Désactiver une entrée

Une entrée de menu peut être désactivée avec `enabled: false`.

_Exemple :_

```yaml
menus:
  main:
    - id: about
      enabled: false
```

### taxonomies

Liste des vocabulaires, associant une valeur au pluriel à une valeur au singulier.

```yaml
taxonomies:
  <plural>: <singular>
```

_Exemple :_

```yaml
taxonomies:
  categories: category
  tags: tag
```

:::warning
Depuis la ++version 8.37.0++, les vocabulaires par défaut `category` et `tag` ont été supprimés.
:::

:::tip
Un vocabulaire peut être désactivé avec la valeur spéciale `disabled`. Exemple : `tags: disabled`.
:::

### theme

Thème à utiliser, ou liste de thèmes.

```yaml
theme: <theme> # theme name
# or
theme:
  - <theme1> # theme name
  - <theme2>
```

:::info
Le premier thème surcharge les suivants, et ainsi de suite.
:::

_Exemples :_

```yaml
theme: hyde
```

```yaml
theme:
  - serviceworker
  - hyde
```

:::info
Voir les [thèmes sur GitHub](https://github.com/Cecilapp?q=theme#org-repositories) ou la [section thèmes](https://cecil.app/themes/) du site.
:::

### date

Format de date et fuseau horaire.

```yaml
date:
  format: <format>     # date format (optional, `F j, Y` by default)
  timezone: <timezone> # date timezone (optional, local time zone by default)
```

- `format` : spécificateur de format de [date PHP](https://php.net/date)
- `timezone` : voir les [fuseaux horaires](https://php.net/timezones)

_Exemple :_

```yaml
date:
  format: 'j F, Y'
  timezone: 'Europe/Paris'
```

### language

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

### languages

Options des langues disponibles, utilisées pour la localisation des [pages](2-Content.md#multilingual) et des [templates](3-Templates.md#localization).

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
    locale: en_EN
  - code: fr
    name: Français
    locale: fr_FR
```

:::info
Une [liste des codes de locale](configuration/locale-codes.md) est disponible si nécessaire.
:::

#### Localiser

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
Dans les [templates](3-Templates.md), vous pouvez accéder à une option avec `{{ site.<option> }}`, par exemple `{{ site.title }}`.  
Si une option n’est pas disponible dans la langue actuelle (ex. : `fr`), elle revient à la valeur globale (ex. : `en`).
:::

### metatags

Les _metatags_ sont des aides SEO et réseaux sociaux qui peuvent être injectées automatiquement dans le `<head>`, via le template _partial_ [`metatags.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/metatags.html.twig).

*[SEO]: Optimisation pour les moteurs de recherche

_Exemple :_

```twig
<html lang="{{ site.language }}">
  <head>
    <meta charset="utf-8">
    {{ include('partials/metatags.html.twig') }}
  </head>
  <body>
    ...
  </body>
</html>
```

Ce template ajoute les balises meta suivantes :

- Titre de page + titre du site, ou titre du site + baseline du site
- Description de la page/du site
- Mots-clés de la page/du site
- Auteur de la page/du site
- Directives des robots des moteurs de recherche (_robots_)
- Liens de favicon
- Liens de navigation (premier, précédent, suivant, dernier)
- URL canonique
- Liens alternatifs (ex. : flux RSS, autres langues)
- Liens [`rel=me`](https://developer.mozilla.org/docs/Web/HTML/Reference/Attributes/rel/me)
- [Open Graph](https://ogp.me)
- Identifiant de profil Facebook
- [Twitter/X Card](https://developer.x.com/docs/x-for-websites/cards/guides/getting-started)
- [Fediverse tag](https://blog.joinmastodon.org/2024/07/highlighting-journalism-on-mastodon/)
- [Structured data](https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data) (JSON-LD)

#### options metatags

Cecil utilise le front matter de la page pour alimenter les meta tags, avec repli sur les options du site si nécessaire.

```yaml
title: "Page/Site title"              # used by title meta
description: "Page/Site description"  # used by description meta
tags: [tag1, tag2]                    # used by keywords meta
keywords: [keyword1, keyword2]        # obsolete
author:                               # used by author meta
  name: <name>                          # author name
  url: <url>                            # author URL
  email: <email>                        # author email
image: image.jpg                      # used by Open Graph and social networks cards
canonical:                            # used to override the generated canonical URL
  url: <URL>                            # absolute URL
  title: "<URL title>"                  # optional canonical title
social:                               # used by social networks meta
  twitter:                              # used by Twitter/X Card
    url: <URL>                            # used for `rel=me` link
    site: username                        # site username
    creator: username                     # page author username
  mastodon:                             # used by Mastodon meta
    url: <URL>                            # used for `rel=me` link
    creator: handle                       # page author account
  facebook:                             # used by Facebook meta
    url: <URL>                            # used for `rel=me` link
    id: 123456789                         # Facebook profile ID
    username: username                    # page author username
```

:::tip
Si besoin, `title` et `image` peuvent être surchargés :

```twig
{{ include('partials/metatags.html.twig', {title: 'Custom title', image: og_image}) }}
```

:::

#### configuration metatags

```yaml
metatags:
  title:                   # title options
    divider: " &middot; "    # string between page title and site title
    only: false              # displays page title only (`false` by default)
    pagination:              # pagination options
      shownumber: true         # displays page number in title (`true` by default)
      label: "Page %s"         # how to display page number (`Page %s` by default)
  robots: "index,follow"   # web crawlers directives (`index,follow` by default)
  favicon:                 # favicon options
    enabled: true            # includes favicon (`true` by default)
    image: favicon.png       # path to favicon image
    sizes:                   # sizes by device
      - "icon": [32, 57, 76, 96, 128, 192, 228]  # web browsers
      - "shortcut icon": [196]                   # Android
      - "apple-touch-icon": [120, 152, 180]      # iOS
  navigation: true         # includes previous and next links (`true` by default)
  image: true              # includes image (`true` by default)
  og: true                 # includes Open Graph meta tags (`true` by default)
  articles: "blog"         # articles' section (`blog` by default)
  twitter: true            # includes Twitter/X Card meta tags (`true` by default)
  mastodon: true           # includes Mastodon meta tags (`true` by default)
  data: false              # includes JSON-LD structured data (`false` by default)
```
