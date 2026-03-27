<!--
title: Templates
description: "Travailler avec les layouts, les templates et les composants."
date: 2026-03-27
slug: templates
-->
# Templates

Cecil est alimenté par le moteur de templates [Twig](https://twig.symfony.com), veuillez donc vous référer à la **[documentation officielle](https://twig.symfony.com/doc/templates.html)** pour savoir comment l'utiliser.

## Example

```twig
{# template d'exemple #}
<h1>{{ page.title }} - {{ site.title }}</h1>
<span>{{ page.date|date('j M Y') }}</span>
<p>{{ page.content }}</p>
<ul>
{% for tag in page.tags %}
  <li>{{ tag }}</li>
{% endfor %}
</ul>
```

- `{# #}` : ajoute des commentaires
- `{{ }}` : affiche le contenu des variables ou des expressions
- `{% %}` : exécute des instructions, comme une boucle (`for`), une condition (`if`), etc.
- `|filter()` : filtre ou formate le contenu

## Organisation des fichiers

### Types de templates

Il existe trois types de templates, **_layouts_**, **_components_** et **_autres templates_** : _layouts_ sont utilisés pour afficher les [pages](2-Content.md#pages), et chacun d'eux peut [inclure des templates](https://twig.symfony.com/doc/templates.html#including-other-templates) et [components](#components).

### Convention de nommage

Les fichiers templates sont stockés dans le répertoire `layouts/` et doivent être nommés selon la convention suivante :

```plaintext
layouts/(<section>/)<type>|<layout>.<format>(.<language>).twig
```

`<section>` (facultatif)
:  La section de la page (ex. : `blog`).

`<type>`
:  Le type de page : `home` (ou `index`) pour _homepage_, `list` pour _list_, `page` pour _page_, etc. (Voir [_Règles de recherche_](#lookup-rules) pour plus de détails).

`<layout>` (facultatif)
:  Le nom de la layout personnalisée défini dans le [front-matter](2-Content.md#front-matter) de la page (par exemple : `layout: my-layout`).

`<language>` (facultatif)
:  La langue de la page (ex. : `fr`).

`<format>`
:  Le [format de sortie](4-Configuration.md#output-formats) de la page rendue (par exemple : `html`, `rss`, `json`, `xml`, etc.).

`.twig`
:  L'extension de fichier Twig obligatoire.

_Exemples :_

```plaintext
layouts/home.html.twig       # `type` is "homepage"
layouts/page.html.twig       # `type` is "page"
layouts/page.html.fr.twig    # `type` is "page" and `language` is "fr"
layouts/my-layout.html.twig  # `layout` is "my-layout"
layouts/blog/list.html.twig  # `section` is "blog"
layouts/blog/list.rss.twig   # `section` is "blog" and `format` is "rss"
```

```plaintext
<mywebsite>
├─ ...
├─ layouts                  <- Layouts and templates
|  ├─ my-layout.html.twig
|  ├─ index.html.twig       <- Used by type "homepage"
|  ├─ list.html.twig        <- Used by types "homepage", "section" and "term"
|  ├─ list.rss.twig         <- Used by types "homepage", "section" and "term", for RSS output format
|  ├─ page.html.twig        <- Used by type "page"
|  ├─ ...
|  ├─ _default              <- Default layouts, that can be easily extended
|  |  ├─ list.html.twig
|  |  ├─ page.html.twig
|  |  └─ ...
|  └─ partials
|     ├─ footer.html.twig   <- Included template
|     └─ ...
└─ themes
   └─ <theme>
      └─ layouts            <- Theme layouts and templates
         └─ ...
```

### Templates intégrés

Cecil est livré avec un ensemble de [templates intégrés](https://github.com/Cecilapp/Cecil/tree/master/resources/layouts).

:::tip
Si vous avez besoin de modifier des templates intégrés, vous pouvez facilement les extraire via la commande suivante : ils seront copiés dans le répertoire `layouts` de votre site.

```bash
php cecil.phar util:templates:extract
```

:::

#### Templates par défaut

[`_default/page.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/page.html.twig)
:   Un modèle principal simple avec un CSS propre.

[`_default/list.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/list.html.twig)
:   Une liste de pages avec une pagination (facultative).

[`_default/list.atom.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/list.atom.twig)
:   Un flux Atom.

[`_default/list.rss.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/list.rss.twig)
:   Un flux RSS.

[`_default/vocabulary.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/vocabulary.html.twig)
:   Une simple liste de tous les termes d'un vocabulaire.

[`_default/404.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/404.html.twig)
:   Un modèle d'erreur de base 404 (« Page non trouvée »).

[`_default/sitemap.xml.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/sitemap.xml.twig)
:   Le modèle [`sitemap.xml`](https://www.sitemaps.org) : liste de toutes les pages triées par date.

[`_default/robots.txt.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/robots.txt.twig)
:   Le modèle [`robots.txt`](https://en.wikipedia.org/wiki/Robots.txt) : autorise toutes les pages sauf 404 et ajoute une référence au plan du site.

[`_default/redirect.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/redirect.html.twig)
:   Le modèle de redirection.

#### Templates partiels

[`partials/navigation.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/navigation.html.twig)
:   Une navigation dans le menu principal.

[`partials/paginator.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/paginator.html.twig)
:   Une navigation paginée simple pour les templates de liste avec des liens "Précédent" et "Suivant".

[`partials/metatags.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/metatags.html.twig)
:   Toutes les balises méta dans un seul modèle : titre, description, canonique, graphique ouvert, carte Twitter, etc. Voir [_metatags_ configuration](4-Configuration.md#metatags).

[`partials/languages.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/partials/languages.html.twig)
:   Un sélecteur [langues](4-Configuration.md#languages) de base.

## Règles de recherche

Dans la plupart des cas **vous n'avez pas besoin de préciser la layout** : Cecil sélectionne la layout la plus appropriée, en fonction du _type_ de la page.

### Recherche de template pour la page d'accueil

Par exemple, la sortie HTML de _home page_ (`index.md`) sera rendue :

1. avec `my-layout.html.twig` si la variable `layout` est définie sur "my-layout" (dans le préambule)
2. sinon, avec `home.html.twig` si le fichier existe
3. sinon, avec `index.html.twig` si le fichier existe
4. sinon, avec `list.html.twig` si le fichier existe
5. etc.

Toutes les règles sont détaillées ci-dessous, pour chaque type de page, par ordre de priorité.

### Type _homepage_

1. `<layout>.<format>.twig`
2. `index.<format>.twig`
3. `home.<format>.twig`
4. `list.<format>.twig`
5. `_default/<layout>.<format>.twig`
6. `_default/index.<format>.twig`
7. `_default/home.<format>.twig`
8. `_default/list.<format>.twig`
9. `_default/page.<format>.twig`

### Type _page_

1. `<section>/<layout>.<format>.twig`
2. `<layout>.<format>.twig`
3. `<section>/page.<format>.twig`
4. `_default/<layout>.<format>.twig`
5. `page.<format>.twig`
6. `_default/page.<format>.twig`

### Type _section_

1. `<layout>.<format>.twig`
2. `<section>/index.<format>.twig`
3. `<section>/list.<format>.twig`
4. `section/<section>.<format>.twig`
5. `_default/section.<format>.twig`
6. `list.<format>.twig`
7. `_default/list.<format>.twig`

### Type _vocabulary_

1. `taxonomy/<plural>.<format>.twig`
2. `vocabulary.<format>.twig`
3. `_default/vocabulary.<format>.twig`

### Type _term_

1. `taxonomy/<term>.<format>.twig`
2. `taxonomy/<singular>.<format>.twig`
3. `term.<format>.twig`
4. `_default/term.<format>.twig`
5. `_default/list.<format>.twig`

:::info
La plupart de ces layouts sont disponibles par défaut, voir [templates intégrés](#templates-intégrés).
:::

## Variables

> L'application transmet des variables aux templates pour manipulation dans le modèle. Les variables peuvent également avoir des attributs ou des éléments auxquels vous pouvez accéder.
> Utilisez un point (.) pour accéder aux attributs d'une variable : `{{ foo.bar }}`

Vous pouvez utiliser des variables de différentes portées : [`site`](#site), [`page`](#page), [`cecil`](#cecil).

### site

La variable `site` contient des variables intégrées **et** celles définies dans la [configuration](4-Configuration.md).

| Variables | Descriptif |
| --------------------- | ------------------------------------------------------------ |
| `site.pages` | Collection de toutes les pages, dans la langue actuelle.            |
| `site.allpages` | Collection de toutes les pages, dans toutes les langues.                   |
| `site.page(id)` | Une page avec l'ID donné.                                    |
| `site.taxonomies` | Recueil de vocabulaires.                                  |
| `site.home` | ID de la page d'accueil.                                         |
| `site.time` | Actuel [_Timestamp_](https://wikipedia.org/wiki/Unix_time). |
| `site.debug` | État du mode débogage (`true` ou `false`).                       |
| `site.build` | ID de build actuel.                                            |

_Exemple:_

```yaml
title: "My amazing website!"
```

Peut être affiché dans un modèle avec :

```twig
{{ site.title }}
```

:::important
Utilisez la méthode `showable` sur la collection de pages pour renvoyer uniquement les pages publiées et non les pages _virtuelles/redirectes/exclues_.

_Exemple:_

```twig
{% for page in site.pages.showable %}
  <a href="{{ url(page) }}">{{ page.title }}</a>
{% endfor %}
```

:::

:::warning
Dans certains cas, vous pouvez rencontrer des conflits entre la configuration et les variables intégrées (ex. : `pages.default` configuration), vous pouvez donc utiliser `config.<variable>` (avec `<variable>` est le nom/chemin de la variable) pour accéder directement à la configuration brute.

Exemple:

```twig
{{ config.pages.default.sitemap.priority }}
```

:::

#### site.menus

Bouclez sur `site.menus.<menu>` pour obtenir chaque entrée de la collection `<menu>` (par exemple : `main`).

| Variables | Descriptif |
| ---------------- | ------------------------------------------- |
| `<entry>.name` | Nom de l'entrée.                                 |
| `<entry>.url` | URL d'entrée.                                  |
| `<entry>.weight` | Poids d'entrée (utile pour trier les entrées de menu). |

_Exemple:_

```twig
<nav>
  <ol>
  {% for entry in site.menus.main|sort_by_weight %}
    <li><a href="{{ url(entry.url) }}" data-weight="{{ entry.weight }}">{{ entry.name }}</a></li>
  {% endfor %}
  </ol>
</nav>
```

#### site.language

Informations sur la langue actuelle.

| Variables | Descriptif |
| ---------------------- | ------------------------------------------------------------ |
| `site.language` | Code de langue (ex. : `en`).                                  |
| `site.language.name` | Nom de la langue (par exemple : `English`).                             |
| `site.language.locale` | Langue [code local](configuration/locale-codes.md) (par exemple : `en_EN`). |
| `site.language.weight` | Position de la langue dans la liste `languages`.                   |

:::tip
Vous pouvez récupérer `name`, `locale` et `weight` d'un langage spécifique en passant son code en paramètre.
par exemple : `site.language.name('fr')`.
:::

#### site.static

La collection de fichiers statiques est accessible via `site.static` si le [_static load_](4-configuration.md#static-load) est activé.

Chaque fichier expose les propriétés suivantes :

- `path` : chemin relatif (ex. : `/images/img-1.jpg`)
- `date` : date de création (_timestamp_)
- `updated` : date de modification (_timestamp_)
- `name` : nom (ex. : `img-1.jpg`)
- `basename` : nom sans extension (ex. : `img-1`)
- `ext` : poste (ex. : `jpg`)
- `type` : type de média (ex. : `image`)
- `subtype` : sous-type de média (ex. : `image/jpeg`)
- `exif` : données EXIF ​​​​de l'image (_array_)
- `audio` : [Mp3Info](https://github.com/wapmorgan/Mp3Info#audio-information) objet
- `video` : tableau d'informations vidéo de base (durée en secondes, largeur et hauteur)

#### site.data

Une collection de données est accessible via `site.data.<filename>` (sans extension de fichier).

_Exemples :_

- `data/authors.yml` : `site.data.authors`
- `data/authors.fr.yml` : `site.data.authors` (si `site.language` = "fr")
- `data/galleries/gallery-1.json` : `site.data.galleries.gallery-1`

### page

La variable `page` contient les variables intégrées d'une page **et** celles définies dans le [avant-plan](2-Content.md#front-matter).

| Variables | Descriptif | Exemple |
| --------------------- | ------------------------------------------------------ | -------------------------- |
| `page.id` | Identifiant unique.                                     | `blog/post-1` |
| `page.title` | Nom du fichier (sans extension).                         | `Post 1` |
| `page.date` | Date de création du fichier.                                    | _DateHeure_ |
| `page.body` | Corps du fichier.                                             | _Marquage_ |
| `page.content` | Corps du fichier converti en HTML.                           | _HTML_ |
| `page.section` | Dossier racine du fichier (_slugified_).                        | `blog` |
| `page.path` | Chemin du fichier (_slugified_).                               | `blog/post-1` |
| `page.slug` | Nom du fichier (_slugified_).                               | `post-1` |
| `page.filepath` | Chemin du système de fichiers.                                      | `Blog/Post 1.md` |
| `page.type` | `homepage`, `page`, `section`, `vocabulary` ou `term`. | `page` |
| `page.pages` | Collection de toutes les sous-pages.                           | _Collection_ |
| `page.translations` | Collection de pages traduites.                        | _Collection_ |

:::important
Utilisez la méthode `showable` sur la collection de pages pour renvoyer uniquement les pages publiées et non les pages _virtuelles/redirectes/exclues_.

_Exemple:_

```twig
{% for page in page.pages.showable %}
  <a href="{{ url(page) }}">{{ page.title }}</a>
{% endfor %}
```

:::

#### page.<prev/next>

Navigation entre les pages d'une même _Section_.

| Variables | Descriptif | Exemple |
| --------------------- | ------------------------------------------------------ | -------------------------- |
| `page.prev` | Page précédente.                                         | _Page_ |
| `page.next` | Page suivante.                                             | _Page_ |

_Exemple:_

```twig
<a href="{{ url(page.prev) }}">{{ page.prev.title }}</a>
```

#### page.paginator

_Paginator_ vous aide à créer une navigation pour les pages de la liste : page d'accueil, sections et taxonomies.

| Variables | Descriptif |
| ---------------------------- | ----------------------------------- |
| `page.paginator.pages` | Collection de pages.                   |
| `page.paginator.pages_total` | Nombre total de pages.              |
| `page.paginator.count` | Nombre de pages du paginateur.        |
| `page.paginator.current` | Index de position de la page actuelle. |
| `page.paginator.links.first` | ID de page de la première page.          |
| `page.paginator.links.prev` | ID de page de la page précédente.       |
| `page.paginator.links.self` | ID de page de la page actuelle.        |
| `page.paginator.links.next` | ID de page de la page suivante.           |
| `page.paginator.links.last` | ID de page de la dernière page.           |
| `page.paginator.links.path` | ID de page sans l'index de position. |

:::important
Étant donné que les entrées de liens sont des ID de page, vous devez utiliser la fonction `url()` pour créer des liens fonctionnels.
par exemple : `{{ url(page.paginator.links.next) }}`
:::

_Exemple:_

```twig
{% if page.paginator %}
<div>
  {% if page.paginator.links.prev is defined %}
  <a href="{{ url(page.paginator.links.prev) }}">Previous</a>
  {% endif %}
  {% if page.paginator.links.next is defined %}
  <a href="{{ url(page.paginator.links.next) }}">Next</a>
  {% endif %}
</div>
{% endif %}
```

_Exemple:_

```twig
{% if page.paginator %}
<div>
  {% for paginator_index in 1..page.paginator.count %}
    {% if paginator_index != page.paginator.current %}
      {% if paginator_index == 1 %}
  <a href="{{ url(page.paginator.links.first) }}">{{ paginator_index }}</a>
      {% else %}
  <a href="{{ url(page.paginator.links.path ~ '/' ~ paginator_index) }}">{{ paginator_index }}</a>
      {% endif %}
    {% else %}
  {{ paginator_index }}
    {% endif %}
  {% endfor %}
</div>
{% endif %}
```

#### Taxonomy

Variables disponibles dans les templates _vocabulary_ et _term_.

##### Vocabulary

| Variables | Descriptif |
| --------------- | --------------------------------- |
| `page.plural` | Nom de vocabulaire au pluriel.   |
| `page.singular` | Nom de vocabulaire au singulier. |
| `page.terms` | Liste de termes (_Collection_).     |

##### Term

| Variables | Descriptif |
| ------------ | ------------------------------------------ |
| `page.term` | Identifiant du terme.                                   |
| `page.pages` | Liste des pages dans ce terme (_Collection_). |

### cecil

| Variables | Descriptif |
| ----------------- | ---------------------------------------------------- |
| `cecil.url` | URL du site Cecil.                            |
| `cecil.version` | Cécile version actuelle.                               |
| `cecil.poweredby` | Imprimez `Cecil v%s`, avec `%s` est la version actuelle. |

## Functions

> [Functions](https://twig.symfony.com/doc/functions/index.html) peut être appelée pour générer du contenu. Les fonctions sont appelées par leur nom suivi de parenthèses (`()`) et peuvent avoir des arguments.

### url

Crée une URL valide pour une page, une entrée de menu, un actif, un ID de page ou un chemin.

```twig
{{ url(value, {options}) }}
```

| Options | Descriptif | Tapez | Par défaut |
| --------- | -------------------------------------------------------------------------- | ------- | ------- |
| canonique | Préfixez l'URL avec [`baseurl`](4-Configuration.md#baseurl) ou utilisez [`canonical.url`](4-Configuration.md#metatags-options) s'il existe. | booléen | `false` |
| formats | Définit la page [format de sortie](4-Configuration.md#output-formats) (par exemple : `json`).   | chaîne | `html` |
| langue | Définit la page [langue](4-Configuration.md#language) (ex. : `fr`). | chaîne | nul |

_Exemples :_

```twig
{# page #}
{{ url(page) }}
{{ url(page, {canonical: true}) }}
{{ url(page, {format: json}) }}
{{ url(page, {language: fr}) }}
{# menu entry #}
{{ url(site.menus.main.about) }}
{# asset #}
{{ url(asset('styles.css')) }}
{# page ID #}
{{ url('page-id') }}
{# path #}
{{ url('about-me/') }}
{{ url('tags/' ~ tag) }}
```

:::info
Pour plus de commodité, la fonction `url` est également disponible sous forme de filtre :

```twig
{# page #}
{{ page|url }}
{{ page|url({canonical: true, format: json, language: fr}) }}
{# asset #}
{{ asset('styles.css')|url }}
```

:::

### asset

Un actif est une ressource utilisable dans des templates, comme CSS, JavaScript, image, audio, vidéo, etc.

La fonction `asset()` crée un objet _asset_ à partir d'un chemin de fichier, d'un tableau de chemins de fichiers (bundle) ou d'une URL (fichier distant), et est traité (minifié, empreinte digitale, etc.) selon la [configuration](4-Configuration.md#assets).

Les fichiers de ressources doivent être stockés dans le répertoire `assets/` (ou `static/`).

```twig
{{ asset(path, {options}) }}
```

| Options | Descriptif | Tapez | Par défaut |
| -------------- | ---------------------------------------------------------------------------------------- | ------- | ---------------------------- |
| nom de fichier | Enregistrez le bundle sous un nom de fichier personnalisé.                                                       | chaîne | `styles.css` ou `scripts.js` |
| ignore_missing | N'arrêtez pas la construction si le fichier n'est pas trouvé.                                                  | booléen | `false` |
| empreinte digitale | Ajoutez un hachage de contenu au nom du fichier.                                                       | booléen | `true` |
| réduire | Compressez CSS ou JavaScript.                                                              | booléen | `true` |
| optimiser | Compresser l'image.                                                                          | booléen | `false` |
| repli | Chargez un actif local si le fichier distant est introuvable.                                          | chaîne | `` |
| agent utilisateur | Clé de l'agent utilisateur (Voir [Configuration des actifs](4-Configuration.md#assets-remote-useragent)). | chaîne | `default` |

:::tip
Vous pouvez utiliser [filters](#filters) pour manipuler les actifs.
:::

:::info
Vous n'avez pas besoin de vider le [cache](#cache) après avoir modifié un actif : le cache est automatiquement vidé lorsque le fichier est modifié ou lorsque le nom du fichier est changé.
:::

_Exemples :_

```twig
{# CSS #}
{{ asset('styles.css') }}
{# CSS bundle #}
{{ asset(['poole.css', 'hyde.css'], {filename: styles.css}) }}
{# JavaScript #}
{{ asset('scripts.js') }}
{# image #}
{{ asset('image.jpeg') }}
{# audio #}
{{ asset('audio.mp3') }}
{# video #}
{{ asset('video.mp4') }}
{# remote file #}
{{ asset('https://cdnjs.cloudflare.com/ajax/libs/anchor-js/4.3.1/anchor.min.js', {minify: false}) }}
{# with filter #}
{{ asset('styles.css')|minify }}
{{ asset('styles.scss')|to_css|minify }}
```

#### Asset attributes

Les ressources créées avec la fonction `asset()` exposent certains attributs utiles.

Commun:

- `file` : chemin du système de fichiers
- `missing` : `true` si le fichier n'est pas trouvé mais que le fichier manquant est autorisé
- `path` : chemin public
- `ext` : extension de fichier
- `type` : type de média (ex. : `image`)
- `subtype` : sous-type de média (ex. : `image/jpeg`)
- `size` : taille en octets
- `content` : contenu du fichier
- `hash` : hachage du contenu du fichier (md5)
- `dataurl` : URL de données encodées en Base64
- `integrity` : hachage d'intégrité

Télécommande:

- `url` : URL du fichier distant

Paquet:

- `files` : tableau du chemin du système de fichiers en cas de bundle

Image:

- `width` : largeur de l'image en pixels
- `height` : hauteur de l'image en pixels
- `exif` : données EXIF ​​de l'image sous forme de tableau

Audio :

- `duration` : durée en secondes.microsecondes
- `bitrate` : débit en bps
- `channel` : 'stéréo', 'dual_mono', 'joint_stereo' ou 'mono'

Vidéo:

- `duration` : durée en secondes
- `width` : largeur en pixels
- `height` : hauteur en pixels

_Exemples :_

```twig
{# image width in pixels #}
{{ asset('image.png').width }}px
{# photo's date in seconds #}
{{ asset('photo.jpeg').exif.EXIF.DateTimeOriginal|date('U') }}
{# audio duration in seconds #}
{{ asset('song.mp3').duration|round }} s
{# video duration in seconds #}
{{ asset('movie.mp4').duration|round }} s
{# file integrity hash #}
{% set integrity = asset('styles.scss').integrity %}
```

### integrity

Crée le hachage (`sha384`) d'un fichier (à partir d'un actif ou d'un chemin).

```twig
{{ integrity(asset) }}
```

Utilisé pour SRI ([Intégrité des sous-ressources](https://developer.mozilla.org/fr/docs/Web/Security/Subresource_Integrity)).

_Exemple:_

```twig
{{ integrity('styles.css') }}
{# sha384-oGDH3qCjzMm/vI+jF4U5kdQW0eAydL8ZqXjHaLLGduOsvhPRED9v3el/sbiLa/9g #}
```

### html

Crée un élément HTML à partir d'un actif (ou d'un tableau d'actifs avec des attributs personnalisés).

```twig
{{ html(asset, {attributes}, {options}) }}
{# dedicated functions for each common type of asset #}
{{ css(asset) }}
{{ js(asset) }}
{{ image(asset) }}
{{ audio(asset) }}
{{ video(asset) }}
```

| Options | Descriptif | Tapez |
| ---------- | ----------------------------------------------- | ----- |
| attributs | Ajoute le couple `name="value"` à l'élément HTML. | tableau |
| options | `{preload: boolean}` : préchargements.<br>Pour les images :<br>`{formats: array}` : ajoute des formats alternatifs.<br>`{responsive: bool|string}` : ajoute des images réactives (basées sur `width` ou des pixels `density`). | tableau |

:::warning
Depuis la version ++8.42.0++, la fonction `html` remplace le filtre `html` obsolète.
:::

:::tip
Vous pouvez définir un comportement global par défaut des options d'images (`formats` et `responsive`) via la [configuration des layouts](4-Configuration.md#layouts-images).
:::

_Exemples :_

```twig
{# CSS with an attribute #}
{{ html(asset('print.css'), {media: 'print'}) }}
{# CSS with an attribute and an option #}
{{ html(asset('styles.css'), {title: 'Main theme'}, {preload: true}) }}
{# Array of assets with media query #}
{{ html([
  {asset: asset('css/style.css')},
  {asset: asset('css/style-dark.css'), attributes: {media: '(prefers-color-scheme: dark)'}}
]) }}
{# JavaScript #}
{{ html(asset('script.js')) }}
{# image without specific attributes nor options #}
{{ html(asset('image.png')) }}
{# image with specific attributes, responsive images and alternative formats #}
{{ html(asset('image.jpg'), {alt: 'Description', loading: 'lazy'}, {responsive: true, formats: ['avif', 'webp']}) }}
{# image with responsive pixels density images #}
{{ html(asset('image.jpg'), options={responsive: 'density'}, attributes={width: 256}) }}
{# Audio #}
{{ html(asset('audio.mp3')) }}
{# Video #}
{{ html(asset('video.mp4')) }}
```

:::info
Pour plus de commodité, la fonction `html` reste disponible en tant que filtre (mais est considérée comme obsolète) :

```twig
{{ asset|html({attributes}, {options}) }}
```

:::

### image_srcset

Construit l'attribut HTML img `srcset` (responsive) d'un élément d'image.

```twig
{{ image_srcset(asset) }}
```

_Exemples :_

```twig
{% set asset = asset(image_path) %}
<img src="{{ url(asset) }}" width="{{ asset.width }}" height="{{ asset.height }}" alt="" class="asset" srcset="{{ image_srcset(asset) }}" sizes="{{ image_sizes('asset') }}">
```

### image_sizes

Renvoie l'attribut HTML img `sizes` basé sur un nom de classe CSS.
Il doit être utilisé conjointement avec la fonction [`image_srcset`](3-Templates.md#image-srcset).

```twig
{{ image_sizes('class') }}
```

_Exemples :_

```twig
{% set asset = asset(image_path) %}
<img src="{{ url(asset) }}" width="{{ asset.width }}" height="{{ asset.height }}" alt="" class="asset" srcset="{{ image_srcset(asset) }}" sizes="{{ image_sizes('asset') }}">
```

### image_from_website

Construit l'élément HTML img à partir d'une URL de site Web en extrayant l'image des balises méta.

```twig
{{ image_from_website('url') }}
```

_Exemples :_

```twig
{{ image_from_website('https://example.com/page-with-image.html') }}
```

### readtime

Détermine le temps de lecture d'un texte, en minutes.

```twig
{{ readtime(value) }}
```

_Exemple:_

```twig
{{ readtime(page.content) }} min
```

### hash

Calcule le hachage d'un objet, d'un tableau ou d'une chaîne avec un algorithme donné.

```twig
{{ hash(value, algorithm) }}
```

`algorithm` peut être n'importe quel algorithme pris en charge par la fonction `hash()` de PHP (par exemple : `md5`, `sha256`, etc.). La valeur par défaut est `xxh128`.

_Exemple:_

```twig
{{ hash('my string', 'sha256') }}
```

### cache_key

Calcule une clé de cache pour [_fragments_ cache](#fragments-cache) en fonction d'un nom et d'une valeur facultative.

```twig
{% cache cache_key(name, value) %}
  {# cacheable content #}
{% endcache %}
```

La fonction ajoute un hachage de la valeur (peut être une chaîne, un tableau ou un objet) au nom (ainsi que la langue actuelle et l'ID de build pour être sûr que la clé de cache générée est unique), donc si la valeur est modifiée, la clé de cache est également modifiée et le cache est automatiquement vidé.

### getenv

Obtient la valeur d'une variable d'environnement à partir de sa clé.

```twig
{{ getenv(var) }}
```

_Exemple:_

```twig
{{ getenv('VAR') }}
```

### dump

La fonction `dump` affiche les informations sur une variable de modèle. Ceci est surtout utile pour déboguer un modèle qui ne se comporte pas comme prévu en introspectant ses variables :

```twig
{{ dump(user) }}
```

:::important
Le [_debug mode_](4-Configuration.md#debug) doit être activé.
:::

### d

La fonction `d()` est la version HTML de [`dump()`](#dump) et utilise le [Symfony VarDumper Component](https://symfony.com/doc/5.4/components/var_dumper.html) en arrière-plan.

```twig
{{ d(variable, {theme: light}) }}
```

- Si _variable_ n'est pas fourni, la fonction renvoie le contexte Twig actuel
- Les thèmes disponibles sont « clair » (par défaut) et « sombre »

:::important
Le [_debug mode_](4-Configuration.md#debug) doit être activé.
:::

## Sorts

Tri des collections (de pages, menus ou taxonomies).

### sort_by_title

Trie une collection par titre (avec [tri naturel](https://en.wikipedia.org/wiki/Natural_sort_order)).

```twig
{{ collection|sort_by_title }}
```

_Exemple:_

```twig
{{ site.pages|sort_by_title }}
```

### sort_by_date

Trie une collection par date (la plus récente en premier).

```twig
{{ collection|sort_by_date(variable='date', desc_title=false) }}
```

_Exemple:_

```twig
{# sort by date #}
{{ site.pages|sort_by_date }}
{# sort by updated variable instead of date #}
{{ site.pages|sort_by_date(variable='updated') }}
{# sort items with the same date by desc title #}
{{ site.pages|sort_by_date(desc_title=true) }}
{# reverse sort #}
{{ site.pages|sort_by_date|reverse }}
```

### sort_by_weight

Trie une collection par poids (le plus léger en premier).

```twig
{{ collection|sort_by_weight }}
```

_Exemple:_

```twig
{{ site.menus.main|sort_by_weight }}
```

### sort

Pour les cas plus complexes, vous devez utiliser [le `sort`](https://twig.symfony.com/doc/filters/sort.html) natif de Twig.

_Exemple:_

```twig
{% set files = site.static|sort((a, b) => a.date|date('U') < b.date|date('U')) %}
```

## Filters

Les variables peuvent être modifiées par [filters](https://twig.symfony.com/doc/filters/index.html). Les filtres sont séparés de la variable par un symbole de barre verticale (`|`). Plusieurs filtres peuvent être chaînés. La sortie d’un filtre est appliquée au suivant.

```twig
{{ page.title|truncate(25)|capitalize }}
```

### filter_by

Filtre une collection de pages par nom/valeur de variable.

```twig
{{ collection|filter_by(variable, value) }}
```

_Exemple:_

```twig
{{ pages|filter_by('section', 'blog') }}
```

### filter

Pour les cas plus complexes, vous devez utiliser [le `filter`](https://twig.symfony.com/doc/filters/filter.html) natif de Twig.

_Exemple:_

```twig
{% pages|filter(p => p.virtual == false and p.id not in ['page-1', 'page-2']) %}
```

### markdown_to_html

Convertit une chaîne Markdown en HTML.

```twig
{{ markdown|markdown_to_html }}
```

```twig
{% apply markdown_to_html %}
{# Markdown here #}
{% endapply %}
```

_Exemples :_

```twig
{% set markdown = '**This is bold text**' %}
{{ markdown|markdown_to_html }}
```

```twig
{% apply markdown_to_html %}
**This is bold text**
{% endapply %}
```

### toc

Extrait uniquement les en-têtes correspondant au `selectors` donné (h2, h3, etc.), ou à ceux définis dans la configuration `pages.body.toc` s'ils ne sont pas spécifiés.
Le paramètre `format` définit le format de sortie : `html` ou `json`.
Le paramètre `url` est utilisé pour créer des liens vers des titres.

```twig
{{ markdown|toc(format, selectors, url) }}
```

_Exemples :_

```twig
{{ page.body|toc }}
{{ page.body|toc('html') }}
{{ page.body|toc(selectors=['h2']) }}
{{ page.body|toc(url=url(page)) }}
```

### json_decode

Convertit une chaîne JSON en tableau.

```twig
{{ json|json_decode }}
```

_Exemple:_

```twig
{% set json = '{"foo": "bar"}' %}
{% set array = json|json_decode %}
{{ array.foo }}
```

### yaml_parse

Convertit une chaîne YAML en tableau.

```twig
{{ yaml|yaml_parse }}
```

_Exemple:_

```twig
{% set yaml = 'key: value' %}
{% set array = yaml|yaml_parse %}
{{ array.key }}
```

### slugify

Convertit une chaîne en slug.

```twig
{{ string|slugify }}
```

### u

Le filtre `u` enveloppe un texte dans un objet Unicode (une [instance Symfony UnicodeString](https://symfony.com/doc/current/components/string.html)) qui expose des méthodes pour « manipuler » la chaîne.

_Exemple:_

```twig
{{ 'cecil_string with twig'|u.camel.title }}
```

> CecilStringAvecTwig

### singular

Le filtre `singular` transforme un nom donné au pluriel en sa version singulière.

```twig
{{ string|singular(locale)}}
```

_Exemple:_

```twig
{# English (en) rules are used by default #}
{{ 'partitions'|singular }}
```

> partition

```twig
{{ 'partitions'|singular('fr') }}
```

> partition

### plural

Le filtre `plural` transforme un nom donné au singulier en sa version plurielle.

```twig
{{ string|plural(locale)}}
```

_Exemple:_

```twig
{# English (en) rules are used by default #}
{{ 'animal'|plural }}
```

> animaux

```twig
{{ 'animal'|plural('fr') }}
```

> animaux

### excerpt

Tronque une chaîne et ajoute un suffixe.

```twig
{{ string|excerpt(length, suffix) }}
```

| Options | Descriptif | Tapez | Par défaut |
| ------ | ------------------------------------------ | ------- | ------- |
| longueur | Tronque après ce nombre de caractères. | entier | 450 |
| suffixe | Ajoute des caractères.                        | chaîne | `…` |

_Exemples :_

```twig
{{ variable|excerpt }}
{{ variable|excerpt(250, '...') }}
```

### excerpt_html

Lit les caractères avant ou après la balise `<!-- excerpt -->` ou `<!-- break -->`.
Voir [Documentation de contenu](2-Content.md#excerpt) pour plus de détails.

```twig
{{ string|excerpt_html({separator, capture}) }}
```

| Options | Descriptif | Tapez | Par défaut |
| --------- | ----------------------------------------------------- | ------- | --------------- |
| séparateur | Chaîne à utiliser comme séparateur.                           | chaîne | `excerpt|break` |
| capturer | Pièce à capturer, `before` ou `after` le séparateur.   | chaîne | `before` |

_Exemples :_

```twig
{{ variable|excerpt_html }}
{{ variable|excerpt_html({separator: 'excerpt|break', capture: 'before'}) }}
{{ variable|excerpt_html({capture: 'after'}) }}
```

### highlight

Met en surbrillance une chaîne de code avec [highlight.php](https://github.com/scrivo/highlight.php).

```twig
{{ code|highlight(language) }}
```

_Exemples :_

```twig
{{ '<?php echo $highlighted->value; ?>'|highlight('php') }}
```

### to_css

Compile un fichier [Sass](https://sass-lang.com) en CSS.

```twig
{{ asset(path)|to_css }}
{{ path|to_css }}
```

_Exemples :_

```twig
{{ asset('styles.scss')|to_css }}
```

### fingerprint

Ajoutez l'empreinte digitale du contenu du fichier au nom du fichier.

```twig
{{ asset(path)|fingerprint }}
{{ path|fingerprint }}
```

_Exemples :_

```twig
{{ asset('styles.css')|fingerprint }}
```

### minify

Réduire un fichier CSS ou JavaScript.

```twig
{{ asset(path)|minify }}
```

_Exemples :_

```twig
{{ asset('styles.css')|minify }}
{{ asset('scripts.js')|minify }}
```

### minify_css

Réduire une chaîne CSS.

```twig
{{ variable|minify_css }}
```

```twig
{% apply minify_css %}
{# CSS here #}
{% endapply %}
```

_Exemples :_

```twig
{% set styles = 'some CSS here' %}
{{ styles|minify_css }}
```

```twig
<style>
{% apply minify_css %}
  html {
    background-color: #fcfcfc;
    color: #444;
  }
{% endapply %}
</style>
```

### minify_js

Réduire une chaîne JavaScript.

```twig
{{ variable|minify_js }}
```

```twig
{% apply minify_js %}
{# JavaScript here #}
{% endapply %}
```

_Exemples :_

```twig
{% set script = 'some JavaScript here' %}
{{ script|minify_js }}
```

```twig
<script>
{% apply minify_js %}
  var test = 'test';
  console.log(test);
{% endapply %}
</script>
```

### scss_to_css

Compile une chaîne [Sass](https://sass-lang.com) en CSS.

```twig
{{ variable|scss_to_css }}
```

```twig
{% apply scss_to_css %}
{# SCSS here #}
{% endapply %}
```

Alias : `sass_to_css`.

_Exemples :_

```twig
{% set scss = 'some SCSS here' %}
{{ scss|scss_to_css }}
```

```twig
<style>
{% apply scss_to_css %}
  $color: #fcfcfc;
  div {
    color: lighten($color, 20%);
  }
{% endapply %}
</style>
```

### resize

Redimensionne une image à une largeur (en pixels) ou/et une hauteur (en pixels) spécifiée.

- Si seule la largeur est spécifiée, la hauteur est calculée pour conserver le rapport hauteur/largeur
- Si seule la hauteur est spécifiée, la largeur est calculée pour conserver le rapport hauteur/largeur
- Si la largeur et la hauteur sont spécifiées, l'image est redimensionnée pour s'adapter aux dimensions données, l'image est recadrée et centrée si nécessaire
- Si Remove_animation est vrai, toute animation dans l'image (par exemple, GIF) sera supprimée

```twig
{{ asset(image_path)|resize(width: width, height: height, remove_animation: bool) }}
```

:::info
Le fichier original n'est pas modifié et la version redimensionnée est enregistrée sous `/thumbnails/<width>x<height>/image.jpg`.
:::

_Exemples :_

```twig
{{ asset(page.image)|resize(300) }}
{# equivalent to: #}
{{ asset(page.image)|resize(width: 300) }}
{# resizes to 300px width, height auto-calculated to preserve aspect ratio #}
{{ asset(page.image)|resize(height: 200) }}
{# resizes to 300px width and 200px height, and crops if necessary #}
{{ asset(page.image)|resize(300, 200) }}
{# removes any animation from the image #}
{{ asset(page.image)|resize(width: 1200, height: 630, remove_animation: true) }}
```

### cover

Redimensionne une image à une largeur et une hauteur spécifiées, en la recadrant si nécessaire.

:::warning
Le filtre `cover` est obsolète depuis la version ++8.77++ et sera supprimé dans les versions futures. Utilisez plutôt le filtre [`resize`](#resize), avec les paramètres de largeur et de hauteur.
:::

```twig
{{ asset(image_path)|cover(width, height) }}
```

_Exemple:_

```twig
{{ asset(page.image)|cover(1200, 630) }}
```

### maskable

Ajoute un remplissage, en pourcentages, à une image pour la rendre masquable.

```twig
{{ asset(image_path)|maskable(padding) }}
```

_Exemple:_

```twig
{{ asset('icon.png')|maskable }}
```

### webp

Convertit une image au format [WebP](https://developers.google.com/speed/webp).

_Exemple:_

```twig
<picture>
    <source type="image/webp" srcset="{{ asset(image_path)|webp }}">
    <img src="{{ url(asset(image_path)) }}" width="{{ asset(image_path).width }}" height="{{ asset(image_path).height }}" alt="">
</picture>
```

### avif

Convertit une image au format [AVIF](https://github.com/AOMediaCodec/libavif).

_Exemple:_

```twig
<picture>
    <source type="image/avif" srcset="{{ asset(image_path)|avif }}">
    <img src="{{ url(asset(image_path)) }}" width="{{ asset(image_path).width }}" height="{{ asset(image_path).height }}" alt="">
</picture>
```

### dataurl

Renvoie l'[URL de données](https://developer.mozilla.org/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) d'un actif.

```twig
{{ asset(path)|dataurl }}
{{ asset(image_path)|dataurl }}
```

### lqip

Renvoie un [espace réservé pour une image de faible qualité](https://www.guypo.com/introducing-lqip-low-quality-image-placeholders) (100 x 100 px, flou à 50 %) comme URL de données.

```twig
{{ asset(image_path)|lqip }}
```

### dominant_color

Renvoie la [couleur hexadécimale](https://developer.mozilla.org/en-US/docs/Web/CSS/hex-color) dominante d'une image.

```twig
{{ asset(image_path)|dominant_color }}
```

### inline

Affiche le contenu d'un _Asset_.

```twig
{{ asset(path)|inline }}
```

_Exemple:_

```twig
{{ asset('styles.css')|inline }}
```

### preg_split

Divise une chaîne en un tableau à l'aide d'une expression régulière.

```twig
{{ string|preg_split(pattern, limit) }}
```

_Exemple:_

```twig
{% set headers = page.content|preg_split('/<br[^>]*>/') %}
```

### preg_match_all

Effectue une correspondance d'expression régulière et renvoie le groupe pour toutes les correspondances.

```twig
{{ string|preg_match_all(pattern, group) }}
```

_Exemple:_

```twig
{% set tags = page.content|preg_match_all('/<[^>]+>(.*)<\/[^>]+>/') %}
```

### hex_to_rgb

Convertit une couleur hexadécimale en RVB.

```twig
{{ color|hex_to_rgb }}
```

## Localization

Cecil prend en charge [traduction du texte](#text-translation) et [localisation de la date](#date-localization).

### Text translation

Utilise le `trans` _tag_ ou _filter_ pour traduire des textes dans des templates.

```twig
{% trans with variables into locale %}{% endtrans %}
```

```twig
{{ message|trans(variables = []) }}
```

#### Examples

```twig
{% trans %}Hello World!{% endtrans %}
```

```twig
{{ message|trans }}
```

Inclure des variables :

```twig
{% trans with {'%name%': 'Arnaud'} %}Hello %name%!{% endtrans %}
```

```twig
{{ message|trans({'%name%': 'Arnaud'}) }}
```

Forcer les paramètres régionaux :

```twig
{% trans into 'fr_FR' %}Hello World!{% endtrans %}
```

Pluraliser :

```twig
{% trans with {'%count%': 42}%}{0}I don't have apples|{1}I have one apple|]1,Inf[I have %count% apples{% endtrans %}
```

### Translation files

Les fichiers de traduction doivent être nommés `messages.<locale>.<format>` et stockés dans le répertoire [`translations`](4-Configuration.md#layouts).
Cecil prend en charge les fichiers `yaml` et `mo` (Gettext) [formats par défaut](4-Configuration.md#layouts).

Le code locale (ex. : `fr_FR`) d'une langue est défini dans les entrées [`languages`](4-Configuration.md#languages) de la configuration.

_Exemple:_

```plaintext
<mywebsite>
└─ translations
   ├─ messages.fr_FR.mo   <- Machine Object format
   └─ messages.fr_FR.yaml <- Yaml format
```

:::info
Vous pouvez facilement extraire les traductions de vos templates avec la commande suivante :

```bash
php cecil.phar util:translations:extract
```

:::

:::tip
[_Poedit_](https://poedit.net) est un éditeur de traduction simple et multiplateforme pour gettext (PO), et [_Poedit Pro_](https://poedit.net/pro) prend en charge l'extraction de chaînes de traduction à partir de templates prêts à l'emploi.
:::

:::important
Faites attention au [cache](#cache) lorsque vous mettez à jour les fichiers de traduction.

Le cache peut être vidé avec la commande suivante :

```bash
php cecil.phar cache:clear:translations`
```

:::

### Date localization

Utilise le filtre Twig [`format_date`](https://twig.symfony.com/doc/3.x/filters/format_date.html) pour localiser une date dans les templates.

```twig
{{ page.date|format_date('long') }}
{# September 30, 2022 #}
```

Les valeurs prises en charge sont : `short`, `medium`, `long` et `full`.

:::important
Si vous souhaitez utiliser le filtre `format_date` **avec des paramètres régionaux autres que "en"**, vous devez [installer l'extension PHP internationale](https://php.net/intl.setup).
:::

## Components

Cecil fournit une logique de composants pour vous donner le pouvoir de créer des "unités" de templates réutilisables.

:::info
La fonctionnalité des composants est fournie par l'[_extension de composants Twig_](https://github.com/giorgiopogliani/twig-components) créée par Giorgio Pogliani.
:::

### Components syntax

Les composants ne sont que des templates Twig stockés dans le sous-répertoire `components/` et peuvent être utilisés n'importe où dans vos templates :

```twig
{# /components/button.twig #}
<button {{ attributes.merge({class: 'rounded px-4'}) }}>
    {{ slot }}
</button>
```

> La variable slot correspond à tout contenu que vous ajouterez entre la balise d'ouverture et la balise de fermeture.

Pour accéder à un composant vous devez utiliser la balise dédiée `x` suivie de `:` et du nom de fichier de votre composant sans extension :

```twig
{# /index.twig #}
{% x:button with {class: 'text-white'} %}
    <strong>Click me</strong>
{% endx %}
```

Il rendra :

```twig
<button class="text-white rounded px-4">
    <strong>Click me</strong>
</button>
```

## Cache

Cecil utilise un système de cache pour accélérer le processus de génération, il peut être désactivé ou effacé.

Il existe trois types de cache dans le cas du rendu des templates : les templates eux-mêmes, [assets](#asset) et [translations](#translation-files).

### Clear cache

Vous pouvez vider le cache avec les commandes suivantes :

```bash
php cecil.phar cache:clear               # clear all caches
php cecil.phar cache:clear:assets        # clear assets cache
php cecil.phar cache:clear:templates     # clear templates cache
php cecil.phar cache:clear:translations  # clear translations cache
```

:::important
En pratique, vous n'avez pas besoin de vider le cache manuellement, Cecil le fait pour vous en cas de besoin (par exemple lorsque des fichiers changent).
:::

### Fragments cache

Cecil fournit un moyen de mettre en cache des parties du rendu des templates pour éviter de restituer plusieurs fois le même contenu partiel.

Pour utiliser le cache _fragments_, vous devez envelopper le contenu que vous souhaitez mettre en cache avec la balise [`cache`](https://twig.symfony.com/doc/tags/cache.html).

```twig
{% cache 'unique-key' %}
  {# cacheable content #}
{% endcache %}
```

:::tip
Vous devez utiliser la fonction [`cache_key`](#cache-key) pour être sûr d'avoir une clé de cache unique pour chaque contenu que vous souhaitez mettre en cache.
:::

:::warning
Le cache _Fragments_ est persistant, donc si la clé de cache est trop générique, vous risquez de vous retrouver avec un mauvais contenu affiché.
:::

Pour vider uniquement le cache des fragments, vous pouvez utiliser la commande suivante :

```bash
php cecil.phar cache:clear:templates --fragments
```

### Disable cache

Vous pouvez désactiver le cache avec la [configuration](4-Configuration.md#cache).

:::warning
La désactivation du cache peut ralentir le processus de génération, ce n'est donc pas recommandé.

Lors du développement local, si vous devez vider le cache avant chaque génération, vous pouvez utiliser l'option suivante :

```bash
php cecil.phar serve --clear-cache          # clear all caches
php cecil.phar serve --clear-cache=<regex>  # clear cache for cache key matches with the regular expression <regex>
```

Exemple:

```bash
php cecil.phar serve --clear-cache=css  # clear cache for all CSS files
```

:::

## Extend

### Functions and filters

Vous pouvez ajouter des [fonctions](3-Templates.md#functions) et des [filtres](3-Templates.md#filters) personnalisés avec une [**_extension Twig_**](7-Extend.md#twig-extension).

### Theme

C'est simple de construire un thème, il suffit de créer un dossier `<theme>` avec la structure suivante (comme un site web mais sans pages) :

```plaintext
<mywebsite>
└─ themes
   └─ <theme>
      ├─ config.yml
      ├─ assets
      ├─ layouts
      ├─ static
      └─ translations
```
