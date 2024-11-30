<!--
description: "List of available commands."
date: 2020-12-19
updated: 2024-01-30
-->
# Commands

List of available commands.

```plaintext
Available commands:
  build                     Builds the website
  clear                     Removes generated files
  help                      Display help for a command
  open                      Open pages directory with the editor
  self-update               Updates Cecil to the latest version
  serve                     Starts the built-in server
 cache
  cache:clear               Removes all caches
  cache:clear:assets        Removes assets cache
  cache:clear:templates     Removes templates cache
  cache:clear:translations  Removes translations cache
 new
  new:page                  Creates a new page
  new:site                  Creates a new website
 show
  show:config               Shows the configuration
  show:content              Shows content as tree
 util
  util:templates:extract    Extracts built-in templates
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
  Creates a new website in the current directory, or in <path> if provided
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
      --name=NAME       Page path name
  -p, --prefix          Prefix the file name with the current date (`YYYY-MM-DD`)
  -f, --force           Override the file if already exist
  -o, --open            Open editor automatically
      --editor=EDITOR   Editor to use with open option
  -h, --help            Display help for the given command. When no command is given display help for the list command
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Creates a new page file (with filename as title)
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
      --optimize[=OPTIMIZE]        Optimize files (disable with "no") [default: false]
      --clear-cache[=CLEAR-CACHE]  Clear cache before build (optional cache key regular expression) [default: false]
      --show-pages                 Show built pages as table
  -h, --help                       Display help for the given command. When no command is given display help for the list command
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi|--no-ansi             Force (or disable --no-ansi) ANSI output
  -n, --no-interaction             Do not ask any interactive question
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Builds the website in the output directory
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
      --optimize[=OPTIMIZE]        Optimize files (disable with "no") [default: false]
      --clear-cache[=CLEAR-CACHE]  Clear cache before build (optional cache key regular expression) [default: false]
      --no-ignore-vcs              Changes watcher must not ignore VCS directories
  -h, --help                       Display help for the given command. When no command is given display help for the list command
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi|--no-ansi             Force (or disable --no-ansi) ANSI output
  -n, --no-interaction             Do not ask any interactive question
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Starts the live-reloading-built-in web server
```
