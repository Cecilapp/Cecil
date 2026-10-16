<!--
title: "Démarrage rapide"
description: "Installez Cecil, créez un site, ajoutez des pages, prévisualisez-le localement, puis générez-le et déployez-le."
date: 2021-11-03
updated: 2026-10-16
alias: documentation/demarrage-rapide
menu: home
path: documentation/bien-demarrer/demarrage-rapide
-->
# Démarrage rapide

Ce guide vous accompagne dans la création de votre premier site Cecil, sa prévisualisation en local, puis sa construction et son déploiement.

## Créer un site web

Vous pouvez créer un nouveau site web en quelques minutes.

Suivez les étapes ci-dessous pour créer votre premier site web Cecil.

[![Example de nouveau site](/docs/cecil-newsite.png)](https://cecilapp.github.io/skeleton/)

:::info
Démo du résultat attendu : <https://cecilapp.github.io/skeleton/>.
:::

### Prérequis

- [PHP](https://php.net/manual/fr/install.php) 8.3+
- Terminal (une compréhension de base du [terminal](https://fr.wikipedia.org/wiki/%C3%89mulateur_de_terminal))
- Éditeur de texte, comme [VS Code](https://code.visualstudio.com) et/ou [Typora](https://typora.io)

### Pas à pas

::::steps
#### Télécharger Cecil

Téléchargez `cecil.phar` depuis votre terminal :

```bash
curl -LO https://cecil.app/cecil.phar
```

Vous pouvez également [télécharger Cecil manuellement](https://cecil.app/download/), ou l’installer globalement avec un gestionnaire de paquets, puis exécuter `cecil` au lieu de `php cecil.phar` :

:::code-group
#### Homebrew

```bash
brew install cecilapp/tap/cecil
```

#### Scoop

```bash
scoop install https://cecil.app/scoop/cecil.json
```
:::

Voir [Installation](2-installation.fr.md) pour les autres méthodes.

#### Créer un nouveau site

Créez un répertoire pour le site Web (ex : `<monsiteweb>`), placez y `cecil.phar`, puis exécutez la commande `new:site` :

```bash
php cecil.phar new:site
```

#### Ajouter une page

Exécutez la commande `new:page` :

```bash
php cecil.phar new:page
```

Vous pouvez maintenant modifier la page nouvellement créée avec votre éditeur Markdown : `<monsiteweb>/pages/<nouvelle-page>.md`.

:::tip
Nous vous recommandons d’utiliser [Typora](https://www.typora.io) pour éditer vos fichiers Markdown.
:::

#### Vérifier l’aperçu

Exécutez la commande suivante pour créer un aperçu du site Web :

```bash
php cecil.phar serve
```

Naviguez ensuite sur `http://localhost:8000`.

:::info
La commande `serve` démarre un serveur HTTP local et un observateur : si un fichier (une page, un template ou la config) est modifié, la page active du navigateur est automatiquement rechargée.
:::

#### Générer et déployer

Quand vous êtes satisfait du résultat, vous pouvez générer le site afin de le déployer sur le Web.

Exécutez la commande suivante pour générer le site :

```bash
php cecil.phar build
```

Vous pouvez maintenant copier le contenu du répertoire `_site` sur un serveur Web 🎉
::::

:::tip
Pour déployer automatiquement à chaque push sur votre dépôt Git, voir [Déployer](../deploy/index.fr.md).
:::

----
