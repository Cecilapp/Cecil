<!--
title: Configuration
description: "Configurez votre site web."
date: 2026-03-27
updated: 2026-05-26
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

Vous pouvez ensuite utiliser ces vocabulaires dans le [front matter](2-Content.md#taxonomie) de votre contenu.

:::warning
Depuis la ++version 8.37.0++, les vocabulaires par défaut `category` et `tag` ont été supprimés. Vous devez les définir dans le fichier de configuration si vous souhaitez les utiliser.
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
    locale: en_US
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

### pages.prefix.separator

Liste des caractères utilisés comme séparateur entre un préfixe de nom de fichier (`date` ou `weight`) et le slug.

```yaml
pages:
  prefix:
    separator: ['-', '_']
```

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

### debug

Active le _mode debug_, utilisé pour afficher des informations de débogage comme des journaux très verbeux, le dump Twig, le profileur Twig, le sourcemap SCSS, etc.

```yaml
debug: true
```

Il existe deux autres façons d’activer le _mode debug_ :

1. Exécuter une commande avec l’option `-vvv`
2. Définir la variable d’environnement `CECIL_DEBUG` à `true`

---

## Pages

### pages.dir

Répertoire source des pages (`pages` par défaut).

```yaml
pages:
  dir: pages
```

### pages.ext

Extensions des fichiers de pages.

```yaml
pages:
  ext: [md, markdown, mdown, mkdn, mkd, text, txt]
```

### pages.exclude

Répertoires, chemins et noms de fichiers à exclure (accepte les glob, les chaînes et les expressions régulières).

```yaml
pages:
  exclude: ['vendor', 'node_modules', '*.scss', '/\.bck$/']
```

### pages.prefix.separator

Liste des caractères utilisés comme séparateur entre un préfixe de nom de fichier (`date` ou `weight`) et le slug.

```yaml
pages:
  prefix:
    separator: ['-', '_']
```

### pages.sortby

Méthode de tri par défaut des collections.

```yaml
pages:
  sortby: date # `date`, `updated`, `title` ou `weight`
  # ou
  sortby:
    variable: date    # `date`, `updated`, `title` ou `weight`
    desc_title: false # tri par titre décroissant
    reverse: false    # inverse l’ordre de tri
```

### pages.pagination

La pagination est disponible pour les pages de liste (_type_ `homepage`, `section` ou `term`).

```yaml
pages:
  pagination:
    max: 5     # nombre maximum d’entrées par page
    path: page # chemin de la page paginée
```

#### Désactiver la pagination

La pagination peut être désactivée :

```yaml
pages:
  pagination: false
```

### pages.paths

Applique un [`path`](2-Content.md#predefined-variables) personnalisé à toutes les pages d’une **_section_**.

```yaml
pages:
  paths:
    - section: <section’s ID>
      path: <path of pages>
```

#### Emplacements des variables de chemin

- `:year`
- `:month`
- `:day`
- `:section`
- `:slug`

_Exemple :_

```yaml
pages:
  paths:
    - section: Blog
      path: :section/:year/:month/:day/:slug # ex. : /blog/2020/12/01/my-post/
# localized
languages:
  - code: fr
    name: Français
    locale: fr_FR
    config:
      pages:
        paths:
          - section: Blog
            path: blogue/:year/:month/:day/:slug # ex. : /blogue/2020/12/01/mon-billet/
```

### pages.frontmatter

Format du front matter des pages (`yaml` par défaut, accepte aussi `ini`, `toml` et `json`).

```yaml
pages:
  frontmatter: yaml
```

### pages.body

Options du corps des pages.

:::info
Pour savoir comment ces options influencent votre contenu, voir la documentation _[Contenu > Markdown](2-Content.md#markdown)_.
:::

#### pages.body.toc

En-têtes utilisés pour construire la table des matières (`[h2, h3]` par défaut).

```yaml
pages:
  body:
    toc: [h2, h3]
```

#### pages.body.highlight

Active la coloration syntaxique du code (`false` par défaut).

```yaml
pages:
  body:
    highlight: false
```

#### pages.body.images

Options de gestion des images.

```yaml
pages:
  body:
    images:
      formats: []       # ajoute des formats d’image alternatifs comme `source` (ex. `[avif, webp]`, tableau vide par défaut)
      resize: 0         # redimensionne toutes les images à <width> (en pixels, `0` pour désactiver)
      responsive: false # ajoute des variantes responsives à l’attribut `srcset` (`false` par défaut)
      lazy: true        # ajoute l’attribut `loading="lazy"` (`true` par défaut)
      decoding: true    # ajoute l’attribut `decoding="async"` (`true` par défaut)
      caption: false    # place l’image dans un élément <figure> et ajoute une <figcaption> contenant le titre (`false` par défaut)
      placeholder: ''   # remplit l’arrière-plan de <img> avant le chargement ('color' ou 'lqip', vide par défaut)
      class: ''         # définit une classe par défaut sur chaque image (vide par défaut)
      dark_suffix: ''   # suffixe de l’image variante sombre (ex. `.dark`), désactivé par défaut
      remote:           # traitement des images distantes (mettre à `false` pour désactiver)
        fallback:         # chemin de l’image de secours, stockée dans le répertoire assets (vide par défaut)
```

:::warning
Depuis la version ++8.41.0++, l’option `pages.body.images.resize` sert à redimensionner les images à une largeur précise, et non plus à activer la fonctionnalité de redimensionnement (activée systématiquement).
:::

:::important
Les options globales, comme les largeurs et tailles des images responsives, sont configurables dans la section [`assets.images`](#assets-images).
:::

:::info
Les images distantes sont téléchargées et converties en _Assets_ pour être manipulées. Vous pouvez désactiver ce comportement en définissant l’option `pages.body.images.remote.enabled` à `false`.
:::

:::tip
Lorsque `dark_suffix` est défini (par ex. `dark_suffix: .dark`), Cecil cherche automatiquement une variante sombre de chaque image (par ex. `photo.dark.jpg` à côté de `photo.jpg`). Si elle est trouvée, l’image est entourée d’un élément `<picture>` avec une balise `<source media="(prefers-color-scheme: dark)">` pour un basculement automatique clair/sombre. Cela fonctionne avec `formats` et `responsive`.
:::

#### pages.body.links

Options de gestion des liens.

```yaml
pages:
  body:
    links:
      embed:
        enabled: false     # transforme les liens en contenu embarqué si possible (`false` par défaut)
        video: [mp4, webm] # extensions des fichiers vidéo
        audio: [mp3]       # extensions des fichiers audio
      external:
        blank: false     # si `true`, ouvre le lien externe dans un nouvel onglet
        noopener: true   # si `true`, ajoute `noopener` à l’attribut `rel`
        noreferrer: true # si `true`, ajoute `noreferrer` à l’attribut `rel`
        nofollow: false  # si `true`, ajoute `nofollow` à l’attribut `rel`
```

#### pages.body.excerpt

Options de gestion des extraits.

```yaml
pages:
  body:
    excerpt:
      separator: excerpt|break # chaîne utilisée comme séparateur (`excerpt|break` par défaut)
      capture: before          # partie à capturer, `before` ou `after` le séparateur (`before` par défaut)
```

### pages.virtual

Les pages virtuelles sont la meilleure façon de créer des pages sans contenu (**front matter uniquement**).

Elles consistent en une liste de pages avec un `path` et quelques variables de front matter.

_Exemple :_

```yaml
pages:
  virtual:
    - path: code
      redirect: https://github.com/ArnaudLigny
```

### pages.default

Les pages par défaut sont des pages créées automatiquement par Cecil (à partir de modèles intégrés) :

```yaml
pages:
  default:
    index:
      path: ''
      title: Accueil
      published: true
    404:
      path: 404
      title: Page introuvable
      layout: 404
      uglyurl: true
      published: true
      excluded: true
    robots:
      path: robots
      title: Robots.txt
      layout: robots
      output: txt
      published: true
      excluded: true
      multilingual: false
    sitemap:
      path: sitemap
      title: Plan de site XML
      layout: sitemap
      output: xml
      changefreq: monthly
      priority: 0.5
      published: true
      excluded: true
      multilingual: false
    xsl/atom:
      path: xsl/atom
      layout: feed
      output: xsl
      uglyurl: true
      published: true
      excluded: true
    xsl/rss:
      path: xsl/rss
      layout: feed
      output: xsl
      uglyurl: true
      published: false
      excluded: true
```

:::info
La structure est presque identique à celle de [`pages.virtual`](#pages-virtual), à l’exception de la clé nommée.
:::

Chaque page peut être :

1. désactivée : `published: false`
2. exclue des pages de liste : `excluded: true`
3. exclue de la localisation : `multilingual: false`

:::tip
Depuis la version 8.68.0, vous pouvez surcharger la page `robots.txt` par défaut en créant une page avec le même `path` :

_pages/robots.md_

```yaml
---
layout: robots
output: txt
---
User-agent: AI-bot
Disallow: /
```

:::

### pages.generators

Les générateurs servent à Cecil pour créer des pages supplémentaires (par ex. sitemap, flux, pagination, etc.) à partir de pages existantes, ou à partir d’autres sources comme le fichier de configuration ou des sources externes.

Voici la liste des générateurs fournis par Cecil, dans un ordre défini :

```yaml
pages:
  generators:
    10: 'Cecil\Generator\DefaultPages'
    20: 'Cecil\Generator\VirtualPages'
    30: 'Cecil\Generator\ExternalBody'
    40: 'Cecil\Generator\Section'
    50: 'Cecil\Generator\Taxonomy'
    60: 'Cecil\Generator\Homepage'
    70: 'Cecil\Generator\Pagination'
    80: 'Cecil\Generator\Alias'
    90: 'Cecil\Generator\Redirect'
```

:::tip
Vous pouvez étendre Cecil avec un [générateur de pages](7-Extend.md#pages-generator).
:::

### pages.subsets

Les sous-ensembles servent à rendre une partie de la collection de pages, selon un chemin, une langue ou un format de sortie spécifique, avec la commande :

```bash
cecil build --render-subset=<name>
```

```yaml
pages:
  subsets:
    <name>:
      path: <path> # chemin glob ou chaîne (ex. `blog/*`, `blog`)
      language: <language> # code de langue (ex. `en`, `fr`)
      output: <output> # format de sortie (ex. `html`, `atom`)
```

_Exemple :_

```yaml
pages:
  subsets:
    blog_en:
      path: blog
      language: en
      output: html
    search_index:
      path: '*'
      output: json
```

---

## Données

Emplacement des fichiers de données et types d’extensions pris en charge.

Formats pris en charge : YAML, JSON, XML et CSV.

### data.dir

Répertoire source des données (`data` par défaut).

```yaml
data:
  dir: data
```

### data.ext

Tableau des extensions de fichiers.

```yaml
data:
  ext: [yaml, yml, json, xml, csv]
```

### data.load

Active la collection `site.data` (`true` par défaut).

```yaml
data:
  load: true
```

---

## Fichiers statiques

Gestion des fichiers statiques copiés (PDF, polices, etc.).

:::important
Vous devez placer les fichiers d’assets, utilisés par [`asset()`](3-Templates.md#asset), dans le [`répertoire assets`](4-Configuration.md#assets-dir) afin d’éviter des copies de fichiers inutiles.
:::

### static.dir

Répertoire source des fichiers statiques (`static` par défaut).

```yaml
static:
  dir: static
```

### static.target

Répertoire vers lequel les fichiers statiques sont copiés (`root` par défaut).

```yaml
static:
  target: ''
```

### static.exclude

Liste des fichiers exclus. Accepte les glob, les chaînes et les expressions régulières.

```yaml
static:
  exclude: ['sass', 'scss', '*.scss', 'package*.json', 'node_modules']
```

:::tip
Si vous utilisez [Bootstrap Icons](https://icons.getbootstrap.com), vous pouvez exclure `node_modules` sauf `node_modules/bootstrap-icons` avec une expression régulière :

```yaml
exclude: ['sass', 'scss', '*.scss', 'package*.json', '#node_modules/(?!bootstrap-icons)#']
```

:::

### static.load

Active la collection `site.static` (`false` par défaut).

```yaml
static:
  load: false
```

### static.mounts

Permet de copier des fichiers ou répertoires spécifiques vers une destination spécifique.

```yaml
static:
  mounts: []
```

### exemple de static

```yaml
static:
  dir: docs
  target: docs
  exclude: ['sass', '*.scss', '/\.bck$/']
  load: true
  mounts:
    - source/path/file.ext: dest/path/file.ext
    - node_modules/bootstrap-icons/font/fonts: fonts
```

## Ressources

Gestion des ressources (images, fichiers CSS et JS).

### assets.dir

Répertoire source des ressources (`assets` par défaut).

```yaml
assets:
  dir: assets
```

### assets.target

Répertoire où les fichiers de ressources distants et redimensionnés sont enregistrés (`root` par défaut).

```yaml
assets:
  target: ''
```

### assets.fingerprint

Active le fingerprinting (cache busting) pour les fichiers de ressources (`true` par défaut).

```yaml
assets:
  fingerprint: true
```

### assets.compile

Active la compilation des fichiers [Sass](https://sass-lang.com) (`true` par défaut). Voir la [documentation de scssphp](https://scssphp.github.io/scssphp/docs/#output-formatting) pour les détails des options.

```yaml
assets:
  compile:
    style: expanded      # style de compilation (`expanded` ou `compressed`, `expanded` par défaut)
    import: [sass, scss] # liste des chemins importés (`[sass, scss, node_modules]` par défaut)
    sourcemap: false     # active les sourcemaps en mode debug (`false` par défaut)
    variables: []        # liste de variables préconfigurées (vide par défaut)
```

:::info
`sourcemap` sert à déboguer la compilation SCSS ([mode debug](#debug) requis).
:::

### assets.minify

Active la minification CSS et JS (`true` par défaut).

```yaml
assets:
  minify: true
```

### assets.images

Gestion des images.

```yaml
assets:
  images:
    optimize: false # active l’optimisation d’images avec JpegOptim, Optipng, Pngquant 2, SVGO 1, Gifsicle, cwebp, avifenc (`false` par défaut)
    quality: 75     # qualité d’image pour `optimize` et `resize` (`75` par défaut)
    responsive:
      widths: [480, 640, 768, 1024, 1366, 1600, 1920] # largeurs d’image pour l’attribut `srcset`
      sizes:
        default: '100vw' # attribut `sizes` par défaut (`100vw` par défaut)
```

### assets.images.cdn

L’URL des ressources image peut être facilement remplacée par une `url` de CDN fournie.

```yaml
assets:
  images:
    cdn:
      enabled: false  # active Image CDN (`false` par défaut)
      canonical: true # `image_url` est canonique (au lieu d’un chemin relatif) (`true` par défaut)
      remote: true    # prend aussi en charge les images non locales (`true` par défaut)
      account: 'xxxx' # compte du fournisseur
      url: 'https://provider.tld/%account%/%image_url%?w=%width%&q=%quality%&format=%format%'
```

`url` est un modèle qui contient des variables :

- `%account%` remplacé par l’option `assets.images.cdn.account`
- `%image_url%` remplacé par l’URL canonique de l’image ou par `path`
- `%width%` remplacé par la largeur de l’image
- `%quality%` remplacé par l’option `assets.images.quality`
- `%format%` remplacé par le format de l’image

Voir les [**fournisseurs CDN**](configuration/cdn-providers.md).

### assets.remote.useragent

User agent utilisé pour télécharger les ressources distantes.

```yaml
assets:
  remote:
    useragent:
      default: <string> # user agent par défaut
      useragent1: <string>
      useragent2: <string>
```

## Disposition

Options des templates.

### layouts.dir

Répertoire source des templates (`layouts` par défaut).

```yaml
layouts:
  dir: layouts
```

### layouts.autoescape

Surcharge l’option Twig `autoescape`.

Si la valeur est `null` (par défaut), Cecil applique une stratégie basée sur l’extension du nom de template :

- `*.js.twig` -> `js`
- `*.css.twig` -> `css`
- `*.html.twig` et `*.twig` -> `html`
- toute autre extension -> `false`

```yaml
layouts:
  autoescape: null # utilise la stratégie automatique Cecil selon l’extension du template (par défaut)
  # autoescape: false
  # autoescape: html
  # autoescape: js
```

### layouts.images

Options de gestion des images.

```yaml
layouts:
  images:
    formats: []       # utilisé par la fonction `html` : ajoute des formats d’image alternatifs comme `source` (ex. `[avif, webp]`, tableau vide par défaut)
    responsive: false # utilisé par la fonction `html` : ajoute des images responsives ('width' ou 'density', `false` par défaut)
    dark_suffix: ''   # suffixe de l’image variante sombre (ex. `.dark`), désactivé par défaut
```

### layouts.translations

Options de gestion des traductions.

```yaml
layouts:
  translations:
    dir: translations       # répertoire source des traductions (`translations` par défaut)
    formats: ['yaml', 'mo'] # format des fichiers de traduction (`yaml` et `mo` par défaut)
```

### layouts.components

Options des [composants de template](3-Templates.md#components).

```yaml
layouts:
  components:
    dir: components # répertoire source des composants (`components` par défaut)
    ext: twig       # extension des fichiers de composants (`twig` par défaut)
```

---

## Sortie

Définit où et dans quel format les pages sont rendues.

### output.dir

Répertoire où les fichiers de pages rendues sont enregistrés (`_site` par défaut).

```yaml
output:
  dir: _site
```

### output.formats

Liste de définition des formats de sortie, utilisés pour rendre les pages (par ex. HTML, Atom, RSS, JSON, XML, etc.).

```yaml
output:
  formats:
    - name: <name>            # nom du format, par ex. `html` (requis)
      mediatype: <media type> # type MIME, ex. `text/html` (facultatif)
      subpath: <sub path>     # sous-chemin, ex. `amp` dans `path/amp/index.html` (facultatif)
      filename: <file name>   # nom du fichier, ex. `index` dans `path/index.html` (facultatif)
      extension: <extension>  # extension du fichier, ex. `html` dans `path/index.html` (requis)
      exclude: [<variable>]   # n’applique pas ce format aux pages identifiées par les variables listées, ex. `[redirect, paginated]` (facultatif)
```

Ces formats sont utilisés dans la configuration [`output.pagetypeformats`](#output-pagetypeformats) et dans la variable de page [`output`](2-Content.md#output).

#### Formats par défaut

Cecil fournit quelques [formats par défaut](https://github.com/Cecilapp/Cecil/blob/master/config/base.php#L81-L162), qui peuvent être surchargés dans le fichier de configuration : `html` (par défaut), `atom`, `rss`, `json`, `xml`, `txt`, `amp`, `js`, `webmanifest`, `xsl`, `jsonfeed`, `iframe`, `oembed`.

### output.pagetypeformats

Il n’est pas nécessaire de définir la variable `output` pour chaque page, car Cecil applique automatiquement les formats définis pour chaque type de page (`homepage`, `page`, `section`, `vocabulary` et `term`).

```yaml
output:
  pagetypeformats:
    page: [<format>]
    homepage: [<format>]
    section: [<format>]
    vocabulary: [<format>]
    term: [<format>]
```

Plusieurs formats peuvent être définis pour un même type de page. Par exemple, le type de page `section` peut être rendu automatiquement en HTML et Atom :

```yaml
output:
  pagetypeformats:
    section: [html, atom]
```

:::info
Pour rendre une page, [Cecil recherche un template](3-Templates.md#lookup-rules) nommé `<layout>.<format>.twig` (ex. `page.html.twig`).
:::

### exemple de output

```yaml
output:
  dir: _site
  formats:
    - name: html
      mediatype: text/html
      filename: index
      extension: html
    - name: atom
      mediatype: application/xml
      filename: atom
      extension: xml
      exclude: [redirect, paginated]
  pagetypeformats:
    page: [html]
    homepage: [html, atom]
    section: [html, atom]
    vocabulary: [html]
    term: [html, atom]
```

### Post-process

Vous pouvez étendre les capacités de Cecil avec un [post-processeur de sortie](7-Extend.md#output-post-processor) pour modifier les fichiers de sortie après leur génération.

---

## Cache

Options de cache.

### cache.enabled

Le cache est activé par défaut (`true`), mais vous pouvez le désactiver avec :

```yaml
cache:
  enabled: false
```

:::warning
Il n’est pas recommandé de désactiver le cache pour des raisons de performance.
:::

### cache.dir

Répertoire où les fichiers de cache sont stockés (`.cache` par défaut).

```yaml
cache:
  dir: '.cache'
```

:::info
Le répertoire de cache est relatif au répertoire du site, mais vous pouvez utiliser un chemin absolu : cela peut être utile pour stocker le cache dans un répertoire partagé.
:::

### cache.assets

Options du cache des ressources.

#### cache.assets.ttl

Temps de vie du cache des ressources en secondes (`null` par défaut = aucune expiration).

```yaml
cache:
  assets:
    ttl: ~
```

#### cache.assets.remote.ttl

Temps de vie du cache des ressources distantes en secondes (7 jours par défaut).

```yaml
cache:
  assets:
    remotes:
      ttl: 604800 # 7 jours
```

### cache.templates

Désactive le cache des templates avec `false` (`true` par défaut).

```yaml
cache:
  templates: true
```

:::info
Voir la [documentation du cache des templates](3-Templates.md#cache) pour plus de détails.
:::

### cache.translations

Désactive le cache des traductions avec `false` (`true` par défaut).

```yaml
cache:
  translations: true
```

---

## Serveur

### server.headers

Vous pouvez définir des [en-têtes HTTP](https://developer.mozilla.org/docs/Glossary/Response_header) personnalisés, utilisés par le serveur d’aperçu local.

:::warning
Depuis la version ++8.38.0++, l’option `headers` a été déplacée dans la section `server.headers`.
:::

```yaml
server:
  headers:
    - path: <path> # chemin relatif, préfixé par un slash. Prend en charge le joker "*".
      headers:
        - key: <key>
          value: "<value>"
```

:::tip
C’est utile pour tester une [Content Security Policy](https://developer.mozilla.org/docs/Web/HTTP/CSP) ou `Cache-Control` personnalisée.
:::

_Exemple :_

```yaml
server:
  headers:
    - path: /*
      headers:
        - key: X-Frame-Options
          value: "SAMEORIGIN"
        - key: X-XSS-Protection
          value: "1; mode=block"
        - key: X-Content-Type-Options
          value: "nosniff"
        - key: Content-Security-Policy
          value: "default-src 'self'; object-src 'self'; img-src 'self'"
        - key: Strict-Transport-Security
          value: "max-age=31536000; includeSubDomains; preload"
    - path: /assets/*
      headers:
        - key: Cache-Control
          value: "public, max-age=31536000"
    - path: /foo.html
      headers:
        - key: Foo
          value: "bar"
```

---

## Optimisation

Les options d’optimisation permettent d’activer la compression des fichiers de sortie : HTML, CSS, JavaScript et images.

```yaml
optimize:
  enabled: false     # active l’optimisation des fichiers (`false` par défaut)
  html:
    enabled: true    # active l’optimisation des fichiers HTML
    ext: [html, htm]   # extensions de fichiers prises en charge
  css:
    enabled: true    # active l’optimisation des fichiers CSS
    ext: [css]         # extensions de fichiers prises en charge
  js:
    enabled: true    # active l’optimisation des fichiers JavaScript
    ext: [js]          # extensions de fichiers prises en charge
  images:
    enabled: true    # active l’optimisation des fichiers images
    ext: [jpeg, jpg, png, gif, webp, svg, avif] # extensions de fichiers prises en charge
```

Cette option est désactivée par défaut et peut être activée via :

```yaml
optimize: true
```

Une fois l’option globale activée, les 4 types de fichiers seront traités.  
Il est possible de désactiver chacun d’eux via `enabled: false` et de modifier l’extension des fichiers traités via `ext`.

:::tip
Il est également possible d’activer cette option via la CLI lors de l’utilisation des commandes "build" et "serve" avec l’option `--optimize`.
:::

:::important
Le compresseur d’**images** utilisera les binaires suivants s’ils sont présents sur le système : [JpegOptim](https://github.com/tjko/jpegoptim), [Optipng](http://optipng.sourceforge.net/), [Pngquant 2](https://pngquant.org/), [SVGO](https://github.com/svg/svgo), [Gifsicle](http://www.lcdf.org/gifsicle/), [cwebp](https://developers.google.com/speed/webp/docs/cwebp) et [avifenc](https://github.com/AOMediaCodec/libavif).
:::

---

## Surcharge de configuration

### Variables d’environnement

La configuration peut être surchargée via des [variables d’environnement](https://en.wikipedia.org/wiki/Environment_variable).

Chaque nom de variable d’environnement doit être préfixé par `CECIL_` et la clé de configuration doit être en majuscules.

Par exemple, la commande suivante définit le `baseurl` du site :

```bash
export CECIL_BASEURL="https://example.com/"
```

### Option CLI

Vous pouvez combiner plusieurs fichiers de configuration avec l’option `--config` (priorité de gauche à droite) :

```bash
php cecil.phar --config config-1.yml,config-2.yml
```
