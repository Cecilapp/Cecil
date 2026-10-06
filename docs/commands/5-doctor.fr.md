<!--
title: "doctor"
description: "Diagnostiquer la configuration, le front matter et le SEO."
date: 2026-03-27
updated: 2026-10-02
path: documentation/commandes/doctor
-->
# doctor

Diagnostique le site courant et l'environnement Cecil.

```plaintext
Description:
  Diagnoses the site configuration

Usage:
  doctor [options] [--] [<path>]

Arguments:
  path                       Use the given path as working directory

Options:
  -c, --config=CONFIG        Set the path to an extra configuration file
  -h, --help                 Display help for the given command. When no command is given display help for the list command
  -q, --quiet                Do not output any message
  -V, --version              Display this application version
      --ansi|--no-ansi       Force (or disable --no-ansi) ANSI output
  -n, --no-interaction       Do not ask any interactive question
  -v|vv|vvv, --verbose       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The doctor command diagnoses the current site and Cecil environment.

    cecil.phar doctor
    cecil.phar doctor path/to/the/working/directory

  To inspect a site with an extra configuration file, run:

    cecil.phar doctor --config=config.yml
```

## doctor:frontmatter

Valide la syntaxe du front matter des pages.

```plaintext
Description:
  Validates pages front matter syntax

Usage:
  doctor:frontmatter|doctor:fm [options] [--] [<path>]

Arguments:
  path                  Use the given path as working directory

Options:
  -c, --config=CONFIG   Set the path to an extra configuration file
  -p, --page=PAGE       Validate a single page relative to the pages directory
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -h, --help            Display help for the given command. When no command is given display help for the list command
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
  -v|vv|vvv             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

## doctor:seo

Audite les pages HTML rendues pour détecter les problèmes SEO courants.

```plaintext
Description:
  Audits rendered HTML pages for common SEO issues

Usage:
  doctor:seo [options] [--] [<path>]

Arguments:
  path                  Use the given path as working directory

Options:
  -c, --config=CONFIG   Set the path to an extra configuration file
  -p, --page=PAGE       Audit a single page relative to the pages directory
      --format=FORMAT   Output format: text (default) or json
      --feedback        Include findings with feedback level
      --include-virtual Include virtual pages (paginated, taxonomies) in audit
```

La commande construit le site en mode dry-run, puis audite le HTML rendu avec un jeu de contrôles ciblés : balise title, meta description, URL canonique, structure des titres, balises Open Graph, attributs alt des images et longueur estimée du contenu.

Par défaut, les pages virtuelles (paginées, pages de taxonomie) sont exclues de l'audit. Utilisez `--include-virtual` pour les inclure.

Par défaut, les findings de niveau `feedback` ne sont pas listés.

Utilisez `--feedback` pour inclure les findings de niveau `feedback` en plus des autres findings.

Exportez les résultats en JSON pour l'intégration CI en utilisant `--format=json`.

### Configuration

Personnalisez les seuils d'audit et les contrôles activés dans votre fichier de configuration :

```yaml
doctor:
  seo:
    title: { min: 30, max: 60 }
    description: { min: 120, max: 160 }
    content: { min_words: 300 }
    checks:
      title: true
      description: true
      canonical: true
      h1: true
      og_tags: true
      img_alt: true
      content_length: true
      lang_attribute: true
```
