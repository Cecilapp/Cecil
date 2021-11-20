<!--
title: Démarrage rapide
description: "Créez un nouveau site et prévisualiser le localement."
date: 2021-11-03
updated: 2021-11-19
slug: demarrage-rapide
menu: home
-->

# Démarrage rapide

Cecil est une application en ligne de commande, propulsée par [PHP](https://www.php.net), qui fusionne des fichiers textes plats (écrit en [Markdown](https://daringfireball.net/projects/markdown/)), des images et des templates [Twig](https://twig.symfony.com/) afin de générer un [site statique](https://fr.wikipedia.org/wiki/Site_web_statique).

## Créer un blog

Si vous souhaiter créer un blog sans vous casser la tête, le [starter blog](https://github.com/Cecilapp/the-butler#readme) est fait pou vous !

Cliquez sur le bouton ci-dessous et laisser [Forestry CMS](https://forestry.io) vous guider.

[![Import this project into Forestry](https://assets.forestry.io/import-to-forestryK.svg)](https://cecil.app/cms/forestry/import/)

----

## Créer un site web

Créer un site web – à partir de rien – en 4 étapes!

### Étape 1 : Installer Cecil

Téléchargez `cecil.phar` depuis votre terminal:

```bash
curl -LO https://cecil.app/cecil.phar
```

Vous pouvez également [télécharger Cecil](https://cecil.app/download/) manuellement depuis le site web.

> [PHP](http://php.net/manual/fr/install.php) 7.1+ est requis.

### Étape 2 : Créer un nouveau site

Lancez la commande `new:site` :

```bash
php cecil.phar new:site <monsiteweb>
```

### Étape 3 : Ajouter du contenu

Lancez la commande `new:page` :

```bash
php cecil.phar new:page blog/mon-premier-billet.md <monsiteweb>
```

Vous pouvez maintenant modifier la page nouvellement créée avec votre éditeur Markdown favoris (je recommande [Typora](https://www.typora.io)): `<monsiteweb>/content/blog/mon-premier-billet.md`.

### Étape 4 : Vérifier l’aperçu

Lancez la commande suivante pour générer et servir le site web :

```bash
php cecil.phar serve --drafts <monsiteweb>
```

Naviguez ensuite sur votre nouveau site web à `http://localhost:8000`.

**Notes :**

- La commande `serve` démarre un serveur HTTP local et un observateur : si un fichier (une page, un template ou la config) est modifié, la page active du navigateur est rechargée.
- L’option `--drafts` est utilisée pour inclure les brouillons.
