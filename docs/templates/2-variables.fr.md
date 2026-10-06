<!--
title: "Variables"
description: "Variables disponibles dans les templates : site, page et cecil."
date: 2026-05-26
updated: 2026-10-06
-->
# Variables

> L'application transmet des variables aux templates pour manipulation dans le modèle. Les variables peuvent également avoir des attributs ou des éléments auxquels vous pouvez accéder.
> Utilisez un point (.) pour accéder aux attributs d'une variable : `{{ foo.bar }}`

Vous pouvez utiliser des variables de différentes portées : [`site`](#site), [`page`](#page), [`cecil`](#cecil).

## site

La variable `site` contient des variables intégrées **et** celles définies dans la [configuration](../configuration/index.fr.md).

| Variables         | Descriptif                                                  |
| ----------------- | ----------------------------------------------------------- |
| `site.pages`      | Collection de toutes les pages, dans la langue actuelle.    |
| `site.allpages`   | Collection de toutes les pages, dans toutes les langues.    |
| `site.page(id)`   | Une page avec l'ID donné.                                   |
| `site.taxonomies` | Recueil de vocabulaires.                                    |
| `site.home`       | ID de la page d'accueil.                                    |
| `site.time`       | Actuel [_Timestamp_](https://wikipedia.org/wiki/Unix_time). |
| `site.debug`      | État du mode débogage (`true` ou `false`).                  |
| `site.build`      | ID de build actuel.                                         |

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

### site.menus

Bouclez sur `site.menus.<menu>` pour obtenir chaque entrée de la collection `<menu>` (par exemple : `main`).

| Variables        | Descriptif                                             |
| ---------------- | ------------------------------------------------------ |
| `<entry>.name`   | Nom de l'entrée.                                       |
| `<entry>.url`    | URL d'entrée.                                          |
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

### site.language

Informations sur la langue actuelle.

| Variables              | Descriptif                                                                  |
| ---------------------- | --------------------------------------------------------------------------- |
| `site.language`        | Code de langue (ex. : `en`).                                                |
| `site.language.name`   | Nom de la langue (par exemple : `English`).                                 |
| `site.language.locale` | Langue [code local](../configuration/3-locale-codes.fr.md) (par exemple : `en_US`). |
| `site.language.weight` | Position de la langue dans la liste `languages`.                            |

:::tip
Vous pouvez récupérer `name`, `locale` et `weight` d'un langage spécifique en passant son code en paramètre.
par exemple : `site.language.name('fr')`.
:::

### site.static

La collection de fichiers statiques est accessible via `site.static` si le [_static load_](../configuration/5-data-static.fr.md#static-load) est activé.

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

### site.data

Une collection de données est accessible via `site.data.<filename>` (sans extension de fichier).

_Exemples :_

- `data/authors.yml` : `site.data.authors`
- `data/authors.fr.yml` : `site.data.authors` (si `site.language` = "fr")
- `data/galleries/gallery-1.json` : `site.data.galleries['gallery-1']`

## page

La variable `page` contient les variables intégrées d'une page **et** celles définies dans le [avant-plan](../content/1-pages.fr.md#front-matter).

| Variables           | Descriptif                                             | Exemple          |
| ------------------- | ------------------------------------------------------ | ---------------- |
| `page.id`           | Identifiant unique.                                    | `blog/post-1`    |
| `page.title`        | Nom du fichier (sans extension).                       | `Post 1`         |
| `page.date`         | Date de création du fichier.                           | _DateHeure_      |
| `page.body`         | Corps du fichier.                                      | _Marquage_       |
| `page.content`      | Corps du fichier converti en HTML.                     | _HTML_           |
| `page.section`      | Dossier racine du fichier (_slugified_).               | `blog`           |
| `page.path`         | Chemin du fichier (_slugified_).                       | `blog/post-1`    |
| `page.slug`         | Nom du fichier (_slugified_).                          | `post-1`         |
| `page.filepath`     | Chemin du système de fichiers.                         | `Blog/Post 1.md` |
| `page.type`         | `homepage`, `page`, `section`, `vocabulary` ou `term`. | `page`           |
| `page.pages`        | Collection de toutes les sous-pages.                   | _Collection_     |
| `page.translations` | Collection de pages traduites.                         | _Collection_     |

:::important
Utilisez la méthode `showable` sur la collection de pages pour renvoyer uniquement les pages publiées et non les pages _virtuelles/redirectes/exclues_.

_Exemple:_

```twig
{% for page in page.pages.showable %}
  <a href="{{ url(page) }}">{{ page.title }}</a>
{% endfor %}
```

:::

### Sections imbriquées

Dans un contexte de [sections imbriquées](../content/1-pages.fr.md#sous-section), les propriétés `page.parent`, `page.ancestors`, `page.sections` et `page.toplevel` facilitent la construction de la navigation.

| Variables        | Descriptif                                                      | Exemple      |
| ---------------- | --------------------------------------------------------------- | ------------ |
| `page.parent`    | Page de la _section_ parente (`null` si aucune).                | _Page_       |
| `page.ancestors` | Collection des _sections_ ancêtres (la plus proche en premier). | _Collection_ |
| `page.sections`  | Collection des _sections_ descendantes immédiates.              | _Collection_ |
| `page.toplevel`  | `true` si la page est une _section_ de premier niveau.          | _Boolean_    |

_Fil d'Ariane (de la page d'accueil à la page courante) :_

```twig
<nav aria-label="breadcrumb">
  <ul>
    <li><a href="{{ url(site.home) }}">{{ site.title }}</a></li>
    {% for section in page.ancestors|reverse %}
    <li><a href="{{ url(section) }}">{{ section.title }}</a></li>
    {% endfor %}
    {% if page.id != site.home %}
    <li><a href="{{ url(page) }}" aria-current="page">{{ page.title }}</a></li>
    {% endif %}
  </ul>
</nav>
```

:::tip
Un partial [`breadcrumb.html.twig`](https://github.com/Cecilapp/Cecil/blob/main/resources/layouts/partials/breadcrumb.html.twig) prêt à l'emploi est disponible :

```twig
{{ include('partials/breadcrumb.html.twig') }}
```

:::

_Menu des sous-sections (sections descendantes immédiates de la section courante) :_

```twig
{% if page.sections|length %}
<ul>
  {% for section in page.sections|sort_by_title %}
  <li><a href="{{ url(section) }}">{{ section.title }}</a></li>
  {% endfor %}
</ul>
{% endif %}
```

_Navigation principale limitée aux sections de premier niveau (depuis n'importe quelle page) :_

```twig
<nav>
  {% for section in site.page(site.home).sections|sort_by_title %}
  <a href="{{ url(section) }}">{{ section.title }}</a>
  {% endfor %}
</nav>
```

_Lien vers la section parente :_

```twig
{% if page.parent %}
<a href="{{ url(page.parent) }}">← {{ page.parent.title }}</a>
{% endif %}
```

### page.<prev/next>

Navigation entre les pages d'une même _Section_, triées selon le `sortby` de la section (ordre chronologique pour les dates).

Avec des [sous-sections](../content/1-pages.fr.md#sous-section), la navigation suit l'arbre des sections : les pages d'une _Section_ de premier niveau et de toutes ses sous-sections sont enchaînées, chaque sous-section (sa page d'index) étant placée parmi les pages de sa _Section_ parente et suivie de ses propres pages.

| Variables   | Descriptif       | Exemple |
| ----------- | ---------------- | ------- |
| `page.prev` | Page précédente. | _Page_  |
| `page.next` | Page suivante.   | _Page_  |

_Exemple:_

```twig
<a href="{{ url(page.prev) }}">{{ page.prev.title }}</a>
```

### page.paginator

_Paginator_ vous aide à créer une navigation pour les pages de la liste : page d'accueil, sections et taxonomies.

| Variables                    | Descriptif                             |
| ---------------------------- | -------------------------------------- |
| `page.paginator.pages`       | Collection de pages.                   |
| `page.paginator.pages_total` | Nombre total de pages.                 |
| `page.paginator.count`       | Nombre de pages du paginateur.         |
| `page.paginator.current`     | Index de position de la page actuelle. |
| `page.paginator.links.first` | ID de page de la première page.        |
| `page.paginator.links.prev`  | ID de page de la page précédente.      |
| `page.paginator.links.self`  | ID de page de la page actuelle.        |
| `page.paginator.links.next`  | ID de page de la page suivante.        |
| `page.paginator.links.last`  | ID de page de la dernière page.        |
| `page.paginator.links.path`  | ID de page sans l'index de position.   |

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

### Taxonomie

Variables disponibles dans les templates _vocabulary_ et _term_.

#### Vocabulaire

Page `/<plural>/` (ex. : `/categories/`).

| Variables       | Descriptif                       |
| --------------- | -------------------------------- |
| `page.plural`   | Nom de vocabulaire au pluriel.   |
| `page.singular` | Nom de vocabulaire au singulier. |
| `page.terms`    | Liste de termes (_Collection_).  |

Chaque terme de `page.terms` fournit `term.id` (identifiant du terme, ex. : `categories/php`), `term.name` (nom du terme, ex. : `PHP`) et le nombre de ses pages avec `term|length`.

#### Terme

Page `/<plural>/<term>/` (ex. : `/categories/php/`).

| Variables       | Descriptif                                                       |
| --------------- | ---------------------------------------------------------------- |
| `page.title`    | Nom du terme.                                                    |
| `page.term`     | Identifiant du terme (ex. : `categories/php`).                   |
| `page.plural`   | Nom de vocabulaire au pluriel.                                   |
| `page.singular` | Nom de vocabulaire au singulier.                                 |
| `page.pages`    | Liste des pages dans ce terme, triées par date (_Collection_).   |

#### Exemple de taxonomie

Configuration :

```yaml
taxonomies:
  categories: category
```

Front matter d’une page :

```yaml
---
categories: ["Data Sovereignty"]
---
```

Liste des termes (`/categories/`), dans `layouts/taxonomy/categories.html.twig` :

```twig
{% extends 'page.html.twig' %}

{% block content %}
  <h1>{{ page.title }}</h1>
  <ul>
  {% for term in page.terms %}
    <li><a href="{{ url(term.id) }}">{{ term.name }}</a> ({{ term|length }})</li>
  {% endfor %}
  </ul>
{% endblock %}
```

Liste des pages d’un terme (`/categories/data-sovereignty/`), dans `layouts/taxonomy/category.html.twig` :

```twig
{% extends 'page.html.twig' %}

{% block content %}
  <h1>{{ page.title }}</h1>
  {% for p in page.paginator.pages ?? page.pages %}
    <article>
      <h2><a href="{{ url(p) }}">{{ p.title }}</a></h2>
    </article>
  {% endfor %}
  <a href="{{ url(page.plural) }}">Toutes les {{ page.plural }}</a>
{% endblock %}
```

Liens vers les termes de la page courante, dans un template de page :

```twig
{% for category in page.categories ?? [] %}
  <a href="{{ url('categories/' ~ category) }}">{{ category }}</a>
{% endfor %}
```

:::tip
La fonction [`url()`](reference/1-functions.fr.md#url) « slugifie » la chaîne fournie pour trouver la page correspondante : `url('categories/Data Sovereignty')` retourne `/categories/data-sovereignty/`.

Vous pouvez aussi utiliser le partial intégré `{{ include('partials/terms-list.html.twig', {vocabulary: 'categories'}) }}`.
:::

## cecil

| Variables         | Descriptif                                               |
| ----------------- | -------------------------------------------------------- |
| `cecil.url`       | URL du site Cecil.                                       |
| `cecil.version`   | Version actuelle de Cecil.                               |
| `cecil.poweredby` | Imprimez `Cecil v%s`, avec `%s` est la version actuelle. |
