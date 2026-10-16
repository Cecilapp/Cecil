<!--
title: "new:page"
description: "Create a new page, optionally from a model."
date: 2020-12-19
updated: 2026-10-16
-->
# new:page

Creates a new page.

## Usage

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
      --silent                Do not output any message
  -q, --quiet                 Only errors are displayed. All other output is suppressed
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

## Page models

You can define your own models for your new pages in the `models` directory:

1. A section model must be named after the section (e.g. `blog.md` for pages created in `blog/`)
2. The model for root pages must be named `default.md`

A page created in a section without a model uses the built-in model.

Two dynamic variables are available:

1. `%title%`: the title, derived from the file name (without extension, dashes replaced by spaces, first letter capitalized)
2. `%date%`: the current date

## Open with your editor

With the `--open` option, the page is opened automatically with the editor defined by the `editor` key of your configuration file (e.g. `editor: typora`), or with the one given by the `--editor` option.
