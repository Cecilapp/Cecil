<!--
description: "List of available commands."
date: 2020-12-19
updated: 2025-06-04
-->
# Commands

List of available commands.

```plaintext
Available commands:
  about                      Shows a short description about Cecil
  build                      Builds the website
  clear                      Removes all generated files
  edit                       [open] Open pages directory with the editor
  help                       Display help for a command
  self-update                [selfupdate] Updates Cecil to the latest version
  serve                      Starts the built-in server
 cache
  cache:clear                Removes all cache files
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
  If you run this command without any options, it will ask you for the website title, baseline, base URL, description, etc.
  
    cecil.phar new:site
    cecil.phar new:site path/to/the/working/directory
  
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
  If your run this command without any options, it will ask you for the page name and others options.
  
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
    cecil.phar build --drafts
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
  -o, --open                       Open web browser automatically
      --host=HOST                  Server host [default: "localhost"]
      --port=PORT                  Server port [default: "8000"]
  -d, --drafts                     Include drafts
      --optimize|--no-optimize     Enable (or disable --no-optimize) optimization of generated files
  -c, --config=CONFIG              Set the path to extra config files (comma-separated)
      --clear-cache[=CLEAR-CACHE]  Clear cache before build (optional cache key as regular expression) [default: false]
  -p, --page=PAGE                  Build a specific page
      --no-ignore-vcs              Changes watcher must not ignore VCS directories
  -m, --metrics                    Show build metrics (duration and memory) of each step
      --timeout=TIMEOUT            Sets the process timeout (max. runtime) in seconds [default: 7200]
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

  You can use a custom host and port by using the --host and --port options:

    cecil.phar serve --host=127.0.0.1 --port=8080

  To build the website with an extra configuration file, you can use the --config option.
  This is useful during local development to override some settings without modifying the main configuration:

    cecil.phar serve --config=config/dev.yml

  To start the server with changes watcher not ignoring VCS directories, run:

    cecil.phar serve --no-ignore-vcs

  To define the process timeout (in seconds), run:

    cecil.phar serve --timeout=7200
```
