<!--
title: "Multilingue"
description: "Traduisez les pages via le nom de fichier ou le front matter et liez les pages traduites."
date: 2026-03-27
updated: 2026-10-03
path: documentation/contenu/multilingue
-->
# Multilingue

Si vos pages sont disponibles en plusieurs [langues](../configuration/2-languages.fr.md#languages), il existe 2 façons différentes de le définir :

## Via le nom de fichier

C’est la méthode la plus courante pour traduire une page depuis la [langue](../configuration/2-languages.fr.md#language) principale vers une autre langue.

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

## Via le front matter

Si vous souhaitez créer une page dans une langue autre que la langue principale, sans qu’elle soit la traduction d’une page existante, vous pouvez utiliser la variable `language` dans son front matter.

_Exemple :_

```yml
---
language: fr
---
```

## Lier les pages traduites

Chaque page traduite référence les pages dans les autres langues.

Cette collection de pages est disponible dans les [templates](../templates/2-variables.fr.md#page) via la variable suivante :

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
