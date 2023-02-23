<!--
title: Démarrage rapide
description: "Créez un nouveau site et prévisualiser le localement."
date: 2021-11-03
updated: 2023-02-11
slug: demarrage-rapide
menu: home
-->
# Démarrage rapide

Cecil est une application en ligne de commande, propulsée par [PHP](https://www.php.net), qui fusionne des fichiers textes plats (écrit en [Markdown](https://daringfireball.net/projects/markdown/)), des images et des templates [Twig](https://twig.symfony.com/) afin de générer un [site statique](https://fr.wikipedia.org/wiki/Site_web_statique).

:::info
La documentation complète est disponible, en anglais, à l’adresse suivante : <https://cecil.app/documentation/>
:::

## Créer un blog

Si vous souhaiter créer un blog sans vous casser la tête, le [starter blog](https://github.com/Cecilapp/the-butler#readme) est fait pour vous.

Le moyen le plus simple de déployer et de gérer votre blog est certainement avec [Netlify](https://www.netlify.com) + [Netlify CMS](https://www.netlifycms.org) ou [Vercel](https://vercel.com).

[![Déployer sur Netlify](https://www.netlify.com/img/deploy/button.svg)](https://cecil.app/hosting/netlify/deploy/) [![Déployer sur Vercel](https://vercel.com/button/default.svg)](https://cecil.app/hosting/vercel/deploy/)

----

## Créer un site web

Comment créer un site Web en quelques étapes.

:::info
Démo du résultat attendu : <https://cecilapp.github.io/skeleton/>.
:::

### Télécharger Cecil

Téléchargez `cecil.phar` depuis votre terminal:

```bash
curl -LO https://cecil.app/cecil.phar
```

Vous pouvez également [télécharger Cecil](https://cecil.app/download/) manuellement.

> [PHP](https://php.net/manual/fr/install.php) 7.4+ est requis.

### Créer un nouveau site

Créez un répertoire pour le site Web (ex : `<monsiteweb>`), placez y `cecil.phar`, puis exécutez la commande `new:site` :

```bash
php cecil.phar new:site
```

### Ajouter du contenu

Exécutez la commande `new:page` :

```bash
php cecil.phar new:page ma-premiere-page.md
```

Vous pouvez maintenant modifier la page nouvellement créée avec votre éditeur Markdown : `<monsiteweb>/pages/ma-premiere-page.md`.

:::tip
Nous vous recommandons d’utiliser [Typora](https://www.typora.io) pour éditer vos fichiers Markdown.
:::

### Vérifier l’aperçu

Exécutez la commande suivante pour créer un aperçu du site Web :

```bash
php cecil.phar serve
```

Naviguez ensuite sur `http://localhost:8000`.

:::info
La commande `serve` démarre un serveur HTTP local et un observateur : si un fichier (une page, un template ou la config) est modifié, la page active du navigateur est automatiquement rechargée.
:::

### Générer et déployer

Quand vous êtes satisfait du résultat, vous pouvez générer le site afin de le déployer sur le Web.

Exécutez la commande suivante pour générer le site :

```bash
php cecil.phar build
```

Vous pouvez maintenant copier le contenu du répertoire `_site` sur un serveur Web 🎉
