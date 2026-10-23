# AI-Friendly Sites

Outputs and options to expose the site content to LLMs and AI agents, or to block AI crawlers. These outputs are disabled by default.

## llms.txt

```yaml
output:
  pagetypeformats:
    homepage:
      llms: true
```

- Renders `/llms.txt` (site title and description, internal links of the main menu, top-level sections and their pages).
- The HTML homepage references it with `<link rel="describedby" type="text/markdown">` (through `partials/metatags.html.twig`).
- Exclude a page or a section (and its pages) with `llms: false` in its front matter; unpublished and excluded sections are not listed.

## Markdown Pages

The `markdown` format writes a Markdown version of the pages next to the HTML one (`blog/post-1.md` for `blog/post-1/index.html`), with the title and the body; a section lists its pages.

```yaml
output:
  pagetypeformats:
    page:
      markdown: true
    section:
      markdown: true
```

Or per page, in the front matter: `output: [html, markdown]`.

The HTML page references it with `<link rel="alternate" type="text/markdown">`, and the links of `llms.txt` point to the Markdown versions when they exist.

## HTTP Headers

Advertise `llms.txt`, and keep Markdown pages out of search engines (duplicate content), with `server.headers`:

```yaml
server:
  headers:
    - path: /*
      headers:
        - key: Link
          value: '</llms.txt>; rel="llms-txt"'
        - key: X-Llms-Txt
          value: /llms.txt
    - path: "/*.md"
      headers:
        - key: X-Robots-Tag
          value: noindex
```

These headers are applied **only by the local `serve` server**. To deploy them, install the [`headers` component theme](https://github.com/Cecilapp/theme-headers): it generates a `_headers` file (Netlify, statichost, Cloudflare Pages) from `server.headers`.

## Block AI Crawlers

The generated `robots.txt` allows all crawlers. Add rules with a `pages/robots.md` page (don't create `static/robots.txt`, nor a new template): its body is appended to the default rules.

```markdown
---
layout: robots
output: txt
---
User-agent: GPTBot
Disallow: /

User-agent: ClaudeBot
Disallow: /

User-agent: Google-Extended
Disallow: /
```

## Templates

Override these outputs in `layouts/` only if needed: `home.llms.twig` (`llms.txt`), `page.markdown.twig`, `list.markdown.twig` and `home.markdown.twig` (Markdown pages).

See the [LLMs](https://cecil.app/documentation/ai/llms/) and [crawlers](https://cecil.app/documentation/ai/crawlers/) documentation.
