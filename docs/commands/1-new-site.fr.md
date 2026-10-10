<!--
title: "new:site"
description: "Créer un nouveau site."
date: 2026-03-27
updated: 2026-10-10
path: documentation/commandes/new-site
-->
# new:site

Crée un nouveau site.

## Utilisation

```plaintext
Description:
  Creates a new website

Usage:
  new:site [options] [--] [<path>]

Arguments:
  path                  Use the given path as working directory

Options:
  -f, --force           Override directory if it already exists
      --demo            Add demo content (pages, templates and assets)
  -h, --help            Display help for the given command. When no command is given display help for the list command
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The new:site command creates a new website in the current directory, or in <path> if provided.
  If you run this command without any options, it will ask you for the website title, baseline, base URL, description, etc.
  
    cecil.phar new:site
    cecil.phar new:site path/to/the/working/directory
  
  To create a new website with demo content, run:
  
    cecil.phar new:site --demo
  
  To override an existing website, run:
  
    cecil.phar new:site --force
```
