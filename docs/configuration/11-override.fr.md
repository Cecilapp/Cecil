<!--
title: "Surcharge de configuration"
description: "Surchargez la configuration avec des variables d’environnement ou une option CLI."
date: 2026-03-27
updated: 2026-10-17
path: documentation/configuration/surcharge
-->
# Surcharge de configuration

La configuration peut être surchargée sans modifier le fichier de configuration, via des variables d’environnement ou une option de la CLI.

## Variables d’environnement

La configuration peut être surchargée via des [variables d’environnement](https://en.wikipedia.org/wiki/Environment_variable).

Au démarrage, Cecil essaie également de charger un fichier `.env` depuis le chemin du site (le répertoire de travail actuel, ou l’argument `<path>` s’il est fourni).

- Si le fichier `.env` n’existe pas, Cecil continue normalement.
- Les variables déjà définies par le shell/système sont conservées.

Chaque nom de variable d’environnement doit être préfixé par `CECIL_` et la clé de configuration doit être en majuscules. Les clés imbriquées sont séparées par `_` (ex. : `CECIL_PAGES_DIR` définit `pages.dir`), les clés qui contiennent un tiret bas ne peuvent donc pas être définies ainsi. Les valeurs de type booléen (`true`/`false`, `1`/`0`, `yes`/`no`, `on`/`off`) sont converties en booléens.

Par exemple, la commande suivante définit le `baseurl` du site :

```bash
export CECIL_BASEURL="https://example.com/"
```

Vous pouvez stocker la même valeur dans un fichier `.env` à la racine du projet :

```dotenv
CECIL_BASEURL="https://example.com/"
CECIL_TITLE="Mon site Cecil"
```

## Option CLI

Vous pouvez combiner plusieurs fichiers de configuration avec l’option `--config` des commandes `build` et `serve` :

```bash
php cecil.phar build --config config-1.yml,config-2.yml
```

Les fichiers sont fusionnés de gauche à droite, après le fichier de configuration principal : une valeur scalaire est remplacée par la dernière définie, les tableaux associatifs sont fusionnés clé par clé, les [listes de valeurs](index.fr.md#listes) sont remplacées (ou ajustées avec un tableau de booléens), les formats de sortie sont fusionnés par `name` et les autres listes d’entrées (ex. menus) sont concaténées.

---
