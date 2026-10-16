<!--
title: "doctor"
description: "Diagnose the configuration, front matter, SEO and links."
date: 2020-12-19
updated: 2026-10-16
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
  path                  Use the given path as working directory

Options:
  -c, --config=CONFIG   Set the path to an extra configuration file
  -h, --help            Display help for the given command. When no command is given display help for the list command
      --silent          Do not output any message
  -q, --quiet           Only errors are displayed. All other output is suppressed
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The doctor command diagnoses the current site and Cecil environment.

    cecil.phar doctor
    cecil.phar doctor path/to/the/working/directory

  To inspect a site with an extra configuration file, run:

    cecil.phar doctor --config=config.yml
```

## doctor:cache

Shows the cache settings (enabled caches, directory) and usage (number of files and size of each cache pool).

```plaintext
Description:
  Shows cache status

Usage:
  doctor:cache [<path>]

Arguments:
  path                  Use the given path as working directory

Options:
  -h, --help            Display help for the given command. When no command is given display help for the list command
      --silent          Do not output any message
  -q, --quiet           Only errors are displayed. All other output is suppressed
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The doctor:cache command shows cache settings and usage details.
```

## doctor:frontmatter

Validates pages front matter syntax.

```plaintext
Description:
  Validates pages front matter syntax

Usage:
  doctor:frontmatter [options] [--] [<path>]
  doctor:fm

Arguments:
  path                  Use the given path as working directory

Options:
  -c, --config=CONFIG   Set the path to an extra configuration file
  -p, --page=PAGE       Validate a single page relative to the pages directory
  -h, --help            Display help for the given command. When no command is given display help for the list command
      --silent          Do not output any message
  -q, --quiet           Only errors are displayed. All other output is suppressed
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The doctor:frontmatter command validates front matter syntax for pages.

    cecil.phar doctor:frontmatter
    cecil.phar doctor:frontmatter path/to/the/working/directory

  To validate a single page, run:

    cecil.phar doctor:frontmatter --page=blog/post.md

  To inspect a site with an extra configuration file, run:

    cecil.phar doctor:frontmatter --config=config.yml
```

## doctor:seo

Audits rendered HTML pages for common SEO issues.

```plaintext
Description:
  Audits rendered HTML pages for common SEO issues

Usage:
  doctor:seo [options] [--] [<path>]

Arguments:
  path                   Use the given path as working directory

Options:
  -c, --config=CONFIG    Set the path to an extra configuration file
  -p, --page=PAGE        Audit a single page relative to the pages directory
      --format=FORMAT    Output format: text (default) or json [default: "text"]
      --include-virtual  Include virtual pages (paginated, taxonomies) in audit
      --feedback         Include findings with feedback level
      --fail-on=FAIL-ON  Return a failure exit code when findings of this level or higher are found: bad (default), ok, feedback or never [default: "bad"]
  -h, --help             Display help for the given command. When no command is given display help for the list command
      --silent           Do not output any message
  -q, --quiet            Only errors are displayed. All other output is suppressed
  -V, --version          Display this application version
      --ansi|--no-ansi   Force (or disable --no-ansi) ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The doctor:seo command builds the site in dry-run mode, then audits
  the rendered HTML output for a focused set of SEO checks.

    cecil.phar doctor:seo
    cecil.phar doctor:seo path/to/the/working/directory

  To audit a single page, run:

    cecil.phar doctor:seo --page=blog/post.md

  To output results as JSON for CI integration:

    cecil.phar doctor:seo --format=json

  To include virtual pages (paginated, taxonomy pages):

    cecil.phar doctor:seo --include-virtual

  To include findings with feedback level:

    cecil.phar doctor:seo --feedback

  By default, the command fails if findings with the bad level are found.
  To change this behavior, use the --fail-on option:

    cecil.phar doctor:seo --fail-on=ok
    cecil.phar doctor:seo --fail-on=never

  To inspect a site with an extra configuration file, run:

    cecil.phar doctor:seo --config=config.yml

  Configure audit thresholds and checks in your configuration:

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

The command builds the site in dry-run mode, then audits the rendered HTML output for a focused set of checks: title tag, meta description, canonical URL, heading structure, Open Graph tags, image alt attributes, estimated content length and `lang` attribute of the `<html>` element.

By default, virtual pages (paginated, taxonomy pages) are excluded from the audit. Use `--include-virtual` to include them.

By default, findings with level `feedback` are not listed.

Use `--feedback` to include findings with level `feedback` in addition to other findings.

Output results as JSON for CI integration using `--format=json`.

By default, the command returns a failure exit code (`1`) when findings with level `bad` are found. Use `--fail-on=ok` to also fail on `ok` findings, `--fail-on=feedback` to fail on any finding, or `--fail-on=never` to always succeed.

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

## doctor:links

Checks internal and external links of the rendered pages.

```plaintext
Description:
  Checks internal and external links of the rendered pages

Usage:
  doctor:links [options] [--] [<path>]

Arguments:
  path                           Use the given path as working directory

Options:
  -c, --config=CONFIG            Set the path to an extra configuration file
  -p, --page=PAGE                Check links of a single page relative to the pages directory
      --internal                 Check internal links
      --external                 Check external links
      --exclude=EXCLUDE          Exclude links matching this regular expression (multiple values allowed)
      --timeout=TIMEOUT          External links request timeout, in seconds
      --concurrency=CONCURRENCY  Number of concurrent external links requests
      --no-cache                 Do not use cached external links results
      --format=FORMAT            Output format: text (default) or json [default: "text"]
      --include-virtual          Include virtual pages (paginated, taxonomies) in audit
      --feedback                 Include findings with feedback level
      --fail-on=FAIL-ON          Return a failure exit code when findings of this level or higher are found: bad (default), ok, feedback or never [default: "bad"]
  -h, --help                     Display help for the given command. When no command is given display help for the list command
      --silent                   Do not output any message
  -q, --quiet                    Only errors are displayed. All other output is suppressed
  -V, --version                  Display this application version
      --ansi|--no-ansi           Force (or disable --no-ansi) ANSI output
  -n, --no-interaction           Do not ask any interactive question
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The doctor:links command builds the site in dry-run mode, then checks the links
  found in the rendered HTML pages (links, images, scripts, stylesheets, media, inline
  styles) and in the linked CSS files (url() and @import).

    cecil.phar doctor:links
    cecil.phar doctor:links path/to/the/working/directory

  By default, only internal links are checked. To check external links too, run:

    cecil.phar doctor:links --internal --external

  To check external links only, run:

    cecil.phar doctor:links --external

  To check links of a single page, run:

    cecil.phar doctor:links --page=blog/post.md

  To exclude links matching a regular expression, run:

    cecil.phar doctor:links --external --exclude="linkedin\.com" --exclude="^https://localhost"

  To output results as JSON for CI integration:

    cecil.phar doctor:links --format=json

  By default, the command fails if findings with the bad level are found.
  To change this behavior, use the --fail-on option:

    cecil.phar doctor:links --fail-on=ok
    cecil.phar doctor:links --fail-on=never

  Configure default settings in your configuration:

    doctor:
      links:
        internal: true
        external: false
        timeout: 10
        concurrency: 10
        cache_ttl: 86400
        exclude: ['linkedin\.com']
```

The command builds the site in dry-run mode, then checks the links found in the rendered HTML pages: links (`<a>`, `<area>`, `<link>`), images (`src` and `srcset`), scripts, iframes, media and inline styles. CSS files linked from the pages are also analyzed (`url()` and `@import`).

- **Internal links** must target a generated page, a static file or an asset. Fragments (`#anchor`) must match an element ID of the target page. Absolute URLs starting with `baseurl` are considered internal links.
- **External links** are requested over HTTP (`HEAD`, then `GET` if the server refuses `HEAD`), concurrently. Successful results are cached (24 hours by default): use `--no-cache` to check them again.

By default, only internal links are checked:

```bash
php cecil.phar doctor:links                       # internal links (default)
php cecil.phar doctor:links --external            # external links only
php cecil.phar doctor:links --internal --external # both
```

Finding levels:

| Level      | Meaning                                                                                           |
|------------|---------------------------------------------------------------------------------------------------|
| `bad`      | Broken link: target not found, missing anchor, HTTP error (4xx, 5xx), unresolved host, etc.       |
| `ok`       | Working link that could be improved: redirection, missing trailing slash                          |
| `feedback` | Unverifiable link: access denied (401, 403, 429, 999), probably due to bot protection, or timeout |

Links using schemes other than HTTP(S) (e.g. `mailto:`, `tel:`, `data:`) are ignored. Use `--exclude` (regular expression, case-insensitive) to ignore other links.

The `--page`, `--include-virtual`, `--feedback`, `--format` and `--fail-on` options behave as with `doctor:seo` (see above).

:::info
Checking external links requires the PHP cURL extension.
:::

### Configuration

Customize default settings in your configuration file:

```yaml
doctor:
  links:
    internal: true    # check internal links
    external: false   # check external links
    timeout: 10       # external links request timeout, in seconds
    concurrency: 10   # number of concurrent external links requests
    cache_ttl: 86400  # external links successful results cache TTL, in seconds
    exclude: []       # links to ignore (list of regular expressions), e.g. ['linkedin\.com', '^https://localhost']
    useragent: ~      # User-Agent header of external links requests (`assets.remote.useragent.default` by default)
```
