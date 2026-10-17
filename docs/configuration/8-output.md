<!--
title: "Output"
description: "Output directory, formats and post-processing."
date: 2021-05-07
updated: 2026-10-17
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
      mediatype: <media type> # media type (MIME type), e.g.: `text/html` (required)
      rel: <rel>              # `rel` attribute of the page alternate link, e.g.: `canonical` (optional, `alternate` by default)
      subpath: <sub path>     # sub path, e.g.: `amp` in `path/amp/index.html` (optional)
      filename: <file name>   # file name, e.g.: `index` in `path/index.html` (optional)
      extension: <extension>  # file extension, e.g.: `html` in `path/index.html` (required)
      exclude: [<variable>]   # don’t apply this format to pages identified by listed variables, e.g.: `[redirect, paginated]` (optional)
```

Those formats are used in the [`output.pagetypeformats`](#output-pagetypeformats) configuration and in the [`output` page variable](../content/2-front-matter.md#output).

### Default formats

Cecil provides some [default formats](https://github.com/Cecilapp/Cecil/blob/main/config/base.php#L89-L197), which can be overridden in the configuration file: `html` (default), `atom`, `rss`, `json`, `xml`, `txt`, `amp`, `js`, `webmanifest`, `xsl`, `jsonfeed`, `oembed`, `embed`, `markdown`, `llms`.

Formats are merged by `name`: to override a default format, only define its `name` and the properties to change.

```yaml
output:
  formats:
    - name: rss
      filename: feed # `feed.xml` instead of `rss.xml`
```

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

Several formats can be defined for a single page type. For example, the `section` page type can be automatically rendered in HTML and Atom:

```yaml
output:
  pagetypeformats:
    section: [html, atom]
```

A list replaces the default formats of the page type (and is added to the formats defined by the theme). To add or remove a format instead, use a [map of booleans](index.md#lists):

```yaml
output:
  pagetypeformats:
    homepage:
      rss: true   # adds RSS
      atom: false # removes Atom
```

:::info
To render a page, [Cecil looks up a template](../templates/1-lookup-rules.md#lookup-rules) named `<layout>.<format>.twig` (e.g. `page.html.twig`).
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
      mediatype: application/atom+xml
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

## output.postprocessors

List of output post processors classes, used to modify the output files after they have been generated.

Default post processors:

```yaml
output:
  postprocessors:
    GeneratorMetaTag: 'Cecil\Renderer\PostProcessor\GeneratorMetaTag'
    HtmlExcerpt: 'Cecil\Renderer\PostProcessor\HtmlExcerpt'
    MarkdownLink: 'Cecil\Renderer\PostProcessor\MarkdownLink'
```

:::tip
You can extend Cecil capabilities with an [Output post processor](../developers/1-extend.md#output-post-processors).
:::

---
