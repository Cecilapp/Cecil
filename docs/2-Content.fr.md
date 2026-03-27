<!--
title: Contenu
description: "Créer du contenu et l’organiser."
date: 2026-03-27
slug: contenu
-->
# Contenu

Il existe différents types de contenu dans Cecil :

**Pages**
: Les pages constituent le contenu principal du site, rédigé en [Markdown](#markdown).
: Les pages doivent être organisées de manière à refléter le site Web généré.
: Les pages peuvent être organisées en _Sections_ (dossiers racine) (ex. : « Blog », « Projet », etc.).

**Assets**
: Les assets sont des fichiers transformés (c.-à-d. : images redimensionnées, Sass compilé, scripts minifiés, etc.) avec la fonction de template [`asset()`](3-Templates.md#asset).

**Static files**
: Les fichiers statiques sont copiés tels quels dans le site généré (ex. : `static/file.pdf` -> `file.pdf`).

**Data files**
: Les fichiers de données sont des collections de variables personnalisées, exposées dans les [templates](3-Templates.md) via [`site.data`](3-Templates.md#site-data).

## Organisation des fichiers

### Arborescence du système de fichiers

Organisation des fichiers du projet.

```plaintext
<monsiteweb>
├─ pages
|  ├─ blog            <- Section
|  |  ├─ post-1.md    <- Page dans Section
|  |  └─ post-2.md
|  ├─ projects
|  |  └─ project-a.md
|  └─ about.md        <- Page racine
├─ assets
|  ├─ styles.scss     <- Fichier d'asset
|  └─ logo.png
├─ static
|  └─ file.pdf        <- Fichier statique
└─ data
   └─ authors.yml     <- Collection de données
```

### Arborescence du site généré

Résultat de la génération.

```plaintext
<monsiteweb>
└─ _site
   ├─ index.html               <- Page d'accueil générée
   ├─ blog/
   |  ├─ index.html            <- Liste des articles générée
   |  ├─ post-1/index.html     <- Article de blog
   |  └─ post-2/index.html
   ├─ projects/
   |  ├─ index.html            <- Liste des projets générée
   |  └─ project-a/index.html  <- Projet individuel
   ├─ about/index.html         <- Page "À propos"
   ├─ styles.css
   ├─ logo.png
   └─ file.pdf
```

:::info
Par défaut, chaque page est générée sous la forme `slugified-filename/index.html` pour obtenir une « belle » URL comme `https://mywebsite.tld/section/slugified-filename/`.

Pour obtenir une URL « ugly » (comme `404.html` au lieu de `404/`), définissez `uglyurl: true` dans le [front matter](#front-matter) de la page.
:::

### Routage basé sur les fichiers

Les fichiers Markdown du répertoire `pages` activent un routage basé sur les fichiers. Cela signifie que l’ajout, par exemple, de `pages/my-projects/project-a.md` le rendra accessible à l’URL `/project-a` dans votre navigateur.

```plaintext
Fichier :
                   pages/my-projects/project-a.md
                        └───── filepath ──────┘
URL :
    ┌───── baseurl ─────┬─────── path ────────┐
     https://example.com/my-projects/project-a/index.html
                        └─ section ─┴─ slug ──┘
```

:::important
Deux types de préfixes peuvent modifier l’URL, voir la section [File prefix](#file-prefix) ci-dessous.
:::

## Pages

Une page est un fichier composé d’un [**front matter**](#front-matter) et d’un [**body**](#body).

### Front matter

Le _front matter_ est une collection de [variables](#variables) (au format _clé/valeur_) entourée par `---`.

_Exemple :_

```yaml
---
title: "The title"
date: 2019-02-21
tags: [tag 1, tag 2]
customvar: "Value of customvar"
---
```

:::info
Vous pouvez aussi utiliser `<!-- -->` ou `+++` comme séparateur.
:::

### Corps (body)

Le _body_ est le contenu principal d’une page ; il peut être écrit en [Markdown](#markdown) ou en texte brut.

_Exemple :_

```markdown
# Header

[toc]

## Sub-Header 1

Lorem ipsum dolor [sit amet](https://example.com), consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
<!-- excerpt -->
Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.

## Sub-Header 2

![Description](/image.jpg "Title")

## Sub-Header 3

:::tip
This is an advice.
:::
```

## Markdown

Cecil prend en charge le format [Markdown](http://daringfireball.net/projects/markdown/syntax), ainsi que [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/).

Cecil fournit aussi des **fonctionnalités supplémentaires** pour enrichir votre contenu, voir ci-dessous.

### Attributs

Avec [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/), vous pouvez définir un id, une classe et des attributs personnalisés sur certains éléments à l’aide d’un bloc d’attributs.  
Par exemple, placez le(s) attribut(s) souhaité(s) après un en-tête, un bloc de code délimité, un lien ou une image en fin de ligne, entre accolades, comme ceci :

```markdown
## En-tête {#id .class attribute=value}
```

:::warning
Pour un élément en ligne, comme un lien, vous devez utiliser un retour à la ligne après l’accolade fermante :

```markdown
Lorem ipsum [dolor](url){attribute=value} 
sit amet.
```

:::

### Liens

Vous pouvez créer un lien avec la syntaxe `[Texte](url)` ; `url` peut être un chemin, un chemin relatif vers un fichier Markdown, une URL externe, etc.

_Exemple :_

```markdown
[Link to a path](/about/)
[Link to a Markdown file](../about.md)
[Link to Cecil website](https://cecil.app)
```

#### Lien vers une page

Vous pouvez facilement créer un lien vers une page avec la syntaxe `[Titre de page](page:page-id)`.

_Exemple :_

```markdown
[Link to a blog post](page:blog/post-1)
```

#### Externe

Par défaut, les liens externes ont la valeur suivante pour l’attribut `rel` : `noopener noreferrer`.

_Exemple :_

```html
<a href="<url>" rel="noopener noreferrer">Link to another website</a>
```

Vous pouvez modifier ce comportement avec les [options `pages.body.links.external`](4-Configuration.md#pages-body-links).

#### Liens intégrés

Vous pouvez laisser Cecil essayer de transformer un lien en contenu embarqué en utilisant l’attribut `{embed}` ou en activant l’option de configuration globale `pages.body.links.embed.enabled` à `true`.

:::important
Seuls les liens **YouTube**, **Vimeo**, **Dailymotion** et **GitHub Gits** sont pris en charge.
:::

_Exemple :_

```markdown
[CECIL : LE générateur de SITES STATIQUES en PHP](https://www.youtube.com/watch?v=ur8koU0iYvc){embed}
```

[CECIL : LE générateur de SITES STATIQUES en PHP](https://www.youtube.com/watch?v=ur8koU0iYvc){embed}

##### Local video/audio files

Cecil peut aussi créer des éléments HTML vidéo et audio, selon l’extension du fichier.

_Exemple :_

```markdown
[Video file](video.mp4){embed controls poster=/images/video-test.png}
[Audio file](song.mp3){embed controls}
```

Est converti en :

```html
<video src="/video.mp4" controls poster="/images/video-test.png" style="max-width:100%;height:auto;"></video>
<audio src="/song.mp3" controls></audio>
```

### Images

Pour ajouter une image, utilisez un point d’exclamation (`!`) suivi d’une description alternative entre crochets (`[]`), puis du chemin ou de l’URL de l’image entre parenthèses (`()`).  
Vous pouvez facultativement ajouter un titre entre guillemets.

```markdown
![Alternative description](/image.jpg "Image title")
```

:::info
Le chemin doit être relatif à la racine de votre site Web (ex. : `/image.jpg`), mais Cecil est capable de normaliser un chemin relatif aux répertoires _assets_ et _static_ (ex. : `../../assets/image.jpg`).
:::

#### Lazy loading

Cecil ajoute l’attribut `loading="lazy"` à chaque image.

_Exemple :_

```markdown
![](/image.jpg)
```

Est converti en :

```html
<img src="/image.jpg" loading="lazy">
```

:::info
Vous pouvez désactiver ce comportement avec l’attribut `{loading=eager}` ou avec l’[option `lazy`](4-Configuration.md#pages-body-images).
:::

#### Decoding

Cecil ajoute l’attribut `decoding="async"` à chaque image.

_Exemple :_

```markdown
![](/image.jpg)
```

Est converti en :

```html
<img src="/image.jpg" decoding="async">
```

:::info
Vous pouvez désactiver ce comportement avec l’attribut `{decoding=auto}` ou avec l’[option `decoding`](4-Configuration.md#pages-body-images).
:::

#### Redimensionnement

Chaque image du _body_ peut être redimensionnée automatiquement en définissant une largeur inférieure à celle d’origine, avec l’attribut additionnel `{width=X}`.

_Exemple :_

```markdown
![](/image.jpg){width=800}
```

Est converti en :

```html
<img src="/thumbnails/800/image.jpg" width="800" height="600">
```

:::info
Le ratio est conservé (l’attribut `height` est calculé automatiquement), le fichier original n’est pas modifié et la version redimensionnée est stockée dans `/thumbnails/<width>/`.
:::

:::important
Cette fonctionnalité nécessite l’[extension GD](https://www.php.net/manual/book.image.php) (sinon elle ajoute seulement un attribut HTML `width` à la balise `img`).
:::

#### Formats

Si l’[option `formats`](4-Configuration.md#pages-body-images) est définie, des images alternatives sont créées et ajoutées.

_Exemple :_

```markdown
![](/image.jpg)
```

Peut être converti en :

```html
<picture>
  <source srcset="/image.avif" type="image/avif">
  <source srcset="/image.webp" type="image/webp">
  <img src="/image.jpg">
</picture>
```

:::important
Veuillez noter que **tous les formats d’image** ne sont pas toujours inclus dans les extensions d’image PHP.
:::

#### Responsive

Si l’[option `responsive`](4-Configuration.md#pages-body-images) est activée, alors toutes les images du _body_ seront automatiquement rendues « responsive ».

_Exemple :_

```markdown
![](/image.jpg){width=800}
```

sera converti en :

```html
<img src="/thumbnails/800/image.jpg" width="800" height="600"
  srcset="/thumbnails/320/image.jpg 320w,
          /thumbnails/640/image.jpg 640w,
          /thumbnails/800/image.jpg 800w"
  sizes="100vw"
>
```

:::info
Comme une image du body est convertie en [Asset](3-Templates.md#asset), les différentes largeurs doivent être définies dans la [configuration des assets](4-Configuration.md#assets).
:::

L’attribut `sizes` prend la valeur de l’option de configuration `assets.images.responsive.sizes.default`, mais peut être modifié en créant une nouvelle entrée nommée d’après une _class_ ajoutée à l’image.

_Exemple :_

```yaml
assets:
  images:
    responsive:
      sizes:
        default: 100vw
        my_class: "(max-width: 800px) 768px, 1024px"
```

```markdown
![](/image.jpg){.my_class}
```

:::info
Vous pouvez combiner les options `formats` et `responsive`.
:::

#### CSS class

Vous pouvez définir une valeur par défaut pour l’attribut `class` de chaque image avec l’[option `class`](4-Configuration.md#pages-body-images).

#### Caption

Le titre optionnel peut être utilisé pour créer automatiquement une légende (`figcaption`) en activant l’[option `caption`](4-Configuration.md#pages-body-images).

_Exemple :_

```markdown
![](/images/img.jpg "Title")
```

Est converti en :

```html
<figure>
  <img src="/image.jpg" title="Title">
  <figcaption>Title</figcaption>
</figure>
```

:::info
La légende prend en charge le contenu Markdown.
:::

#### Placeholder

Comme les images sont généralement des ressources plus lourdes et plus lentes, et qu’elles ne bloquent pas le rendu, il est préférable de donner aux utilisateurs quelque chose à voir pendant qu’ils attendent leur chargement.

L’attribut `placeholder` accepte 2 options :

1. `color`: affiche un fond coloré (basé sur la couleur dominante de l’image)
2. `lqip`: [Low-Quality Image Placeholder](https://www.guypo.com/introducing-lqip-low-quality-image-placeholders)

_Exemples :_

```markdown
![](/images/img.jpg){placeholder=color}
![](/images/img.jpg){placeholder=lqip}
```

:::tip
Vous pouvez définir une valeur pour l’attribut `placeholder` de chaque image avec l’[option `placeholder`](4-Configuration.md#pages-body-images).
:::

:::warning
L’option `lqip` n’est pas compatible avec les GIF animés.
:::

### Table des matières

Vous pouvez ajouter une table des matières avec la syntaxe Markdown suivante :

```markdown
[toc]
```

:::info
Par défaut, la ToC extrait les en-têtes H2 et H3. Vous pouvez modifier ce comportement avec les [options de body](4-Configuration.md#pages-body).
:::

### Extrait

Un extrait peut être défini dans le _body_ avec l’une des balises suivantes : `excerpt` ou `break`.

_Exemple :_

```html
Introduction.
<!-- excerpt -->
Main content.
```

Utilisez ensuite le filtre [`excerpt_html`](3-Templates.md#excerpt-html) dans votre template.

### Notes

Créez un bloc de _Note_ (info, astuce, important, etc.).

_Exemple :_

```markdown
:::tip
**Tip:** This is an advice.
:::
```

Est converti en :

```html
<aside class="note note-tip">
  <p>
    <strong>Tip:</strong> This is an advice.
  </p>
</aside>
```

:::tip
**Tip:** This is an advice.
:::

_Autres exemples :_

:::
empty
:::

:::info
info
:::

:::tip
tip
:::

:::important
important
:::

:::warning
warning
:::

:::caution
caution
:::

### Coloration syntaxique

Active le surligneur syntaxique des blocs de code en définissant l’option [pages.body.highlight.enabled](4-Configuration.md#pages-body-highlight) à `true`.

_Exemple :_

<pre>
```php
echo "Hello world";
```
</pre>

Est rendu en :

```php
echo "Hello world";
```

:::important
Vous devez ajouter la [StyleSheet](https://highlightjs.org/download/) dans l’en-tête de votre template.
:::

### Texte inséré

Représente une plage de texte qui a été ajoutée.

```markdown
++text++
```

Est converti en :

```html
<ins>text</ins>
```

## Variables

Le _front matter_ peut contenir des variables personnalisées appliquées à la page courante.

Il doit se trouver au tout début du fichier et être un [YAML](https://en.wikipedia.org/wiki/YAML) valide.

### Variables prédéfinies

| Variable    | Description       | Valeur par défaut                                  | Exemple       |
| ----------- | ----------------- | -------------------------------------------------- | ------------- |
| `title`     | Titre             | Nom de fichier sans extension.                     | `Post 1`      |
| `layout`    | Template          | Voir [_Lookup rules_](3-Templates.md#lookup-rules). | `404`         |
| `date`      | Date de création  | Date de création du fichier (objet PHP _DateTime_). | `2019/04/15`  |
| `section`   | Section           | _Section_ de la page.                              | `blog`        |
| `path`      | Chemin            | _Path_ de la page.                                 | `blog/post-1` |
| `slug`      | Slug              | _Slug_ de la page.                                 | `post-1`      |
| `published` | Publié ou non     | `true`.                                            | `false`       |
| `draft`     | Brouillon ou non  | `false`.                                           | `true`        |

:::info
Toutes les variables prédéfinies peuvent être surchargées, sauf `section`.
:::

### updated

La variable `updated` sert à définir la date de dernière modification d’une page.

_Exemple :_

```yaml
---
updated: 2026-02-02
---
```

:::warning
Avant la version 8.80.1, la variable `updated` était une variable prédéfinie. Elle est désormais optionnelle (et doit être définie dans le front matter pour être utilisée).
:::

### menu

Une page peut être ajoutée à un [menu](4-Configuration.md#menus).

Le nom de l’entrée est le `title` de la page et l’URL est le `path` de la page.

Une même page peut être ajoutée à plusieurs menus, et la position de chaque entrée peut être définie avec la clé `weight` (la plus faible en premier).

_Exemples :_

```yaml
---
menu: main
---
```

```yaml
---
menu: [main, navigation] # same page in multiple menus
---
```

```yaml
---
menu:
  main:
    weight: 10
  navigation:
    weight: 20
---
```

### Taxonomie

La taxonomie permet de connecter, relier et classer le contenu de votre site Web.  
Dans Cecil, ces termes sont regroupés dans des vocabulaires.

Les vocabulaires sont déclarés dans la [_Configuration_](4-Configuration.md#taxonomies).

Une page peut contenir plusieurs vocabulaires (ex. : `tags`) et termes (ex. : `Tag 1`).

_Exemple :_

```yaml
---
tags: ["Tag 1", "Tag 2"]
---
```

### Planification

Planifie la publication des pages.

_Exemple :_

La page sera publiée si la date courante est >= 2023-02-07 :

```yaml
schedule:
  publish: 2023-02-07
```

Cette page est publiée si la date courante est <= 2022-04-28 :

```yaml
schedule:
  expiry: 2022-04-28
```

### redirect

Comme son nom l’indique, la variable `redirect` sert à rediriger une page vers une URL dédiée.

_Exemple :_

```yaml
---
redirect: "https://arnaudligny.fr"
---
```

:::info
La redirection fonctionne avec le template [`redirect.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/_default/redirect.html.twig).
:::

### alias

Un alias est une redirection vers la page courante.

_Exemple :_

```yaml
---
title: "About"
alias:
  - contact
---
```

Dans l’exemple précédent, `contact/` redirige vers `about/`.

### output

Définit le format de sortie de la page.

Les formats disponibles sont : `html`, `atom`, `rss`, `json`, `xml`, etc.  
Vous pouvez définir un ou plusieurs formats dans un tableau.

Il n’est pas obligatoire de définir un format de sortie, mais si vous le faites, il doit correspondre à l’un des formats disponibles définis dans la [_Configuration_](4-Configuration.md#output-formats).

_Exemple :_

```yaml
---
output: [html, atom]
---
```

### external

Une page avec une variable `external` tente de récupérer le contenu de la ressource ciblée.

_Exemple :_

```yaml
---
external: "https://raw.githubusercontent.com/Cecilapp/Cecil/master/README.md"
---
```

### Préfixe de fichier

Le nom de fichier peut contenir un préfixe pour définir les variables `date` ou `weight` de la page (utilisé par [`sortby`](3-Templates.md#sort-by-date)).

:::info
Les séparateurs de préfixe disponibles sont `_` et `-`.
:::

#### date

Le _date prefix_ est utilisé pour définir la `date` de la page et doit être un format de date valide (c.-à-d. : « YYYY-MM-DD »).

_Exemple :_

Dans « 2019-04-23_My blog post.md » :

- le préfixe est « 2019-04-23 »
- la `date` de la page est « 2019-04-23 »
- le `title` de la page est « My blog post »

#### weight

Le _weight prefix_ est utilisé pour définir l’ordre de tri de la page et doit être une valeur entière valide.

_Exemple :_

Dans « 1_The first project.md » :

- le préfixe est « 1 »
- le `weight` de la page est « 1 »
- le `title` de la page est « The first project »

### Section

Certaines variables dédiées peuvent être utilisées dans une _Section_ personnalisée (c.-à-d. : `<section>/index.md`).

#### sortby

L’ordre des pages dans une _Section_ peut être modifié.

Valeurs disponibles :

- `date`: plus récentes en premier
- `title`: ordre alphabétique
- `weight`: plus léger en premier

_Exemple :_

```yaml
---
sortby: title
---
```

**More options:**

```yaml
---
sortby:
  variable: date    # "date", "updated", "title" or "weight"
  desc_title: false # used with "date" or "updated" variable value to sort by desc title order if items have the same date
  reverse: false    # reversed if true
---
```

#### pagination

La [configuration globale de pagination](4-Configuration.md#pages-pagination) est utilisée par défaut, mais vous pouvez la modifier pour une _Section_ donnée.

_Exemple :_

```yaml
---
pagination:
  max: 5
  path: "page"
  pagination: false
---
```

#### cascade

Toutes les variables de `cascade` sont ajoutées au front matter de toutes les _sous-pages_.

_Exemple :_

```yaml
---
cascade:
  banner: image.jpg
---
```

:::info
Les variables existantes ne sont pas écrasées.
:::

#### circular

Définissez `circular` à `true` pour activer la navigation circulaire avec [_page.<prev/next>_](3-Templates.md#page-prev-next).

_Exemple :_

```yaml
---
circular: true
---
```

### Page d'accueil

Comme une autre section, la _Page d'accueil_ prend en charge la configuration `sortby` et `pagination`.

#### pagesfrom

Définissez un nom de _Section_ valide dans `pagesfrom` pour utiliser la collection de pages de cette _Section_ dans la _Page d'accueil_.

_Exemple :_

```yaml
---
pagesfrom: blog
---
```

### excluded

Définissez `excluded` à `true` pour masquer une page des pages de liste (c.-à-d. : _Home page_, _Section_, _Sitemap_, etc.).

_Exemple :_

```yaml
---
excluded: true
---
```

:::info
`excluded` est différent de [`published`](#predefined-variables) : une page exclue est publiée, mais masquée des pages de liste.
:::

:::warning
Depuis la version 8.49.0, l’ancienne variable `exclude` a été remplacée par `excluded`.
:::

## Multilingue

Si vos pages sont disponibles en plusieurs [langues](4-Configuration.md#languages), il existe 2 façons différentes de le définir :

### Via le nom de fichier

C’est la méthode la plus courante pour traduire une page depuis la [langue](4-Configuration.md#language) principale vers une autre langue.

Il suffit de dupliquer la page de référence et de lui ajouter en suffixe le `code` de la langue cible (ex. : `fr`).

_Exemple :_

```plaintext
├─ about.md    # the reference page
└─ about.fr.md # the french version (`fr`)
```

:::tip
Vous pouvez changer l’URL de la page traduite avec la variable `slug` dans le front matter. Par exemple :

```yml
---
slug: a-propos
---
# about.md    -> /about/
# about.fr.md -> /fr/a-propos/
```

:::

### Via le front matter

Si vous souhaitez créer une page dans une langue autre que la langue principale, sans qu’elle soit la traduction d’une page existante, vous pouvez utiliser la variable `language` dans son front matter.

_Exemple :_

```yml
---
language: fr
---
```

### Lier les pages traduites

Chaque page traduite référence les pages dans les autres langues.

Cette collection de pages est disponible dans les [templates](3-Templates.md#page) via la variable suivante :

```twig
{{ page.translations }}
```

:::info
La variable `langref` est fournie par défaut, mais vous pouvez la modifier dans le front matter :

```yml
---
langref: my-page-ref
---
```

:::

## Contenu dynamique

Avec cette fonctionnalité **_expérimentale_**, vous pouvez utiliser des **[variables](3-Templates.md#variables)** et des **shortcodes** dans le [body](#body).

:::important
Pour cela, vous devez inclure un template spécifique :

```twig
{{ include(page.content_template) }}
```

(au lieu de `{{ page.content }}`)
:::

### Afficher des variables

Les variables du front matter peuvent être utilisées dans le body avec la syntaxe de template `{{ page.variable }}`.

_Exemple :_

```twig
--
var: 'value'
---
The value of `var` is {{ page.var }}.
```

> Experimental

### Shortcodes

Les shortcodes sont des helpers pour créer du contenu dynamique.

> Experimental

#### Shortcodes intégrés

2 shortcodes sont disponibles par défaut :

##### YouTube

```twig
{{ shortcode.youtube(id) }}
```

- `id`: ID de la vidéo YouTube

_Exemple :_

```twig
{{ shortcode.youtube('NaB8JBfE7DY') }}
```

##### GitHub Gist

```twig
{{ shortcode.gist(user, id) }}
```

- `user`: nom d’utilisateur GitHub
- `id`: ID du Gist

_Exemple :_

```twig
{{ shortcode.gist('ArnaudLigny', 'fbe791e05b93951ffc1f6abda8ee88f0') }}
```

#### Shortcode personnalisé

Un shortcode est une [macro Twig](https://twig.symfony.com/doc/tags/macro.html) que vous devez ajouter dans un template nommé `shortcodes.twig`.

_Exemple :_

`shortcodes.twig`:

```twig
{% extends 'extended/macros.twig' %}

{% block macros %}

{# the "foo" shortcode #}
{% macro foo(bar = 'bar') %}
<strong>{{ bar }}</strong>
{% endmacro %}

{% endblock %}
```
