<!--
title: "Commands"
description: "Reference of Cecil CLI commands and options."
date: 2020-12-19
updated: 2026-10-16
weight: 6
sortby: weight
-->
# Commands

List of available commands.

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
`self-update` and `util:templates:extract` are only available when Cecil runs from the PHAR (`self-update` is not available in the [standalone binary](../getting-started/2-installation.md#standalone-binary)).

Run `cecil help <command>` to display the options of a command.
:::

## Other commands

Commands without a dedicated page, and their specific options:

- `about`: shows a short description about Cecil.
- `edit` (alias `open`): opens the pages directory with the editor defined by the `editor` configuration key, or with `--editor=EDITOR`.
- `self-update` (alias `selfupdate`): updates Cecil to the latest version, see [Installation](../getting-started/2-installation.md#update).
- `serve:stop` (alias `stop`): stops a background server previously started with `serve --background`.
- `clear`: removes all generated files; `clear:output` removes the output directory and `clear:temporary` (alias `clear:tmp`) removes the temporary directory.
- `cache:clear`: removes all cache files; `cache:clear:assets`, `cache:clear:templates` and `cache:clear:translations` remove a single cache. `cache:clear:templates --fragments` removes the templates fragments cache only.
- `show:config`: shows the configuration in YAML format; `show:content` shows the content as a tree. Both accept `-c, --config=CONFIG` to add an extra configuration file.
- `util:templates:extract`: extracts built-in templates into the `layouts` directory (`-f, --force` overrides existing files).
- `util:translations:extract`: extracts translations from templates, with the following options:
  - `--locale=LOCALE`: sets the locale
  - `--show`: displays translation messages in the console, as a list
  - `--save`: saves translation messages into the translation file
  - `--format=FORMAT`: sets the output format (`yaml` by default, or `po`)
  - `--theme=THEME`: merges translation messages from a given theme
