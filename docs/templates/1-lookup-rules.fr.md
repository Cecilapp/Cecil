<!--
title: "Organisation et règles de recherche"
description: "Types de templates, convention de nommage, templates intégrés et choix du template d’une page."
date: 2026-05-26
updated: 2026-10-16
path: documentation/templates/regles-de-recherche
-->
# Organisation et règles de recherche

Cecil génère les pages à l’aide de templates Twig : cette page explique comment les templates sont organisés et comment le bon template est choisi pour chaque page.

## Organisation des fichiers

### Types de templates

Il existe trois types de templates : les **_layouts_**, les **_composants_** et les **_autres templates_**. Les _layouts_ sont utilisés pour générer les [pages](../content/1-pages.fr.md), et chacun d’eux peut [inclure des templates](https://twig.symfony.com/doc/templates.html#including-other-templates) et des [composants](4-components.fr.md).

### Convention de nommage

Les fichiers templates sont stockés dans le répertoire `layouts/` et doivent être nommés selon la convention suivante :

```plaintext
layouts/(<section>/)<type>|<layout>.<format>(.<language>).twig
```

`<section>` (_facultatif_)
:  La section de la page (ex. : `blog`).

`<type>`
:  Le type de template : `home` (ou `index`) pour la _homepage_, `list` pour les pages de liste (_homepage_ et _sections_), `page` pour une _page_, etc. (voir [_Règles de recherche_](#regles-de-recherche) pour plus de détails).

`<layout>` (_facultatif_)
:  Le nom du layout personnalisé défini dans le [front matter](../content/1-pages.fr.md#front-matter) de la page (ex. : `layout: my-layout`).

`<format>`
:  Le [format de sortie](../configuration/8-output.fr.md#output-formats) de la page générée (ex. : `html`, `rss`, `json`, `xml`, etc.).

`<language>` (_facultatif_)
:  La langue de la page (ex. : `fr`).

_Exemples :_

```plaintext
layouts/home.html.twig       # `type` est "homepage"
layouts/page.html.twig       # `type` est "page"
layouts/page.html.fr.twig    # `type` est "page" et `language` est "fr"
layouts/my-layout.html.twig  # `layout` est "my-layout"
layouts/blog/list.html.twig  # `section` est "blog"
layouts/blog/list.rss.twig   # `section` est "blog" et `format` est "rss"
```

```plaintext
<mon-site>
├─ ...
├─ layouts
|  ├─ index.html.twig      # Utilisé par le type "homepage"
|  ├─ list.html.twig       # Utilisé par les types "homepage" et "section"
|  ├─ list.rss.twig        # Utilisé par les types "homepage" et "section", pour le format de sortie RSS
|  ├─ page.html.twig       # Utilisé par le type "page"
|  ├─ taxonomy
|  |  ├─ tags.html.twig    # Utilisé par le type "vocabulary" de `tags` (liste des termes)
|  |  └─ tag.html.twig     # Utilisé par le type "term" de `tags` (liste des pages)
|  ├─ my-layout.html.twig  # Utilisé par les pages avec `layout: my-layout` dans le front matter
|  ├─ ...
|  └─ partials             # Templates inclus
|     ├─ footer.html.twig
|     └─ ...
└─ themes                  # Layouts et templates des thèmes
   └─ ...
```

### Templates intégrés

Cecil est livré avec un ensemble de [templates intégrés](https://github.com/Cecilapp/Cecil/tree/main/resources/layouts).

:::tip
Si vous avez besoin de modifier des templates intégrés, vous pouvez facilement les extraire via la commande suivante : ils seront copiés dans le répertoire `layouts` de votre site.

```bash
php cecil.phar util:templates:extract
```

:::

## Règles de recherche

Dans la plupart des cas, **vous n’avez pas besoin de préciser le layout** : Cecil sélectionne le layout le plus approprié, en fonction du **type de la page**.

Par exemple, la sortie HTML de la **page d’accueil** (`index.md`) sera générée :

1. avec `my-layout.html.twig` si la variable `layout` est définie à « my-layout » (dans le front matter)
2. sinon, avec `index.html.twig` si le fichier existe
3. sinon, avec `home.html.twig` si le fichier existe
4. sinon, avec `list.html.twig` si le fichier existe

Toutes les règles sont détaillées ci-dessous, pour chaque type de page, par ordre de priorité.

:::info
Chaque template candidat est recherché dans le répertoire `layouts/` du site, puis dans le répertoire `layouts/` de chaque [thème](7-extend.fr.md#theme) (dans l’ordre configuré), puis dans les templates intégrés. Le premier fichier trouvé est utilisé.
:::

:::tip
Pour une page dans une autre langue que la langue par défaut, la variante localisée de chaque candidat (ex. : `page.html.fr.twig`) est essayée en premier : tous les candidats localisés sont recherchés avant les candidats non localisés.
:::

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
5. `<parent>/index.<format>.twig`, `<parent>/list.<format>.twig` et `section/<parent>.<format>.twig`, pour chaque section parente d’une sous-section (la plus proche en premier)
6. `_default/section.<format>.twig`
7. `list.<format>.twig`
8. `_default/list.<format>.twig`

:::tip
La `<section>` d’une [sous-section](../content/1-pages.fr.md#sous-section) est son chemin complet (ex. : `blog/2024`), et une sous-section se replie sur les templates de ses sections parentes : si `blog/2024/list.html.twig` n’existe pas, la sous-section `blog/2024` est générée avec `blog/list.html.twig`.
:::

:::info
Le nom utilisé comme `<section>` (pour une section et ses pages) peut être associé aux layouts d’une autre section avec l’option [`layouts.sections`](../configuration/7-layouts.fr.md#layouts-sections).
:::

### Type _vocabulary_

1. `taxonomy/<plural>.<format>.twig`
2. `vocabulary.<format>.twig`
3. `_default/vocabulary.<format>.twig`

### Type _term_

1. `taxonomy/<plural>/<term>.<format>.twig`
2. `taxonomy/<singular>.<format>.twig`
3. `term.<format>.twig`
4. `_default/term.<format>.twig`
5. `_default/list.<format>.twig`

:::important
Le template du **vocabulaire** est nommé d’après le **pluriel** (ex. : `taxonomy/categories.html.twig` pour `/categories/`), tandis que le template d’un **terme** est nommé d’après le **singulier** (ex. : `taxonomy/category.html.twig` pour `/categories/data-sovereignty/`).
:::

:::tip
`<term>` est le nom du terme « slugifié » : un template dédié au terme « Data Sovereignty » du vocabulaire `categories` est `taxonomy/categories/data-sovereignty.html.twig`.
:::

:::info
La plupart de ces layouts sont disponibles par défaut (dans `_default/`), voir [templates intégrés](#templates-integres).
:::
