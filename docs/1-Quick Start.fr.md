<!--
title: Démarrage rapide
description: "Créez un nouveau site web et prévisualiser le localement."
date: 2021-11-03
updated: 2025-11-27
slug: demarrage-rapide
menu: home
-->
# Démarrage rapide

Cecil est une application en ligne de commande, propulsée par [PHP](https://www.php.net), qui fusionne des fichiers textes plats (écrit en [Markdown](https://daringfireball.net/projects/markdown/)), des images et des templates [Twig](https://twig.symfony.com/) afin de générer un [site statique](https://fr.wikipedia.org/wiki/Site_web_statique).

:::info
La documentation complète est disponible, en anglais, à l’adresse suivante : <https://cecil.app/documentation/>
:::

## Créer un site web

Vous pouvez créer un nouveau site web en quelques minutes.

Suivez les étapes ci-dessous pour créer votre premier site web Cecil.

[![Example de nouveau site](/docs/cecil-newsite.png)](https://cecilapp.github.io/skeleton/)

:::info
Démo du résultat attendu : <https://cecilapp.github.io/skeleton/>.
:::

### Prérequis

- [PHP](https://php.net/manual/fr/install.php) 8.1+
- Terminal (une compréhension de base du [terminal](https://fr.wikipedia.org/wiki/%C3%89mulateur_de_terminal))
- Éditeur de texte, comme [VS Code](https://code.visualstudio.com) et/ou [Typora](https://typora.io)

### 1. Télécharger Cecil

Téléchargez `cecil.phar` depuis votre terminal:

```bash
curl -LO https://cecil.app/cecil.phar
```

Vous pouvez également [télécharger Cecil](https://cecil.app/download/) manuellement, ou utiliser :

- [Homebrew](https://brew.sh): `brew install cecilapp/tap/cecil`
- [Scoop](https://scoop.sh): `scoop install https://cecil.app/scoop/cecil.json`

### 2. Créer un nouveau site

Créez un répertoire pour le site Web (ex : `<monsiteweb>`), placez y `cecil.phar`, puis exécutez la commande `new:site` :

```bash
php cecil.phar new:site
```

### 3. Ajouter une page

Exécutez la commande `new:page` :

```bash
php cecil.phar new:page --name=ma-premiere-page.md
```

Vous pouvez maintenant modifier la page nouvellement créée avec votre éditeur Markdown : `<monsiteweb>/pages/ma-premiere-page.md`.

:::tip
Nous vous recommandons d’utiliser [Typora](https://www.typora.io) pour éditer vos fichiers Markdown.
:::

### 4. Vérifier l’aperçu

Exécutez la commande suivante pour créer un aperçu du site Web :

```bash
php cecil.phar serve
```

Naviguez ensuite sur `http://localhost:8000`.

:::info
La commande `serve` démarre un serveur HTTP local et un observateur : si un fichier (une page, un template ou la config) est modifié, la page active du navigateur est automatiquement rechargée.
:::

### 5. Générer et déployer

Quand vous êtes satisfait du résultat, vous pouvez générer le site afin de le déployer sur le Web.

Exécutez la commande suivante pour générer le site :

```bash
php cecil.phar build
```

Vous pouvez maintenant copier le contenu du répertoire `_site` sur un serveur Web 🎉

----

## Créer un blog

Si vous souhaiter créer un blog sans vous casser la tête, rapidement, le [starter blog](https://github.com/Cecilapp/the-butler#readme) est fait pour vous.

[![Exemple de nouveau blog](/docs/cecil-newblog.png)](https://github.com/Cecilapp/the-butler#readme)

Le moyen le plus simple de déployer et de gérer votre blog est certainement avec [Netlify](https://cecil.app/hosting/netlify/deploy/) ou [Vercel](https://cecil.app/hosting/vercel/deploy/).

### Déployer sur Netlify

[![Déployer sur Netlify](https://www.netlify.com/img/deploy/button.svg)](https://cecil.app/hosting/netlify/deploy/)

### Déployer sur Vercel

[![Déployer sur Vercel](https://vercel.com/button/default.svg)](https://cecil.app/hosting/vercel/deploy/)
