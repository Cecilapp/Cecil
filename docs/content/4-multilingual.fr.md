<!--
title: "Multilingue"
description: "Traduisez les pages via le nom de fichier ou le front matter et liez les pages traduites."
date: 2026-03-27
updated: 2026-10-16
path: documentation/contenu/multilingue
-->
# Multilingue

Si vos pages sont disponibles en plusieurs [langues](../configuration/2-languages.fr.md#languages), il existe deux façons de les définir :

## Via le nom de fichier

C’est la méthode la plus courante pour traduire une page depuis la [langue](../configuration/2-languages.fr.md#language) principale vers une autre langue.

Il suffit de dupliquer la page de référence et de lui ajouter en suffixe le `code` de la langue cible, avant l’extension (ex. : `about.fr.md`). Le code doit correspondre à l’une des [langues](../configuration/2-languages.fr.md#languages) déclarées (format `fr` ou `fr-FR`).

_Exemple :_

```plaintext
├─ about.md    # the reference page
└─ about.fr.md # the French version (`fr`)
```

:::tip
Vous pouvez changer l’URL de la page traduite avec la variable `slug` dans son front matter. Par exemple, dans `about.fr.md` :

```yaml
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

```yaml
---
language: fr
---
```

## Lier les pages traduites

Chaque page traduite référence ses versions dans les autres langues.

Cette collection de pages est disponible dans les [templates](../templates/2-variables.fr.md#page) via la variable suivante :

```twig
{{ page.translations }}
```

:::info
Les traductions sont liées par leur variable `langref` : par défaut, le chemin de la page sans la langue (ex. : `about` pour `about.md` et `about.fr.md`).

Vous pouvez la modifier dans le front matter, par exemple pour lier des pages traduites ayant des noms de fichier différents :

```yaml
---
langref: my-page-ref
---
```

:::
