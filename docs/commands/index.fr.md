<!--
title: "Commandes"
description: "Référence des commandes et options de la CLI de Cecil."
date: 2026-03-27
updated: 2026-10-17
weight: 6
sortby: weight
path: documentation/commandes
-->
# Commandes

Liste des commandes disponibles.

```plaintext
Available commands:
  about                      Shows a short description about Cecil
  build                      Builds the website
  clear                      Removes all generated files
  doctor                     Diagnoses the site configuration
  edit                       [open] Open pages directory with the editor
  help                       Display help for a command
  self-update                [selfupdate] Updates Cecil to the latest version
  serve                      Starts the built-in server
 cache
  cache:clear                Removes all cache files
  cache:clear:assets         Removes assets cache
  cache:clear:templates      Removes templates cache
  cache:clear:translations   Removes translations cache
 clear
  clear:output               Removes output directory
  clear:temporary            [clear:tmp] Removes temporary directory
 doctor
  doctor:cache               Shows cache status
  doctor:frontmatter         [doctor:fm] Validates pages front matter syntax
  doctor:links               Checks internal and external links of the rendered pages
  doctor:seo                 Audits rendered HTML pages for common SEO issues
 new
  new:page                   Creates a new page
  new:site                   Creates a new website
 serve
  serve:background           Starts the built-in server in the background (alias of `serve --background`)
  serve:log                  Shows combined server and error logs
  serve:stop                 [stop] Stops the background server
 show
  show:config                Shows the configuration
  show:content               Shows content as tree
 util
  util:templates:extract     Extracts built-in templates
  util:translations:extract  Extracts translations from templates
```

:::info
`self-update` et `util:templates:extract` ne sont disponibles que lorsque Cecil est exécuté depuis le PHAR (`self-update` n’est pas disponible dans le [binaire autonome](../getting-started/2-installation.fr.md#standalone-binary)).

Exécutez `cecil help <commande>` pour afficher les options d’une commande.
:::

## Autres commandes

Commandes sans page dédiée, et leurs options spécifiques :

- `about` : affiche une courte description de Cecil.
- `edit` (alias `open`) : ouvre le répertoire des pages avec l’éditeur défini par la clé de configuration `editor`, ou avec `--editor=EDITOR`.
- `self-update` (alias `selfupdate`) : met à jour Cecil vers la dernière version, voir [Installation](../getting-started/2-installation.fr.md#update).
- `serve:stop` (alias `stop`) : arrête un serveur en arrière-plan précédemment démarré avec `serve --background`.
- `clear` : supprime tous les fichiers générés ; `clear:output` supprime le répertoire de sortie et `clear:temporary` (alias `clear:tmp`) supprime le répertoire temporaire.
- `cache:clear` : supprime tous les fichiers de cache ; `cache:clear:assets`, `cache:clear:templates` et `cache:clear:translations` suppriment un seul cache. `cache:clear:templates --fragments` supprime uniquement le cache des fragments de templates.
- `show:config` : affiche la configuration au format YAML (`-k, --key=KEY` affiche uniquement la clé donnée, en notation pointée, par ex. `--key=pages.dir`) ; `show:content` affiche le contenu sous forme d’arborescence. Les deux acceptent `-c, --config=CONFIG` pour ajouter un fichier de configuration supplémentaire.
- `util:templates:extract` : extrait les templates intégrés dans le répertoire `layouts` (`-f, --force` écrase les fichiers existants).
- `util:translations:extract` : extrait les traductions des templates, avec les options suivantes :
  - `--locale=LOCALE` : définit la locale
  - `--show` : affiche les messages de traduction dans la console, sous forme de liste
  - `--save` : enregistre les messages de traduction dans le fichier de traduction
  - `--format=FORMAT` : définit le format de sortie (`yaml` par défaut, ou `po`)
  - `--theme=THEME` : fusionne les messages de traduction d’un thème donné
