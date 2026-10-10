<!--
title: "build"
description: "Générer le site."
date: 2026-03-27
updated: 2026-10-10
path: documentation/commandes/build
-->
# build

Construit le site.

## Utilisation

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
