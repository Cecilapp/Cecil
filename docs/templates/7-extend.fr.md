<!--
title: "Étendre les templates"
description: "Ajoutez des fonctions et filtres personnalisés, ou utilisez un thème."
date: 2026-05-26
updated: 2026-10-16
path: documentation/templates/etendre
-->
# Étendre les templates

Les templates peuvent être étendus avec des fonctions et filtres personnalisés, ou regroupés dans un thème réutilisable.

## Fonctions et filtres

Vous pouvez ajouter des [fonctions](reference/1-functions.fr.md) et des [filtres](reference/3-filters.fr.md) personnalisés avec une [**_extension Twig_**](../developers/1-extend.fr.md#extension-twig).

## Thème

Il est simple de créer un thème : il suffit de créer un dossier `<theme>` avec la structure suivante (comme un site web, mais sans pages) :

```plaintext
<mon-site>
└─ themes
   └─ <theme>
      ├─ config.yml
      ├─ assets
      ├─ layouts
      ├─ static
      └─ translations
```

Activez-le ensuite avec l’option de configuration [`theme`](../configuration/1-site.fr.md#theme).
