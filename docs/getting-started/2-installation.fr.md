<!--
title: "Installation"
description: "Prérequis, méthodes d’installation (PHAR, gestionnaires de paquets, Composer), mise à jour et dépannage."
date: 2026-10-05
updated: 2026-10-05
path: documentation/bien-demarrer/installation
-->
# Installation

Cecil est distribué sous la forme d’un unique fichier exécutable, `cecil.phar`, qui fonctionne partout où PHP est installé.

## Prérequis

- [PHP](https://www.php.net/manual/fr/install.php) 8.3+
- Extensions PHP : `fileinfo`, `gd` et `mbstring`

Vérifiez votre version de PHP et les extensions chargées :

```bash
php -v
php -m
```

### Extensions optionnelles

| Extension | Usage |
| --------- | ----- |
| [`intl`](https://www.php.net/manual/fr/book.intl.php) | [Localisation](../templates/5-localization.fr.md) des dates avec d’autres locales que `en` (améliore les performances sinon). |
| [`imagick`](https://www.php.net/manual/fr/book.imagick.php) | Traitement des images, préféré à GD si disponible. |
| [`ffi`](https://www.php.net/manual/fr/book.ffi.php) | Traitement des images avec [libvips](https://www.libvips.org/) (nécessite une [installation via Composer](#composer)). |

## Télécharger le PHAR

Téléchargez `cecil.phar` depuis votre terminal :

```bash
curl -LO https://cecil.app/cecil.phar
```

Puis exécutez-le avec PHP :

```bash
php cecil.phar --version
```

:::info
Vous pouvez également télécharger une [version spécifique ou la version d’aperçu](/fr/telecharger/).
:::

## Installation globale

Installer Cecil globalement permet d’exécuter la commande `cecil` depuis n’importe quel dossier, au lieu de `php cecil.phar`.

### macOS et Linux

Avec [Homebrew](https://brew.sh) :

```bash
brew install cecilapp/tap/cecil
```

Ou manuellement, en déplaçant le PHAR dans un dossier de votre `PATH` :

```bash
mv cecil.phar /usr/local/bin/cecil
chmod +x /usr/local/bin/cecil
```

### Windows

Avec [Scoop](https://scoop.sh) :

```bash
scoop install https://cecil.app/scoop/cecil.json
```

Ou manuellement :

1. Déplacez `cecil.phar` dans un dossier dédié tel que `C:\bin`
2. Renommez `cecil.phar` en `cecil`
3. Ajoutez `;C:\bin` à votre variable d’environnement `PATH`
4. Créez un [« wrapping batch script »](https://raw.githubusercontent.com/Cecilapp/Cecil/main/bin/cecil.bat) à côté

### PHIVE

Avec [PHIVE](https://phar.io) (The PHAR Installation and Verification Environment) :

```bash
phive install cecil
```

### Composer {#composer}

Avec [Composer](https://getcomposer.org) :

```bash
composer global require cecil/cecil
```

:::important
Assurez-vous que le dossier des binaires globaux de Composer est dans votre `PATH`. Exécutez `composer global config bin-dir --absolute` pour l’obtenir.
:::

:::tip
Pour utiliser Cecil comme dépendance d’un projet PHP, voir [Bibliothèque](../developers/2-library.fr.md).
:::

## Vérifier l’installation

```bash
cecil --version
```

:::info
Exécutez `cecil list` pour afficher les [commandes disponibles](../commands/index.fr.md).
:::

## Mise à jour

Mettez à jour Cecil vers la dernière version selon votre méthode d’installation :

```bash
# PHAR
php cecil.phar self-update
# Homebrew
brew upgrade cecilapp/tap/cecil
# Scoop
scoop update cecil
# PHIVE
phive update cecil
# Composer
composer global update cecil/cecil
```

La commande `self-update` (PHAR uniquement) accepte également les options suivantes :

- `--rollback` : revient à la version précédemment installée
- `--stable` : force la mise à jour vers la dernière version stable
- `--preview` : force la mise à jour vers la dernière version instable

:::info
Le journal des modifications de chaque version est disponible sur [GitHub](https://github.com/Cecilapp/Cecil/releases).
:::

## Dépannage

### `command not found: cecil`

Le dossier d’installation n’est pas dans votre `PATH` : ajoutez-le, ou exécutez Cecil avec `php cecil.phar` depuis le dossier contenant le PHAR.

### Erreur de version ou d’extension PHP

Le PHP utilisé en ligne de commande peut différer de celui attendu (plusieurs versions installées) : vérifiez-le avec `php -v` et `php --ini`, puis activez les extensions manquantes dans le fichier `php.ini` chargé.

### Diagnostiquer un site

Une fois un site créé, exécutez la commande [`doctor`](../commands/5-doctor.fr.md) pour diagnostiquer sa configuration.
