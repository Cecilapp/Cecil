<!--
title: "Content for LLMs"
description: "Render a llms.txt file and Markdown versions of your pages for language models."
date: 2026-10-22
updated: 2026-10-23
-->
# Content for LLMs

Language models read Markdown more easily than HTML. Cecil provides two [output formats](../configuration/8-output.md#default-formats) for them:

- `llms`: a [`llms.txt`](https://llmstxt.org) file, an index of the site;
- `markdown`: a Markdown version of each page.

Both are disabled by default.

## llms.txt

Since version ++8.114.0++, the `llms` format renders the homepage as a `llms.txt` file at the root of the site. It contains:

- the site title and description;
- the links of the main menu (external URLs are ignored);
- the top-level sections and their pages (sub-sections are nested), with their title and description.

To enable it, add the format to the homepage:

```yaml
output:
  pagetypeformats:
    homepage:
      llms: true
```

With the [metatags](../configuration/1-site.md#metatags) template, the HTML homepage references the file with a `<link rel="describedby" type="text/markdown">` tag.

### HTTP headers

You can also advertise the file to crawlers and agents with HTTP headers, using the [`server.headers`](../configuration/10-server.md#serverheaders) option:

```yaml
server:
  headers:
    - path: /*
      headers:
        # LLMs
        - key: Link
          value: '</llms.txt>; rel="llms-txt"'
        - key: X-Llms-Txt
          value: /llms.txt
```

These headers are only applied by the local preview server. To deploy them, use the [_Headers_ component theme](https://github.com/Cecilapp/theme-headers): it generates a `_headers` file (supported by Netlify, statichost and Cloudflare Pages) including the headers defined in `server.headers`.

### Exclude content

To exclude a page or a section (and its pages) from `llms.txt`, set `llms: false` in its front matter:

```yaml
---
title: "Legal notice"
llms: false
---
```

Unpublished and [excluded](../content/2-front-matter.md#excluded) sections are not listed.

## Markdown pages

The `markdown` format renders a page as a Markdown file, next to its HTML version (e.g.: `blog/post-1.md` for `blog/post-1/index.html`). The file contains the title and the body of the page; a section lists its pages.

To render all pages and sections in Markdown:

```yaml
output:
  pagetypeformats:
    page:
      markdown: true
    section:
      markdown: true
```

Or for a single page, with the [`output`](../content/2-front-matter.md#output) variable:

```yaml
---
output: [html, markdown]
---
```

With the [metatags](../configuration/1-site.md#metatags) template, the HTML page references its Markdown version with a `<link rel="alternate" type="text/markdown">` tag. The links of `llms.txt` point to the Markdown version of the pages when it exists.

### Prevent indexing

To prevent search engines from indexing the Markdown pages (duplicate content of the HTML pages), add a `X-Robots-Tag` HTTP header with the [`server.headers`](../configuration/10-server.md#serverheaders) option:

```yaml
server:
  headers:
    # Markdown outputs
    - path: "/*.md"
      headers:
        - key: X-Robots-Tag
          value: noindex
```

As for the [`llms.txt` headers](#http-headers), use the [_Headers_ component theme](https://github.com/Cecilapp/theme-headers) to deploy it.

## Templates

To customize these outputs, override the default templates in your `layouts` directory (see [lookup rules](../templates/1-lookup-rules.md)):

- `home.llms.twig`: the `llms.txt` file;
- `page.markdown.twig`, `list.markdown.twig` and `home.markdown.twig`: the Markdown pages.
