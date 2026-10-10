<!--
title: "new:page"
description: "Créer une nouvelle page, éventuellement à partir d’un modèle."
date: 2026-03-27
updated: 2026-10-10
path: documentation/commandes/new-page
-->
# new:page

Crée une nouvelle page.

## Utilisation

```plaintext
Description:
  Creates a new page

Usage:
  new:page [options] [--] [<path>]

Arguments:
  path                        Use the given path as working directory

Options:
      --name=NAME             Page path name
      --slugify|--no-slugify  Slugify file name (or disable --no-slugify)
  -p, --prefix                Prefix the file name with the current date (`YYYY-MM-DD`)
  -f, --force                 Override the file if already exist
  -o, --open                  Open editor automatically
      --editor=EDITOR         Editor to use with open option
  -h, --help                  Display help for the given command. When no command is given display help for the list command
  -q, --quiet                 Do not output any message
  -V, --version               Display this application version
      --ansi|--no-ansi        Force (or disable --no-ansi) ANSI output
  -n, --no-interaction        Do not ask any interactive question
  -v|vv|vvv, --verbose        Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The new:page command creates a new page file.
  If you run this command without any options, it will ask you for the page name and other options.
  
    cecil.phar new:page
    cecil.phar new:page --name=path/to/a-page.md
    cecil.phar new:page --name=path/to/A Page.md --slugify
  
  To create a new page with a date prefix (i.e: `YYYY-MM-DD`), run:
  
    cecil.phar new:page --prefix
  
  To create a new page and open it with an editor, run:
  
    cecil.phar new:page --open --editor=editor
  
  To override an existing page, run:
  
    cecil.phar new:page --force
```

## Modèles de page

Vous pouvez définir vos propres modèles pour vos nouvelles pages dans le répertoire `models` :

1. Le nom doit être basé sur le nom de la section (par exemple : `blog.md`)
2. Le modèle par défaut doit être nommé `default.md` (pour les pages racine ou les sections de pages sans modèle)

Deux variables dynamiques sont disponibles :

1. `%title%` : le nom du fichier
2. `%date%` : la date actuelle

## Ouvrir avec votre éditeur

Avec l’option `--open`, l’éditeur s’ouvrira automatiquement. Utilisez donc la clé `editor` dans votre fichier de configuration pour définir l’éditeur par défaut (par exemple : `editor: typora`).
