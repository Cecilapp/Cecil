<!--
title: "Pages"
description: "Dossier des pages, tri, pagination, chemins, corps, pages par défaut, générateurs, etc."
date: 2026-03-27
updated: 2026-10-26
-->
# Pages

Ces options contrôlent la façon dont les pages sont chargées, triées, paginées, routées et générées.

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

Répertoires, chemins et noms de fichiers à exclure (accepte les globs, les chaînes et les expressions régulières).

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

Ou :

```yaml
pages:
  pagination:
    enabled: false
```

:::tip
Lorsque la pagination est désactivée globalement, une page de liste peut tout de même l’activer dans son [front matter](../content/1-pages.fr.md#pagination) avec `pagination.enabled: true`.
:::

## pages.paths

Applique un [`path`](../content/2-front-matter.fr.md#variables-predefinies) personnalisé à toutes les pages d’une **_section_**.

```yaml
pages:
  paths:
    - section: <section’s ID>
      path: <path of pages>
```

### Variables de chemin

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

## pages.converter

Classe utilisée pour convertir le front matter et le corps Markdown des pages (`Cecil\Converter\Converter` par défaut).

```yaml
pages:
  converter: Cecil\Converter\Converter
```

Voir [Convertisseur Markdown](../developers/1-extend.fr.md#convertisseur-markdown) pour utiliser le vôtre.

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

### pages.body.anchors

Titres avec un lien d’ancre (`[h2, h3]` par défaut) : un élément `<a class="heading-anchor" href="#<id>">` vide est ajouté à la fin de chacun de ces titres ayant un ID, pour être stylé par les templates (ex. : un `#` affiché au survol).

```yaml
pages:
  body:
    anchors: [h2, h3] # false pour désactiver les ancres
```

### pages.body.highlight

Active la coloration syntaxique du code (`true` par défaut).

```yaml
pages:
  body:
    highlight: false # `false` pour désactiver la coloration syntaxique
```

Le moteur de coloration peut être changé avec l’option `engine` :

```yaml
pages:
  body:
    highlight:
      enabled: true    # `true` par défaut
      engine: tempest  # `highlightjs` (par défaut) ou `tempest`
```

- `highlightjs` : [highlight.php](https://github.com/scrivo/highlight.php), un portage PHP de highlight.js (plus de 190 langages, classes CSS `hljs-*`)
- `tempest` : [tempest/highlight](https://github.com/tempestphp/highlight), un moteur de coloration rapide côté serveur (langages du web : PHP, HTML, CSS, JavaScript, JSON, YAML, Twig, SQL, XML, etc., classes CSS `hl-*`)

:::info
Ces options ne s’appliquent qu’au contenu Markdown : le filtre [`highlight`](../templates/reference/3-filters.fr.md#highlight) des templates utilise l’option [`layouts.highlight.engine`](7-layouts.fr.md#layouts-highlight).
:::

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
      dark_suffix: ~    # suffixe de l’image variante sombre (ex. `.dark`), désactivé par défaut
      mobile_suffix: ~  # suffixe de l’image variante mobile (ex. `.mobile`), désactivé par défaut
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
        audio: [mp3, ogg, wav] # extensions des fichiers audio
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

## pages.default

Les pages par défaut sont des pages sans fichier de contenu (**front matter uniquement**), indexées par ID, chacune avec un `path` et quelques variables de front matter.

Cecil fournit des pages par défaut intégrées (à partir de templates intégrés) :

```yaml
pages:
  default:
    index:
      path: ''
      title: Home
      published: true
    404:
      path: 404
      title: Page not found
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
      title: XML sitemap
      layout: sitemap
      output: xml
      changefreq: monthly
      priority: 0.5
      published: true
      excluded: true
      multilingual: false
    xsl/sitemap:
      path: xsl/sitemap
      layout: sitemap
      output: xsl
      uglyurl: true
      published: true
      excluded: true
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

Chaque page peut être :

1. désactivée : `published: false`
2. exclue des pages de liste : `excluded: true`
3. exclue de la localisation : `multilingual: false`

Vous pouvez aussi ajouter vos propres pages (un thème peut faire de même dans son `config.yml`) :

```yaml
pages:
  default:
    code:
      path: code
      redirect: https://github.com/ArnaudLigny
```

Comme toute autre page, une page par défaut est listée (par exemple dans `site.pages.showable`, dans le sitemap, sur la page d’accueil) : utilisez `excluded: true` pour exclure des listes une page technique (index JSON, manifeste, etc.).

:::important
Depuis la version ++9.19.0++, `pages.virtual` est supprimée : déplacez ses pages dans `pages.default`, indexées par ID (par exemple le chemin de la page). Les pages sans fichier de contenu ne sont plus exclues des listes par défaut.
:::

:::tip
Depuis la version ++8.68.0++, vous pouvez surcharger la page `robots.txt` par défaut en créant une page avec le même `path` :

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

Cecil utilise les générateurs pour créer des pages supplémentaires (par ex. sitemap, flux, pagination, etc.) à partir de pages existantes, ou à partir d’autres sources comme le fichier de configuration ou des sources externes.

Les générateurs sont indexés par nom et exécutés par ordre croissant de priorité. Voici la liste des générateurs fournis par Cecil :

```yaml
pages:
  generators:
    DefaultPages: { class: Cecil\Generator\DefaultPages, priority: 10 }
    ExternalBody: { class: Cecil\Generator\ExternalBody, priority: 30 }
    Section:      { class: Cecil\Generator\Section, priority: 40 }
    Taxonomy:     { class: Cecil\Generator\Taxonomy, priority: 50 }
    Homepage:     { class: Cecil\Generator\Homepage, priority: 60 }
    Pagination:   { class: Cecil\Generator\Pagination, priority: 70 }
    Alias:        { class: Cecil\Generator\Alias, priority: 80 }
    Redirect:     { class: Cecil\Generator\Redirect, priority: 90 }
```

Un générateur intégré peut être désactivé ou déplacé par son nom :

```yaml
pages:
  generators:
    Redirect: false            # désactivé
    Section: { priority: 45 }  # déplacé
```

Voir [Extensions](../developers/1-extend.fr.md#configuration) pour la syntaxe complète.

:::tip
Vous pouvez étendre Cecil avec un [générateur de pages](../developers/1-extend.fr.md#generateurs-de-pages).
:::

## pages.subsets

Les sous-ensembles servent à générer une partie de la collection de pages, selon un chemin, une langue ou un format de sortie spécifique, avec la commande :

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
