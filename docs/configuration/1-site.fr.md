<!--
title: "Options du site"
description: "title, baseurl, menus, taxonomies, theme, date, metatags, debug, etc."
date: 2026-03-27
updated: 2026-10-10
-->
# Options du site

Ces options définissent les réglages principaux du site : titre, URL, description, menus, taxonomies, thème, métadonnées, etc.

## title

Titre principal du site.

```yaml
title: "<site title>"
```

## baseline

Description courte (~ 20 caractères).

```yaml
baseline: "<baseline>"
```

## baseurl

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

## canonicalurl

Si la valeur est `true`, la fonction [`url()`](../templates/reference/1-functions.fr.md#url) renverra l’URL absolue (`false` par défaut).

```yaml
canonicalurl: <true|false> # false by default
```

## description

Description du site (~ 250 caractères).

```yaml
description: "<description>"
```

## menus

Les menus sont utilisés pour créer des [liens de navigation dans les templates](../templates/2-variables.fr.md#site-menus).

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
Un menu `main` est créé automatiquement avec l’entrée de la page d’accueil et toutes les entrées de sections ([Voir la gestion du contenu](../content/index.fr.md)).
:::

:::tip
Une page peut être ajoutée à un menu en définissant la variable [`menu`](../content/2-front-matter.fr.md#menu) dans son front matter.
:::

### Surcharger une entrée

Une entrée de menu de page peut être surchargée : utilisez l’ID de la page comme `id`.

_Exemple :_

```yaml
menus:
  main:
    - id: index
      name: "My amazing homepage!"
      weight: 1
```

### Désactiver une entrée

Une entrée de menu peut être désactivée avec `enabled: false`.

_Exemple :_

```yaml
menus:
  main:
    - id: about
      enabled: false
```

## taxonomies

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

Vous pouvez ensuite utiliser ces vocabulaires dans le [front matter](../content/2-front-matter.fr.md#taxonomie) de votre contenu.

:::warning
Depuis la ++version 8.37.0++, les vocabulaires par défaut `category` et `tag` ont été supprimés. Vous devez les définir dans le fichier de configuration si vous souhaitez les utiliser.
:::

:::tip
Un vocabulaire peut être désactivé avec la valeur spéciale `disabled`. Exemple : `tags: disabled`.
:::

## theme

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

## date

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

## metatags

Les _metatags_ sont des aides SEO et réseaux sociaux qui peuvent être injectées automatiquement dans le `<head>`, via le template [`partials/metatags.html.twig`](https://github.com/Cecilapp/Cecil/blob/main/resources/layouts/partials/metatags.html.twig).

*[SEO]: Optimisation pour les moteurs de recherche

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
- [Dublin Core](https://www.dublincore.org/specifications/dublin-core/dcmi-terms/)
- [Structured data](https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data) (JSON-LD)

### options metatags

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

### configuration metatags

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
  dc: false                # includes Dublin Core meta tags (`false` by default)
  data: false              # includes JSON-LD structured data (`false` by default)
```

## debug

Active le _mode debug_, utilisé pour afficher des informations de débogage comme des journaux très verbeux, le dump Twig, le profileur Twig, le sourcemap SCSS, etc.

```yaml
debug: true
```

Il existe deux autres façons d’activer le _mode debug_ :

1. Exécuter une commande avec l’option `-vvv`
2. Définir la variable d’environnement `CECIL_DEBUG` à `true`

---
