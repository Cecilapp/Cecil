<!--
title: "Structure des dossiers"
description: "Organisation des fichiers sources, arborescence du site généré et routage des fichiers vers les URL."
date: 2026-03-27
updated: 2026-10-03
path: documentation/bien-demarrer/structure-des-dossiers
-->
# Structure des dossiers

## Arborescence du système de fichiers

Organisation des fichiers du projet.

```plaintext
<monsiteweb>
├─ pages
|  ├─ blog            <- Section
|  |  ├─ billet-1.md  <- Page dans Section
|  |  └─ billet-2.md
|  ├─ projets
|  |  └─ projet-a.md
|  └─ about.md        <- Page racine
├─ assets
|  ├─ styles.scss     <- Fichier d'asset
|  └─ logo.png
├─ static
|  └─ fichier.pdf     <- Fichier statique
└─ data
   └─ auteurs.yml     <- Collection de données
```

## Arborescence du site généré

Résultat de la génération.

```plaintext
<monsiteweb>
└─ _site
   ├─ index.html               <- Page d'accueil générée
   ├─ blog/
   |  ├─ index.html            <- Liste des articles générée
   |  ├─ billet-1/index.html   <- Article de blog
   |  └─ billet-2/index.html
   ├─ projets/
   |  ├─ index.html            <- Liste des projets générée
   |  └─ projet-a/index.html   <- Projet individuel
   ├─ about/index.html         <- Page "À propos"
   ├─ styles.css
   ├─ logo.png
   └─ fichier.pdf
```

:::info
Par défaut, chaque page est générée sous la forme `nomdufichier-sluglifié/index.html` pour obtenir une « belle » URL comme `https://monsiteweb.tld/section/nomdufichier-sluglifié/`.

Pour obtenir une URL « ugly » (comme `404.html` au lieu de `404/`), définissez `uglyurl: true` dans le [front matter](../content/1-pages.fr.md#front-matter) de la page.
:::

## Routage basé sur les fichiers

Les fichiers Markdown du répertoire `pages` activent un routage basé sur les fichiers. Cela signifie que l’ajout, par exemple, de `pages/mon-projet/projet-a.md` le rendra accessible à l’URL `/projet-a` dans votre navigateur.

```plaintext
Fichier :
                   pages/mon-projet/projet-a.md
                        └───── filepath ──────┘
URL :
    ┌───── baseurl ─────┬─────── path ────────┐
     https://exemple.com/mon-projet/projet-a/index.html
                        └─ section ─┴─ slug ──┘
```

:::important
Deux types de préfixes peuvent modifier l’URL, voir la section [Préfixe de fichier](../content/1-pages.fr.md#prefixe-de-fichier) ci-dessous.
:::
