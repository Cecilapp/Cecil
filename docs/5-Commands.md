<!--
description: "List of available commands."
date: 2020-12-19
updated: 2021-12-08
-->

# Commands

List of all available commands.

```plaintext
Available commands:
  build                  Builds the website
  clear                  [clean] Removes generated files
  help                   Display help for a command
  open                   Open content directory with the editor
  self-update            Updates Cecil to the latest version
  serve                  Starts the built-in server
 cache
  cache:clear            Removes all caches
  cache:clear:assets     Removes assets cache
  cache:clear:templates  Removes templates cache
 new
  new:page               Creates a new page
  new:site               Creates a new website
 show
  show:config            Shows the configuration
  show:content           Shows content as tree
```

## Main commands

### new:site

Creates a new skeleton site.

```plaintext
Description:
  Creates a new website

Usage:
  new:site [options] [--] [<path>]

Arguments:
  path                  Use the given path as working directory

Options:
  -f, --force           Override the directory if already exist
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Creates a new website in the current directory, or in <path> if provided
```

### new:page

Creates a new page.

```plaintext
Description:
  Creates a new page

Usage:
  new:page [options] [--] <name> [<path>]

Arguments:
  name                  New page name
  path                  Use the given path as working directory

Options:
  -f, --force           Override the file if already exist
  -o, --open            Open editor automatically
  -p, --prefix          Prefix the file name with the current date (`YYYY-MM-DD`)
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Creates a new page file (with filename as title)
```

#### Page’s models

You can define your own models for your new pages in the `models` directory:

1. The name must be based on the section’s name (e.g.: `blog.md`)
2. The default model must be named `default.md` (for root pages or pages’s section without model)

Two dynamic variables are available:

1. `%title%`: the file’s name
2. `%date%`: the curent date

#### Open with your editor

With the `--open` option, the editor will be opened automatically. So use `editor` key in your configuration file to define the default editor (e.g.: `editor: typora`).

### build

Build the site.

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
      --postprocess[=POSTPROCESS]  Post-process output (disable with "no") [default: false]
      --clear-cache                Clear cache before build
  -h, --help                       Display this help message
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi                       Force ANSI output
      --no-ansi                    Disable ANSI output
  -n, --no-interaction             Do not ask any interactive question
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Builds the website in the output directory
```

### serve

Builds and serves the site locally.

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
      --postprocess[=POSTPROCESS]  Post-process output (disable with "no") [default: false]
      --clear-cache                Clear cache before build
  -h, --help                       Display this help message
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi                       Force ANSI output
      --no-ansi                    Disable ANSI output
  -n, --no-interaction             Do not ask any interactive question
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Starts the live-reloading-built-in web server
```
