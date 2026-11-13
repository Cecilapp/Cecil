<!--
title: "Étendre les templates"
description: "Ajoutez des fonctions et filtres personnalisés, ou utilisez un thème."
date: 2026-05-26
updated: 2026-11-13
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

Le fichier `config.yml` du thème surcharge la [configuration par défaut](../configuration/index.fr.md#priorite) et est fusionné avec la configuration du site, qui est prioritaire, sauf pour les [listes de valeurs](../configuration/index.fr.md#listes) : les valeurs du site s’ajoutent à celles du thème, et une valeur du thème ne peut être retirée qu’avec `false` (ex. `homepage: {atom: false}`).

### Thèmes requis

Un thème peut requérir d’autres thèmes (ex. un thème composant fournissant un champ de recherche, ou un [plugin](../developers/1-extend.fr.md#plugins)) avec l’option `theme` de son `config.yml` :

_/themes/&lt;theme&gt;/config.yml_

```yaml
theme:
  - <theme-requis>
```

Les thèmes requis sont activés avec le thème, sans autre configuration du site : leurs layouts, assets, fichiers statiques, traductions, extensions et `config.yml` sont chargés. Ils sont ajoutés après les thèmes du site : le thème (comme le site) surcharge donc les thèmes qu’il requiert.

:::important
Les thèmes requis doivent eux aussi être installés, dans le dossier `themes` du site : déclarez-les comme dépendances dans le `composer.json` du thème.
:::
