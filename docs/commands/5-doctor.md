<!--
title: "doctor"
description: "Diagnose the configuration, front matter and SEO."
date: 2020-12-19
updated: 2026-10-10
-->
# doctor

Diagnoses the current site and Cecil environment.

## Usage

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

Validates pages front matter syntax.

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

Audits rendered HTML pages for common SEO issues.

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

The command builds the site in dry-run mode, then audits the rendered HTML output for a focused set of checks: title tag, meta description, canonical URL, heading structure, Open Graph tags, image alt attributes and estimated content length.

By default, virtual pages (paginated, taxonomy pages) are excluded from the audit. Use `--include-virtual` to include them.

By default, findings with level `feedback` are not listed.

Use `--feedback` to include findings with level `feedback` in addition to other findings.

Output results as JSON for CI integration using `--format=json`.

### Configuration

Customize audit thresholds and enabled checks in your configuration file:

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
