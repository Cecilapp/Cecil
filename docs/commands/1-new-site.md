<!--
title: "new:site"
description: "Create a new website."
date: 2020-12-19
updated: 2026-10-24
-->
# new:site

Creates a new site.

## Usage

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
      --silent          Do not output any message
  -q, --quiet           Only errors are displayed. All other output is suppressed
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The new:site command creates a new website in the current directory, or in <path> if provided.
  If you run this command without any options, it will ask you for the website title, baseline, base URL, description, author, language, etc.

    cecil.phar new:site
    cecil.phar new:site path/to/the/working/directory

  To create a new website with demo content, run:

    cecil.phar new:site --demo

  To override an existing website, run:

    cecil.phar new:site --force
```

## Created files

The command creates the following files:

- `cecil.yml`: the configuration, with commented examples of common options
- `pages/index.md`: the home page
- `assets/favicon.png` and `assets/icon.png`: the favicon and the default image of social networks cards
- `README.md` and `.gitignore` (`_site/`, `.cache/` and `.cecil/`), if they don't already exist
- `.github/workflows/build-and-deploy.yml`: a GitHub Actions workflow to [deploy on GitHub Pages](../deploy/2-continuous-deployment.md#github-pages), if requested

With the `--demo` option, it also adds demo content: a blog (with tags, pagination, a draft and a French translation), a documentation section (sorted by weight, with a table of contents enabled by `cascade`), custom templates, a Sass stylesheet, a data file and translations.
