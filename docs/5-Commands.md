<!--
description: "List of available commands."
date: 2020-12-19
updated: 2025-05-20
-->
# Commands

List of available commands.

```plaintext
Available commands:
  about                      Shows a short description about Cecil
  build                      Builds the website
  clear                      Removes generated files
  help                       Display help for a command
  open                       Open pages directory with the editor
  self-update                Updates Cecil to the latest version
  serve                      Starts the built-in server
 cache
  cache:clear                Removes all caches
  cache:clear:assets         Removes assets cache
  cache:clear:templates      Removes templates cache
  cache:clear:translations   Removes translations cache
 new
  new:page                   Creates a new page
  new:site                   Creates a new website
 show
  show:config                Shows the configuration
  show:content               Shows content as tree
 util
  util:templates:extract     Extracts built-in templates
  util:translations:extract  Extracts translations from templates
```

## new:site

Creates a new site.

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

  To create a new website, run:

    cecil.phar new:site

  To create a new website in a specific directory, run:

    cecil.phar new:site path/to/directory

  To create a new website with demo content, run:

    cecil.phar new:site --demo

  To override an existing website, run:

    cecil.phar new:site --force
```

## new:page

Creates a new page.

```plaintext
Description:
  Creates a new page

Usage:
  new:page [options] [--] [<path>]

Arguments:
  path                  Use the given path as working directory

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

  To create a new page, run:

    cecil.phar new:page

  To create a new page with a specific name, run:

    cecil.phar new:page --name=path/to/a-page.md

  To slugify the file name, run:

    cecil.phar new:page --name=path/to/A Page.md --slugify

  To create a new page with a date prefix (i.e: YYYY-MM-DD), run:

    cecil.phar new:page --prefix

  To create a new page and open it with an editor, run:

    cecil.phar new:page --open

  To create a new page and open it with a specific editor, run:

    cecil.phar new:page --open --editor=editor

  To override an existing page, run:

    cecil.phar new:page --force
```

### Page’s models

You can define your own models for your new pages in the `models` directory:

1. The name must be based on the section’s name (e.g.: `blog.md`)
2. The default model must be named `default.md` (for root pages or pages’s section without model)

Two dynamic variables are available:

1. `%title%`: the file’s name
2. `%date%`: the curent date

### Open with your editor

With the `--open` option, the editor will be opened automatically. So use `editor` key in your configuration file to define the default editor (e.g.: `editor: typora`).

## build

Builds the site.

```plaintext
Description:
  Builds the website

Usage:
  build [options] [--] [<path>]

Arguments:
  path                             Use the given path as working directory

Options:
  -c, --config=CONFIG              Set the path to extra config files (comma-separated)
  -d, --drafts                     Include drafts
  -p, --page=PAGE                  Build a specific page
      --dry-run                    Build without saving
      --baseurl=BASEURL            Set the base URL
      --output=OUTPUT              Set the output directory
      --optimize|--no-optimize     Optimize files (or disable --no-optimize)
      --clear-cache[=CLEAR-CACHE]  Clear cache before build (optional cache key regular expression) [default: false]
      --show-pages                 Show built pages as table
      --metrics                    Show build steps metrics
  -h, --help                       Display help for the given command. When no command is given display help for the list command
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi|--no-ansi             Force (or disable --no-ansi) ANSI output
  -n, --no-interaction             Do not ask any interactive question
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The build command generates the website in the output directory.

  To build the website, run:

    cecil.phar build

  To build the website from a specific directory, run:

    cecil.phar build path/to/directory

  To build the website with a specific configuration file, run:

    cecil.phar build --config=config.yml

  To build the website with drafts, run:

    cecil.phar build --drafts

  To build the website without saving, run:

    cecil.phar build --dry-run

  To build the website with a specific page, run:

    cecil.phar build --page=page-id

  To build the website with a specific base URL, run:

    cecil.phar build --baseurl=https://example.com/

  To build the website with a specific output directory, run:

    cecil.phar build --output=_site

  To build the website with optimization, run:

    cecil build --optimize

  To build the website without optimization, run:

    cecil build --no-optimize

  To clear the cache before building the website, run:

    cecil.phar build --clear-cache

  To clear the cache before building the website with a specific cache key regular expression, run:

    cecil.phar build --clear-cache=cache-key

  To show built pages as table, run:

    cecil.phar build --show-pages

  To show build steps metrics, run:

    cecil.phar build --metrics
```

## serve

Builds and serves the site locally.

:::warning
The web server is designed to aid website testing. It is not intended to be a full-featured web server and it should not be used on a public network.
:::

```plaintext
Description:
  Starts the built-in server

Usage:
  serve [options] [--] [<path>]

Arguments:
  path                             Use the given path as working directory

Options:
  -c, --config=CONFIG              Set the path to extra config files (comma-separated)
  -d, --drafts                     Include drafts
  -p, --page=PAGE                  Build a specific page
  -o, --open                       Open web browser automatically
      --host=HOST                  Server host
      --port=PORT                  Server port
      --optimize|--no-optimize     Optimize files (or disable --no-optimize)
      --clear-cache[=CLEAR-CACHE]  Clear cache before build (optional cache key regular expression) [default: false]
      --no-ignore-vcs              Changes watcher must not ignore VCS directories
      --timeout[=TIMEOUT]          Sets the process timeout (max. runtime) in seconds
      --metrics                    Show build steps metrics
  -h, --help                       Display help for the given command. When no command is given display help for the list command
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi|--no-ansi             Force (or disable --no-ansi) ANSI output
  -n, --no-interaction             Do not ask any interactive question
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The serve command starts the live-reloading-built-in web server.

  To start the server, run:

    cecil.phar serve

  To start the server from a specific directory, run:

    cecil.phar serve path/to/directory

  To start the server with a specific configuration file, run:

    cecil.phar serve --config=config.yml

  To start the server and open web browser automatically, run:

    cecil.phar serve --open

  To start the server with a specific host, run:

    cecil.phar serve --host=127.0.0.1

  To start the server with a specific port, run:

    cecil.phar serve --port=8080

  To build the website with optimization, run:

    cecil serve --optimize

  To build the website without optimization, run:

    cecil serve --no-optimize

  To clear the cache before building the website, run:

    cecil serve --clear-cache

  To clear the cache before building the website with a specific cache key regular expression, run:

    cecil serve --clear-cache=cache-key

  To start the server with changes watcher not ignoring VCS directories, run:

    cecil.phar serve --no-ignore-vcs

  To define the process timeout (in seconds), run:

    cecil.phar serve --timeout=3600

  To show build steps metrics, run:

    cecil.phar serve --metrics
```
