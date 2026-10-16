<!--
title: "Structure des dossiers"
description: "Organisation des fichiers sources, arborescence du site généré et routage des fichiers vers les URL."
date: 2026-03-27
updated: 2026-10-16
path: documentation/bien-demarrer/structure-des-dossiers
-->
# Structure des dossiers

Cette page décrit l’organisation d’un projet Cecil, la structure du site construit et la façon dont les fichiers sont associés aux URL.

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
|  ├─ styles.scss     <- Fichier d’asset
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
   ├─ index.html               <- Page d’accueil générée
   ├─ blog/
   |  ├─ index.html            <- Liste des billets générée
   |  ├─ billet-1/index.html   <- Un billet de blog
   |  └─ billet-2/index.html
   ├─ projets/
   |  ├─ index.html            <- Liste des projets générée
   |  └─ projet-a/index.html   <- Un projet
   ├─ about/index.html         <- Page « À propos »
   ├─ styles.css               <- Asset compilé
   ├─ logo.png
   └─ fichier.pdf
```

:::info
Par défaut, chaque page est générée sous la forme `nom-du-fichier-slugifié/index.html` pour obtenir une « belle » URL comme `https://monsiteweb.tld/section/nom-du-fichier-slugifié/`.

Pour obtenir une URL « brute » (comme `404.html` au lieu de `404/`), définissez `uglyurl: true` dans le [front matter](../content/1-pages.fr.md#front-matter) de la page.

Les fichiers du répertoire `assets` ne sont publiés que s’ils sont utilisés dans les templates (avec la fonction `asset()`), alors que les fichiers du répertoire `static` sont copiés tels quels.
:::

## Routage basé sur les fichiers

Les fichiers Markdown du répertoire `pages` permettent un routage basé sur les fichiers. Cela signifie que l’ajout, par exemple, de `pages/mon-projet/projet-a.md` le rendra accessible à l’URL `/mon-projet/projet-a/` dans votre navigateur.

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
Deux types de préfixes peuvent modifier l’URL, voir la section [Préfixe de fichier](../content/1-pages.fr.md#prefixe-de-fichier).
:::
