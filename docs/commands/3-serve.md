<!--
title: "serve"
description: "Preview the website with the built-in server."
date: 2020-12-19
updated: 2026-10-10
-->
# serve

Builds and serves the site locally.

:::warning
The web server is designed to aid website testing. It is not intended to be a full-featured web server and it should not be used on a public network.
:::

## Usage

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
  When content pages change, Cecil rebuilds just those pages.
  When templates change, Cecil rebuilds only pages using those templates (including Twig dependencies such as extends/include).
  Any other change (data, config, static or asset file, or file deletion) triggers a full rebuild:

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

## serve:background

Alias of `serve --background`: starts the built-in server in the background without occupying the terminal.

```plaintext
Description:
  Starts the built-in server in the background (alias of `serve --background`)

Usage:
  serve:background [options] [--] [<path>]

Help:
  The serve:background command starts the built-in web server in the background.

    cecil.phar serve:background
    cecil.phar serve:background path/to/the/working/directory

  This command is an alias of serve --background.

  Stop the server with:

    cecil.phar serve:stop
```

## serve:log

Displays the combined server and error logs.

```plaintext
Description:
  Shows combined server and error logs

Usage:
  serve:log [options] [--] [<path>]

Arguments:
  path                  Use the given path as working directory

Options:
  -l, --lines=LINES     Number of entries to display (default: 25)

Help:
  The serve:log command displays entries from combined server and error logs, sorted by date.

    cecil.phar serve:log
    cecil.phar serve:log path/to/the/working/directory
    cecil.phar serve:log --lines=100
    cecil.phar serve:log -l 100

  This command shows logs from `.cecil/errors.log` and `.cecil/server.log`.
  It is useful for debugging issues with your local development server.
```
