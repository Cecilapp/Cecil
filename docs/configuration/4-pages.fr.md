<!--
title: "Pages"
description: "Dossier des pages, tri, pagination, chemins, corps, pages virtuelles, générateurs, etc."
date: 2026-03-27
updated: 2026-10-05
-->
# Pages

## pages.dir

Répertoire source des pages (`pages` par défaut).

```yaml
pages:
  dir: pages
```

## pages.ext

Extensions des fichiers de pages.

```yaml
pages:
  ext: [md, markdown, mdown, mkdn, mkd, text, txt]
```

## pages.exclude

Répertoires, chemins et noms de fichiers à exclure (accepte les glob, les chaînes et les expressions régulières).

```yaml
pages:
  exclude: ['vendor', 'node_modules', '*.scss', '/\.bck$/']
```

## pages.prefix.separator

Liste des caractères utilisés comme séparateur entre un préfixe de nom de fichier (`date` ou `weight`) et le slug.

```yaml
pages:
  prefix:
    separator: ['-', '_']
```

## pages.sortby

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

## pages.pagination

La pagination est disponible pour les pages de liste (_type_ `homepage`, `section` ou `term`).

```yaml
pages:
  pagination:
    max: 5     # nombre maximum d’entrées par page
    path: page # chemin de la page paginée
```

### Désactiver la pagination

La pagination peut être désactivée :

```yaml
pages:
  pagination: false
```

## pages.paths

Applique un [`path`](../content/2-front-matter.fr.md#variables-predefinies) personnalisé à toutes les pages d’une **_section_**.

```yaml
pages:
  paths:
    - section: <section’s ID>
      path: <path of pages>
```

### Emplacements des variables de chemin

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

## pages.frontmatter

Format du front matter des pages (`yaml` par défaut, accepte aussi `ini`, `toml` et `json`).

```yaml
pages:
  frontmatter: yaml
```

## pages.body

Options du corps des pages.

:::info
Pour savoir comment ces options influencent votre contenu, voir la documentation _[Contenu > Markdown](../content/3-markdown.fr.md)_.
:::

### pages.body.toc

En-têtes utilisés pour construire la table des matières (`[h2, h3]` par défaut).

```yaml
pages:
  body:
    toc: [h2, h3]
```

### pages.body.highlight

Active la coloration syntaxique du code (`true` par défaut).

```yaml
pages:
  body:
    highlight: false # définissez à false pour désactiver la coloration syntaxique
```

### pages.body.images

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
      mobile_suffix: '' # suffixe de l’image variante mobile (ex. `.mobile`), désactivé par défaut
      mobile_media_query: '(max-width: 767px)' # media query de la `<source>` de la variante mobile
      remote:           # traitement des images distantes (mettre à `false` pour désactiver)
        fallback:         # chemin de l’image de secours, stockée dans le répertoire assets (vide par défaut)
```

:::warning
Depuis la version ++8.41.0++, l’option `pages.body.images.resize` sert à redimensionner les images à une largeur précise, et non plus à activer la fonctionnalité de redimensionnement (activée systématiquement).
:::

:::important
Les options globales, comme les largeurs et tailles des images responsives, sont configurables dans la section [`assets.images`](6-assets.fr.md#assets-images).
:::

:::info
Les images distantes sont téléchargées et converties en _Assets_ pour être manipulées. Vous pouvez désactiver ce comportement en définissant l’option `pages.body.images.remote.enabled` à `false`.
:::

:::tip
Lorsque `dark_suffix` est défini (par ex. `dark_suffix: .dark`), Cecil cherche automatiquement une variante sombre de chaque image (par ex. `photo.dark.jpg` à côté de `photo.jpg`). Si elle est trouvée, l’image est entourée d’un élément `<picture>` avec une balise `<source media="(prefers-color-scheme: dark)">` pour un basculement automatique clair/sombre. Cela fonctionne avec `formats` et `responsive`.

De la même manière, lorsque `mobile_suffix` est défini (par ex. `mobile_suffix: .mobile`), Cecil cherche une variante mobile de chaque image (par ex. `photo.mobile.jpg` à côté de `photo.jpg`) et ajoute une balise `<source>` avec la media query `mobile_media_query` (`(max-width: 767px)` par défaut). Si `dark_suffix` est également défini, la variante sombre de l’image mobile (par ex. `photo.mobile.dark.jpg`) est utilisée sur mobile en mode sombre. Les sources mobiles sont placées avant les sources sombres, afin que la variante mobile soit prioritaire.
:::

### pages.body.links

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

### pages.body.excerpt

Options de gestion des extraits.

```yaml
pages:
  body:
    excerpt:
      separator: excerpt|break # chaîne utilisée comme séparateur (`excerpt|break` par défaut)
      capture: before          # partie à capturer, `before` ou `after` le séparateur (`before` par défaut)
```

## pages.virtual

Les pages virtuelles sont la meilleure façon de créer des pages sans contenu (**front matter uniquement**).

Elles consistent en une liste de pages avec un `path` et quelques variables de front matter.

_Exemple :_

```yaml
pages:
  virtual:
    - path: code
      redirect: https://github.com/ArnaudLigny
```

## pages.default

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

## pages.generators

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
Vous pouvez étendre Cecil avec un [générateur de pages](../developers/1-extend.fr.md#generateur-de-pages).
:::

## pages.subsets

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
