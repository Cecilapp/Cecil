<!--
title: "Installation"
description: "Prérequis, méthodes d’installation (PHAR, binaire autonome, gestionnaires de paquets, Composer), mise à jour et dépannage."
date: 2026-10-05
updated: 2026-10-16
path: documentation/bien-demarrer/installation
-->
# Installation

Cecil est distribué sous la forme d’un unique fichier exécutable, `cecil.phar`, qui fonctionne partout où PHP est installé, ainsi que sous forme de [binaires autonomes](#standalone-binary) qui ne nécessitent pas PHP.

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
| [`intl`](https://www.php.net/manual/fr/book.intl.php) | [Localisation](../templates/5-localization.fr.md) des dates pour les locales autres que `en` (sinon facultative, améliore les performances). |
| [`curl`](https://www.php.net/manual/fr/book.curl.php) | Contrôle des liens externes avec la commande [`doctor:links`](../commands/5-doctor.fr.md). |
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

## Binaire autonome {#standalone-binary}

Si PHP n’est pas installé, téléchargez le binaire autonome correspondant à votre plateforme depuis la [dernière version GitHub](https://github.com/Cecilapp/Cecil/releases/latest). Il embarque PHP et les extensions requises.

| Plateforme | Fichier |
| ---------- | ------- |
| Linux x64 | `cecil-linux-x64` |
| Linux ARM64 | `cecil-linux-arm64` |
| macOS Intel | `cecil-macos-x64` |
| macOS Apple Silicon | `cecil-macos-arm64` |
| Windows x64 | `cecil-windows-x64.exe` |

Par exemple :

:::tabs
#### Linux

```bash
curl -Lo cecil https://github.com/Cecilapp/Cecil/releases/latest/download/cecil-linux-x64
chmod +x cecil
./cecil --version
```

#### macOS

```bash
curl -Lo cecil https://github.com/Cecilapp/Cecil/releases/latest/download/cecil-macos-arm64
chmod +x cecil
xattr -d com.apple.quarantine cecil
./cecil --version
```

Les binaires ne sont pas signés : la commande `xattr` supprime l’attribut de quarantaine avant la première exécution.

#### Windows

```powershell
curl.exe -Lo cecil.exe https://github.com/Cecilapp/Cecil/releases/latest/download/cecil-windows-x64.exe
.\cecil.exe --version
```
:::

:::info
Les sommes de contrôle SHA-256 sont disponibles dans le fichier `cecil-binaries.sha256` de chaque version, et la provenance du build peut être vérifiée avec `gh attestation verify cecil --repo Cecilapp/Cecil`.
:::

:::important
Le binaire utilise GD pour le traitement des images : Imagick et libvips ne sont pas disponibles. La commande `self-update` n’est pas disponible non plus : téléchargez à nouveau le binaire pour le mettre à jour.

La commande `serve` repose sur le serveur web intégré de PHP, que le binaire n’embarque pas : elle nécessite un exécutable PHP local. Sans PHP, exécutez la commande `build` et servez le dossier de sortie avec un autre serveur web.
:::

## Installation globale

Installer Cecil globalement permet d’exécuter la commande `cecil` depuis n’importe quel dossier, au lieu de `php cecil.phar`.

::::tabs
#### Linux

En déplaçant le PHAR dans un dossier de votre `PATH` :

```bash
sudo mv cecil.phar /usr/local/bin/cecil
sudo chmod +x /usr/local/bin/cecil
```

#### macOS

Avec [Homebrew](https://brew.sh) :

```bash
brew install cecilapp/tap/cecil
```

#### Windows

Avec [Scoop](https://scoop.sh) :

```powershell
scoop install https://cecil.app/scoop/cecil.json
```

Ou manuellement :

1. Déplacez `cecil.phar` dans un dossier dédié tel que `C:\bin`
2. Renommez `cecil.phar` en `cecil`
3. Ajoutez `;C:\bin` à votre variable d’environnement `PATH`
4. Créez un [script batch d’encapsulation](https://raw.githubusercontent.com/Cecilapp/Cecil/main/bin/cecil.bat) à côté

#### PHIVE

Avec [PHIVE](https://phar.io) (The PHAR Installation and Verification Environment) :

```bash
phive install cecil
```

#### Composer {#composer}

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
::::

## Vérifier l’installation

```bash
cecil --version
```

Ou, si Cecil n’est pas installé globalement :

```bash
php cecil.phar --version
```

:::info
Exécutez `cecil list` pour afficher les [commandes disponibles](../commands/index.fr.md).
:::

## Mise à jour {#update}

Mettez à jour Cecil vers la dernière version selon votre méthode d’installation :

:::code-group
#### PHAR

```bash
php cecil.phar self-update
```

#### Homebrew

```bash
brew upgrade cecilapp/tap/cecil
```

#### Scoop

```bash
scoop update cecil
```

#### PHIVE

```bash
phive update cecil
```

#### Composer

```bash
composer global update cecil/cecil
```
:::

Le [binaire autonome](#standalone-binary) se met à jour en le téléchargeant à nouveau.

La commande `self-update` (alias `selfupdate`, PHAR uniquement) accepte également les options suivantes :

- `--rollback` : revient à la version précédemment installée
- `--stable` : force la mise à jour vers la dernière version stable
- `--preview` : force la mise à jour vers la dernière préversion

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
