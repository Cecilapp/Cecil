<!--
title: "new:extension"
description: "Create the skeleton of an extension: generator, Twig extension, post-processor, event subscriber or plugin."
date: 2026-10-26
updated: 2026-10-26
-->
# new:extension

Creates the skeleton of an [extension](../developers/1-extend.md) class in the `extensions` directory, and displays the configuration to enable it.

## Usage

```plaintext
Description:
  Creates a new extension

Usage:
  new:extension [options] [--] [<path>]

Arguments:
  path                       Use the given path as working directory

Options:
  -t, --type=TYPE            Extension type (generator, twig, postprocessor, subscriber, plugin)
      --name=NAME            Extension class name (e.g.: "MyGenerator")
      --namespace=NAMESPACE  Root namespace of the extension (e.g.: "MySite")
  -f, --force                Override the file if already exist
  -h, --help                 Display help for the given command. When no command is given display help for the list command
      --silent               Do not output any message
  -q, --quiet                Only errors are displayed. All other output is suppressed
  -V, --version              Display this application version
      --ansi|--no-ansi       Force (or disable --no-ansi) ANSI output
  -n, --no-interaction       Do not ask any interactive question
  -v|vv|vvv, --verbose       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The new:extension command creates the skeleton of an extension class in the extensions directory,
  and displays the configuration to enable it.
  If you run this command without any options, it will ask you for the extension type, name and namespace.

    cecil.phar new:extension
    cecil.phar new:extension --type=generator --name=Database --namespace=MySite

  Available types: generator, twig, postprocessor, subscriber and plugin.

  To override an existing extension, run:

    cecil.phar new:extension --force
```

## Types

| Type            | Class                                                    | Configuration            |
|-----------------|----------------------------------------------------------|--------------------------|
| `generator`     | `<namespace>\Generator\<name>` (page generator)          | `pages.generators`       |
| `twig`          | `<namespace>\Twig\<name>` (Twig extension)               | `layouts.extensions`     |
| `postprocessor` | `<namespace>\PostProcessor\<name>` (output post-processor) | `output.postprocessors` |
| `subscriber`    | `<namespace>\Subscriber\<name>` (build events subscriber) | `extensions.subscribers` |
| `plugin`        | `<namespace>\<name>` (plugin)                            | `extensions.plugins`     |

The class is created in `extensions/<namespace path>/`. The `Cecil` namespace is reserved and can't be used.
