<!--
title: "Output"
description: "Output directory, formats and post-processing."
date: 2021-05-07
updated: 2026-10-05
-->
# Output

Defines where and in what format pages are rendered.

## output.dir

Directory where rendered pages’ files are saved (`_site` by default).

```yaml
output:
  dir: _site
```

## output.formats

List of output formats definition, which are used to render pages (e.g. HTML, Atom, RSS, JSON, XML, etc.).

```yaml
output:
  formats:
    - name: <name>            # name of the format, e.g.: `html` (required)
      mediatype: <media type> # media type (MIME type), ie: 'text/html' (optional)
      subpath: <sub path>     # sub path, e.g.: `amp` in `path/amp/index.html` (optional)
      filename: <file name>   # file name, e.g.: `index` in `path/index.html` (optional)
      extension: <extension>  # file extension, e.g.: `html` in `path/index.html` (required)
      exclude: [<variable>]   # don’t apply this format to pages identified by listed variables, e.g.: `[redirect, paginated]` (optional)
```

Those formats are used in the [`output.pagetypeformats`](#output-pagetypeformats) configuration and in the [`output` page variable](../content/6-front-matter.md#output).

### Default formats

Cecil provides some [default formats](https://github.com/Cecilapp/Cecil/blob/main/config/base.php#L81-L162), which can be overridden in the configuration file: `html` (default), `atom`, `rss`, `json`, `xml`, `txt`, `amp`, `js`, `webmanifest`, `xsl`, `jsonfeed`, `iframe`, `oembed`.

## output.pagetypeformats

It’s not required to set `output` variable for each page, as Cecil automatically applies the formats defined for each page type (`homepage`, `page`, `section`, `vocabulary` and `term`).

```yaml
output:
  pagetypeformats:
    page: [<format>]
    homepage: [<format>]
    section: [<format>]
    vocabulary: [<format>]
    term: [<format>]
```

Several formats can be defined for the one type of page. For example the `section` page type can be automatically rendered in HTML and Atom:

```yaml
output:
  pagetypeformats:
    section: [html, atom]
```

:::info
To render a page, [Cecil lookup for a template](../templates/10-lookup-rules.md#lookup-rules) named `<layout>.<format>.twig` (e.g. `page.html.twig`)
:::

## output example

```yaml
output:
  dir: _site
  formats:
    - name: html
      mediatype: text/html
      filename: index
      extension: html
    - name: atom
      mediatype: application/xml
      filename: atom
      extension: xml
      exclude: [redirect, paginated]
  pagetypeformats:
    page: [html]
    homepage: [html, atom]
    section: [html, atom]
    vocabulary: [html]
    term: [html, atom]
```

## Post process

You can extend Cecil capabilities with an [Output post processor](../developers/41-extend.md#output-post-processor) to modify the output files after they have been generated.

---
