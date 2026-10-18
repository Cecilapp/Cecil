<!--
title: "Front matter"
description: "Variables de page personnalisées et prédéfinies : menu, taxonomie, planification, redirection, alias, sortie, etc."
date: 2026-03-27
updated: 2026-10-16
path: documentation/contenu/front-matter
-->
# Front matter

Le _front matter_ peut contenir des variables personnalisées appliquées à la page courante.

Il doit se trouver au tout début du fichier et être du [YAML](https://fr.wikipedia.org/wiki/YAML) valide.

:::info
YAML est le format par défaut : vous pouvez utiliser INI, TOML ou JSON à la place avec l’option [`pages.frontmatter`](../configuration/4-pages.fr.md#pages-frontmatter).
:::

## Variables prédéfinies

| Variable    | Description       | Valeur par défaut                                   | Exemple       |
| ----------- | ----------------- | --------------------------------------------------- | ------------- |
| `title`     | Titre             | Nom de fichier sans extension, préfixe ni suffixe de langue. | `Post 1`      |
| `layout`    | Template          | Voir les [_Règles de recherche_](../templates/1-lookup-rules.fr.md#regles-de-recherche). | `404`         |
| `date`      | Date de création  | Date de modification du fichier (objet PHP _DateTime_). | `2019-04-15`  |
| `section`   | Section           | _Section_ de la page.                               | `blog`        |
| `path`      | Chemin            | _Path_ de la page.                                  | `blog/post-1` |
| `slug`      | Slug              | _Slug_ de la page.                                  | `post-1`      |
| `weight`    | Ordre de tri      | [Préfixe de poids](1-pages.fr.md#weight) du fichier, le cas échéant. | `10`          |
| `language`  | Code de langue    | Suffixe de langue du fichier, ou la [langue](../configuration/2-languages.fr.md#language) principale. | `fr` |
| `langref`   | Référence des traductions | _Path_ de la page (sans la langue).         | `blog/post-1` |
| `published` | Publié ou non     | `true`.                                             | `false`       |
| `draft`     | Brouillon ou non  | `false`.                                            | `true`        |

:::info
Toutes les variables prédéfinies peuvent être surchargées, sauf `section`.
:::

:::important
Les valeurs de `path` et `slug` doivent déjà être « slugifiées » (ex. : `my-post`, et non `My Post`), sinon la génération échoue.
:::

:::tip
`draft: true` équivaut à `published: false`. Les pages non publiées peuvent être prévisualisées avec l’option `--drafts` des commandes [`build`](../commands/4-build.fr.md) et [`serve`](../commands/3-serve.fr.md).
:::

## updated

La variable `updated` sert à définir la date de dernière modification d’une page.

_Exemple :_

```yaml
---
updated: 2026-02-02
---
```

:::info
`lastmod` est un alias de `updated`.
:::

:::warning
Avant la version 8.80.1, la variable `updated` était une variable prédéfinie. Elle est désormais optionnelle (et doit être définie dans le front matter pour être utilisée).
:::

## menu

Une page peut être ajoutée à un [menu](../configuration/1-site.fr.md#menus).

Le nom de l’entrée est le `title` de la page et l’URL est le `path` de la page.

La même page peut être ajoutée à plusieurs menus, et la position de chaque entrée peut être définie avec la clé `weight` (la plus faible en premier). La clé `name` peut être utilisée pour personnaliser le nom de l’entrée par menu.

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

```yaml
---
title: 'Notre expertise'
menu:
  main:
    weight: 15
  footer:
    weight: 15
    name: "Expertise" # personnalise le nom de l'entrée dans ce menu
---
```

## Taxonomie

La taxonomie permet de connecter, relier et classer le contenu de votre site Web.  
Dans Cecil, ces termes sont regroupés dans des vocabulaires.

Les vocabulaires sont déclarés dans la [_Configuration_](../configuration/1-site.fr.md#taxonomies).

Vocabulaire
: Une catégorisation du contenu (ex. : `tags`, `categories`, etc.).

Terme
: Un terme est un élément d’un vocabulaire (ex. : `Développement`, `PHP`, etc.).

_Exemple :_

```yaml
---
tags: ["Développement", "PHP"]
---
```

Cecil génère ensuite, pour chaque vocabulaire :

- une page listant ses termes, ex. : `/tags/`
- une page par terme listant ses pages, ex. : `/tags/developpement/` et `/tags/php/`

Voir les [règles de recherche des templates](../templates/1-lookup-rules.fr.md#type-vocabulary) et les [variables de taxonomie](../templates/2-variables.fr.md#taxonomie) pour personnaliser ces pages.

## Planification

Planifie la publication des pages.

_Exemple :_

La page est publiée à partir du 2023-02-07 (si la date courante est >= 2023-02-07) :

```yaml
---
schedule:
  publish: 2023-02-07
---
```

La page est publiée jusqu’au 2022-04-28 (si la date courante est < 2022-04-28, c’est-à-dire avant minuit) :

```yaml
---
schedule:
  expiry: 2022-04-28
---
```

Les deux peuvent être combinées : la page est publiée si la date `publish` est atteinte **et** que la date `expiry` n’est pas dépassée.

## redirect

Comme son nom l’indique, la variable `redirect` sert à rediriger une page vers une URL dédiée.

_Exemple :_

```yaml
---
redirect: "https://arnaudligny.fr"
---
```

:::info
La redirection fonctionne avec le template [`redirect.html.twig`](https://github.com/Cecilapp/Cecil/blob/main/resources/layouts/_default/redirect.html.twig).
:::

## alias

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

:::info
`aliases` est toujours pris en charge pour la rétrocompatibilité.
:::

## output

Définit le format de sortie de la page.

Les formats disponibles sont : `html`, `atom`, `rss`, `json`, `xml`, etc.  
Vous pouvez définir un ou plusieurs formats dans un tableau.

Il n’est pas obligatoire de définir un format de sortie, mais si vous le faites, il doit correspondre à l’un des formats disponibles définis dans la [_Configuration_](../configuration/8-output.fr.md#output-formats).

_Exemple :_

```yaml
---
output: [html, atom]
---
```

## uglyurl

Définissez `uglyurl` à `true` pour publier la page sous la forme d’un fichier nommé d’après son chemin (ex. : `blog/post-1.html`) au lieu d’un fichier `index.html` dans un dossier (ex. : `blog/post-1/index.html`).

_Exemple :_

```yaml
---
uglyurl: true
---
```

## external

Une page avec une variable `external` tente de récupérer le contenu de la ressource ciblée et l’utilise comme corps de la page.

_Exemple :_

```yaml
---
external: "https://raw.githubusercontent.com/Cecilapp/Cecil/main/README.md"
---
```

## excluded

Définissez `excluded` à `true` pour masquer une page des pages de liste (ex. : _Page d’accueil_, _Section_, _Sitemap_, etc.).

_Exemple :_

```yaml
---
excluded: true
---
```

:::info
`excluded` est différent de [`published`](#variables-predefinies) : une page exclue est publiée, mais masquée des pages de liste.
:::

:::warning
Depuis la version 8.49.0, l’ancienne variable `exclude` a été renommée `excluded` (`exclude` est toujours prise en charge pour la rétrocompatibilité).
:::
