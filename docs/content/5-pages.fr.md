<!--
title: "Pages et sections"
description: "Anatomie d’une page, préfixe de fichier, sections, sous-sections et page d’accueil."
date: 2026-03-27
updated: 2026-10-03
path: documentation/contenu/pages
-->
# Pages et sections

Une page est un fichier composé d’un [**front matter**](#front-matter) et d’un [**body**](#corps-body).

## Front matter

Le _front matter_ est une collection de [variables](6-front-matter.fr.md) (au format _clé/valeur_) entourée par `---`.

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

## Corps (body)

Le _body_ est le contenu principal d’une page ; il peut être écrit en [Markdown](7-markdown.fr.md) ou en texte brut.

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
This is advice.
:::
```

## Préfixe de fichier

Le nom de fichier peut contenir un préfixe pour définir les variables `date` ou `weight` de la page (utilisé par [`sortby`](../templates/reference/13-sorts.fr.md#sort-by-date)).

:::info
Séparateurs de préfixe par défaut : `_` et `-`.

Vous pouvez les personnaliser avec l’option [`pages.prefix.separator`](../configuration/25-pages.fr.md#pages-prefix-separator).
:::

### date

Le _date prefix_ est utilisé pour définir la `date` de la page et doit être un format de date valide (c.-à-d. : « YYYY-MM-DD »).

_Exemple :_

Dans « 2019-04-23_My blog post.md » :

- le préfixe est « 2019-04-23 »
- la `date` de la page est « 2019-04-23 »
- le `title` de la page est « My blog post »

### weight

Le _weight prefix_ est utilisé pour définir l’ordre de tri de la page et doit être une valeur entière valide.

_Exemple :_

Dans « 1_The first project.md » :

- le préfixe est « 1 »
- le `weight` de la page est « 1 »
- le `title` de la page est « The first project »

## Section

Certaines variables dédiées peuvent être utilisées dans une _Section_ personnalisée (c.-à-d. : `<section>/index.md`).

### sortby

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

### pagination

La [configuration globale de pagination](../configuration/25-pages.fr.md#pages-pagination) est utilisée par défaut, mais vous pouvez la modifier pour une _Section_ donnée.

_Exemple :_

```yaml
---
pagination:
  max: 5
  path: "page"
---
```

La pagination peut être désactivée pour une _Section_ :

```yaml
---
pagination: false
---
```

### cascade

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

### circular

Définissez `circular` à `true` pour activer la navigation circulaire avec [_page.<prev/next>_](../templates/11-variables.fr.md#page-prev-next).

_Exemple :_

```yaml
---
circular: true
---
```

### Sous-section

Un dossier imbriqué qui contient explicitement un fichier `index.md` devient une _sous-section_ de sa _Section_ parente.

```plaintext
<monsiteweb>
└─ pages
   └─ blog                 <- Section
      ├─ index.md
      ├─ post-1.md         <- Page de la Section « blog »
      └─ 2024              <- Sous-section (contient un « index.md »)
         ├─ index.md
         └─ post-2.md      <- Page de la Section « blog » *et* de la sous-section « blog/2024 »
```

Une _sous-section_ :

- est une _Section_ (même type, mêmes variables et même résolution de [gabarit](../templates/10-lookup-rules.fr.md#type-section)) accessible à sa propre URL (ex. : `/blog/2024/`)
- est rendue avec les gabarits de ses _Sections_ parentes si elle n'a pas les siens (ex. : `blog/list.html.twig`)
- peut être imbriquée à n'importe quelle profondeur (ex. : `blog/2024/06/`)
- liste ses propres pages, et ses pages appartiennent aussi à chacune de leurs _Sections_ parentes
- n'est **pas** listée dans sa _Section_ parente

:::info
Un dossier imbriqué **sans** fichier `index.md` n'est pas une _sous-section_ : ses pages appartiennent simplement à la _Section_ parente.
:::

## Page d'accueil

Comme une autre section, la _Page d'accueil_ prend en charge la configuration `sortby` et `pagination`.

### pagesfrom

Définissez un nom de _Section_ valide dans `pagesfrom` pour utiliser la collection de pages de cette _Section_ dans la _Page d'accueil_.

_Exemple :_

```yaml
---
pagesfrom: blog
---
```
