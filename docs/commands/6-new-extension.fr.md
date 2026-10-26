<!--
title: "new:extension"
description: "Créer le squelette d’une extension : générateur, extension Twig, post-processeur, abonné aux événements ou plugin."
date: 2026-10-26
updated: 2026-10-26
path: documentation/commandes/new-extension
-->
# new:extension

Crée le squelette d’une classe d’[extension](../developers/1-extend.fr.md) dans le répertoire `extensions`, et affiche la configuration pour l’activer.

## Utilisation

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

| Type            | Classe                                                        | Configuration            |
|-----------------|---------------------------------------------------------------|--------------------------|
| `generator`     | `<namespace>\Generator\<name>` (générateur de pages)          | `pages.generators`       |
| `twig`          | `<namespace>\Twig\<name>` (extension Twig)                    | `layouts.extensions`     |
| `postprocessor` | `<namespace>\PostProcessor\<name>` (post-processeur de sortie) | `output.postprocessors` |
| `subscriber`    | `<namespace>\Subscriber\<name>` (abonné aux événements)       | `extensions.subscribers` |
| `plugin`        | `<namespace>\<name>` (plugin)                                 | `extensions.plugins`     |

La classe est créée dans `extensions/<chemin du namespace>/`. L’espace de noms `Cecil` est réservé et ne peut pas être utilisé.
