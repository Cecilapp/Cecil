<!--
title: Commandes
description: "Liste des commandes disponibles."
date: 2026-03-27
updated: 2026-06-18
slug: commandes
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
  doctor:frontmatter         [doctor:fm] Validates pages front matter syntax
  doctor:seo                 Audits rendered HTML pages for common SEO issues
 new
  new:page                   Creates a new page
  new:site                   Creates a new website
 serve
  serve:stop                 [stop] Stops the background server
 show
  show:config                Shows the configuration
  show:content               Shows content as tree
 util
  util:templates:extract     Extracts built-in templates
  util:translations:extract  Extracts translations from templates
```

## new:site

Crée un nouveau site.

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

## new:page

Crée une nouvelle page.

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

### Modèles de page

Vous pouvez définir vos propres modèles pour vos nouvelles pages dans le répertoire `models` :

1. Le nom doit être basé sur le nom de la section (par exemple : `blog.md`)
2. Le modèle par défaut doit être nommé `default.md` (pour les pages racine ou les sections de pages sans modèle)

Deux variables dynamiques sont disponibles :

1. `%title%` : le nom du fichier
2. `%date%` : la date actuelle

### Ouvrir avec votre éditeur

Avec l’option `--open`, l’éditeur s’ouvrira automatiquement. Utilisez donc la clé `editor` dans votre fichier de configuration pour définir l’éditeur par défaut (par exemple : `editor: typora`).

## serve

Construit et sert le site en local.

:::warning
Le serveur web est conçu pour faciliter les tests d’un site. Il n’a pas vocation à être un serveur web complet et ne doit pas être utilisé sur un réseau public.
:::

```plaintext
Description:
  Starts the built-in server

Usage:
  serve [options] [--] [<path>]

Arguments:
  path                             Use the given path as working directory

Options:
  -o, --open                       Open web browser automatically
      --host=HOST                  Server host [default: "localhost"]
      --port=PORT                  Server port [default: "8000"]
  -w, --watch|--no-watch           Enable (or disable --no-watch) changes watcher (enabled by default)
    -i, --incremental                Enable incremental builds (rebuild only changed pages)
  -d, --drafts                     Include drafts
      --optimize|--no-optimize     Enable (or disable --no-optimize) optimization of generated files
  -c, --config=CONFIG              Set the path to extra config files (comma-separated)
      --clear-cache[=CLEAR-CACHE]  Clear cache before build (optional cache key as regular expression) [default: false]
  -p, --page=PAGE                  Build a specific page
      --no-ignore-vcs              Changes watcher must not ignore VCS directories
  -m, --metrics                    Show build metrics (duration and memory) of each step
      --timeout=TIMEOUT            Sets the process timeout (max. runtime) in seconds [default: 7200]
      --notify                     Send desktop notification on server start
  -b, --background                 Run the server in the background
  -h, --help                       Display help for the given command. When no command is given display help for the list command
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi|--no-ansi             Force (or disable --no-ansi) ANSI output
  -n, --no-interaction             Do not ask any interactive question
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The serve command starts the live-reloading-built-in web server.

    cecil.phar serve
    cecil.phar serve path/to/the/working/directory
    cecil.phar serve --open
    cecil.phar serve --drafts
    cecil.phar serve --no-watch

  To speed up local development you can enable incremental builds with the --incremental option.
  When only content pages change, Cecil rebuilds just those pages instead of the whole website;
  any other change (layout, data, config, theme, static or asset file) triggers a full rebuild:

    cecil.phar serve --incremental

  You can use a custom host and port by using the --host and --port options:

    cecil.phar serve --host=127.0.0.1 --port=8080

  To build the website with an extra configuration file, you can use the --config option.
  This is useful during local development to override some settings without modifying the main configuration:

    cecil.phar serve --config=config/dev.yml

  To start the server with changes watcher not ignoring VCS directories, run:

    cecil.phar serve --no-ignore-vcs

  To define the process timeout (in seconds), run:

    cecil.phar serve --timeout=7200

  To run the server in the background, run:

    cecil.phar serve --background
    cecil.phar serve -b

  Then stop it with:

    cecil.phar serve:stop

  In background mode, file changes are not watched automatically.
```

## build

Construit le site.

```plaintext
Description:
  Builds the website

Usage:
  build [options] [--] [<path>]

Arguments:
  path                               Use the given path as working directory

Options:
  -d, --drafts                       Include drafts
  -u, --baseurl=BASEURL              Set the base URL
  -o, --output=OUTPUT                Set the output directory
      --optimize|--no-optimize       Enable (or disable --no-optimize) optimization of generated files
      --dry-run                      Build without saving
  -c, --config=CONFIG                Set the path to extra config files (comma-separated)
      --clear-cache[=CLEAR-CACHE]    Clear cache before build (optional cache key as regular expression) [default: false]
  -p, --page=PAGE                    Build a specific page
      --render-subset=RENDER-SUBSET  Render a subset of pages
      --show-pages                   Show list of built pages in a table
  -m, --metrics                      Show build metrics (duration and memory) of each step
      --notify                       Send desktop notification on build completion
  -h, --help                         Display help for the given command. When no command is given display help for the list command
  -q, --quiet                        Do not output any message
  -V, --version                      Display this application version
      --ansi|--no-ansi               Force (or disable --no-ansi) ANSI output
  -n, --no-interaction               Do not ask any interactive question
  -v|vv|vvv, --verbose               Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The build command generates the website in the output directory.

    cecil.phar build
    cecil.phar build path/to/the/working/directory
    cecil.phar build --baseurl=https://example.com/
    cecil.phar build --output=_site

  To build the website with optimization of generated files, you can use the --optimize option.
  This is useful to reduce the size of the generated files and improve performance:

    cecil.phar build --optimize
    cecil.phar build --no-optimize

  To build the website without overwriting files in the output directory, you can use the --dry-run option.
  This is useful to check what would be built without actually writing files:

    cecil.phar build --dry-run

  To build the website with a specific subset of rendered pages, you can use the --render-subset option.
  This is useful to build only a part of the website, for example, only "hot" pages or a specific section:

    cecil.phar build --render-subset=subset

  To show build steps metrics, run:

    cecil.phar build --metrics
```

## doctor

Diagnostique le site courant et l'environnement Cecil.

```plaintext
Description:
  Diagnoses the site configuration

Usage:
  doctor [options] [--] [<path>]

Arguments:
  path                       Use the given path as working directory

Options:
  -c, --config=CONFIG        Set the path to an extra configuration file
  -h, --help                 Display help for the given command. When no command is given display help for the list command
  -q, --quiet                Do not output any message
  -V, --version              Display this application version
      --ansi|--no-ansi       Force (or disable --no-ansi) ANSI output
  -n, --no-interaction       Do not ask any interactive question
  -v|vv|vvv, --verbose       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The doctor command diagnoses the current site and Cecil environment.

    cecil.phar doctor
    cecil.phar doctor path/to/the/working/directory

  To inspect a site with an extra configuration file, run:

    cecil.phar doctor --config=config.yml
```

### doctor:frontmatter

Valide la syntaxe du front matter des pages.

```plaintext
Description:
  Validates pages front matter syntax

Usage:
  doctor:frontmatter|doctor:fm [options] [--] [<path>]

Arguments:
  path                  Use the given path as working directory

Options:
  -c, --config=CONFIG   Set the path to an extra configuration file
  -p, --page=PAGE       Validate a single page relative to the pages directory
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -h, --help            Display help for the given command. When no command is given display help for the list command
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
  -v|vv|vvv             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### doctor:seo

Audite les pages HTML rendues pour détecter les problèmes SEO courants.

```plaintext
Description:
  Audits rendered HTML pages for common SEO issues

Usage:
  doctor:seo [options] [--] [<path>]

Arguments:
  path                  Use the given path as working directory

Options:
  -c, --config=CONFIG   Set the path to an extra configuration file
  -p, --page=PAGE       Audit a single page relative to the pages directory
      --format=FORMAT   Output format: text (default) or json
      --feedback        Include findings with feedback level
      --include-virtual Include virtual pages (paginated, taxonomies) in audit
```

La commande construit le site en mode dry-run, puis audite le HTML rendu avec un jeu de contrôles ciblés : balise title, meta description, URL canonique, structure des titres, balises Open Graph, attributs alt des images et longueur estimée du contenu.

Par défaut, les pages virtuelles (paginées, pages de taxonomie) sont exclues de l'audit. Utilisez `--include-virtual` pour les inclure.

Par défaut, les findings de niveau `feedback` ne sont pas listés.

Utilisez `--feedback` pour inclure les findings de niveau `feedback` en plus des autres findings.

Exportez les résultats en JSON pour l'intégration CI en utilisant `--format=json`.

#### Configuration

Personnalisez les seuils d'audit et les contrôles activés dans votre fichier de configuration :

```yaml
doctor:
  seo:
    title: { min: 30, max: 60 }
    description: { min: 120, max: 160 }
    content: { min_words: 300 }
    checks:
      title: true
      description: true
      canonical: true
      h1: true
      og_tags: true
      img_alt: true
      content_length: true
      lang_attribute: true
```
