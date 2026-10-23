# Writing Content

How to write the body of the pages (Markdown), and front matter features beyond the basics of [SKILL.md](../SKILL.md#front-matter).

## Contents

- [Markdown Flavor](#markdown-flavor)
- [Attributes](#attributes)
- [Links](#links)
- [Images](#images)
- [Embeds (Videos, Audio, Gists)](#embeds-videos-audio-gists)
- [Table of Contents and Excerpt](#table-of-contents-and-excerpt)
- [Notes](#notes)
- [Menus](#menus)
- [Taxonomies](#taxonomies)
- [Permalinks](#permalinks)

## Markdown Flavor

The body is [Markdown](https://daringfireball.net/projects/markdown/syntax) plus [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/) (tables, footnotes, definition lists, attribute blocks), plus `++inserted text++` → `<ins>`. Fenced code blocks are highlighted at build time (see [assets.md](assets.md#code-syntax-highlighting)).

Twig is **not** evaluated in the body by default (see [workflows.md](workflows.md#use-dynamic-content-twig-in-page-body)).

## Attributes

Put an attribute block at the end of a heading, fenced code block, link or image line:

```markdown
## Title {#my-id .my-class data-foo=bar}
```

For an inline element (link, image) followed by text, a line break must follow the closing brace:

```markdown
Read the [documentation](https://cecil.app/documentation/){.button}
and start now.
```

## Links

```markdown
[About](/about/)               <!-- path -->
[About](about.md)              <!-- relative Markdown file, resolved from the current file folder (as on GitHub) -->
[First post](page:blog/post-1) <!-- page ID -->
[Cecil](https://cecil.app)     <!-- external -->
```

Links to a Markdown file or a page ID are replaced by the page URL: prefer them to hardcoded paths.

External links (starting with `http` but not with `baseurl`, which must be set) get `rel="noopener noreferrer"`. To open them in a new tab, or add `nofollow`, configure `pages.body.links.external` rather than adding attributes by hand:

```yaml
pages:
  body:
    links:
      external:
        blank: true     # target="_blank"
        noopener: true
        noreferrer: true
        nofollow: false
```

## Images

```markdown
![Alternative text](/images/photo.jpg "Optional title")
![Alternative text](/images/photo.jpg){width=800 .wide placeholder=lqip loading=eager}
```

- Paths are relative to the site root, and resolved from `assets/` (then `static/`): `/images/photo.jpg` is `assets/images/photo.jpg`; a path relative to these directories (`../../assets/images/photo.jpg`) is normalized.
- On a translated page, a localized image (`photo.fr.jpg`) is used first, if it exists.
- `{width=800}` resizes the image (output in `/thumbnails/800/…`) and computes the `height`. Without an image library (Imagick, libvips or GD), only the HTML attribute is set.
- Images are lazy loaded and decoded asynchronously by default: `{loading=eager}` / `{decoding=auto}` to opt out (e.g. for a hero image).
- `{placeholder=color}` or `{placeholder=lqip}` fills the image area before loading (JPEG, PNG and GIF only; `lqip` is not compatible with animated GIF).
- A CSS class (`{.wide}`) also selects the `assets.images.responsive.sizes.<class>` value of the `sizes` attribute.
- Remote images (`https://…`) are downloaded and processed as assets (disable with `pages.body.images.remote: false`, or set a `fallback` image).

Global options for the images of the body:

```yaml
pages:
  body:
    images:
      formats: [avif, webp] # adds <source> elements in a <picture>
      responsive: true      # srcset from assets.images.responsive.widths
      resize: 0             # global width in pixels (0 = disabled)
      caption: true         # <figure> with the image title as <figcaption>
      placeholder: color    # or lqip
      class: img-fluid      # default CSS class(es)
      lazy: true
      decoding: true
      dark_suffix: .dark    # dark/mobile variants, see assets.md
```

## Embeds (Videos, Audio, Gists)

```markdown
[Cecil presentation](https://www.youtube.com/watch?v=ur8koU0iYvc){embed}
[Video file](video.mp4){embed controls poster=/images/poster.png}
[Audio file](song.mp3){embed controls}
```

- Only **YouTube**, **Vimeo**, **Dailymotion** and **GitHub Gist** links can be embedded.
- Local `mp4`/`webm` files become `<video>`, `mp3`/`ogg`/`wav` files become `<audio>`; a video without `controls` gets `autoplay`, `loop`, `muted` and `playsinline`.
- `pages.body.links.embed.enabled: true` embeds every supported link without `{embed}`; `{embed=false}` keeps a link.

## Table of Contents and Excerpt

- Insert `[toc]` in the body to render a table of contents (headings from `pages.body.toc`, `[h2, h3]` by default). In templates, use `page.body|toc` instead.
- End the excerpt with `<!-- excerpt -->` or `<!-- break -->` (not `<!--more-->`), then render it with `{{ page.content|excerpt_html }}` (`pages.body.excerpt.capture`: `before` by default, or `after`).

```markdown
Introduction displayed in lists.
<!-- excerpt -->
Rest of the post.
```

## Notes

```markdown
:::tip
**Tip:** This is advice.
:::
```

- Renders `<aside class="note note-tip">`. Any type name can be used (`info`, `tip`, `important`, `warning`, `caution`, `danger`…): it becomes the `note-<type>` class.
- Nest notes with a longer fence for the parent (`::::`, then `:::::`…): a note is closed by a line with at least as many `:` as its opening line.
- Built-in types with dedicated rendering (`<div class="note note-<type>">`, no JavaScript):

| Type         | Items                                                                       | Rendering                                     |
|--------------|-----------------------------------------------------------------------------|-----------------------------------------------|
| `tabs`       | one per `####` heading                                                      | stacked panels (`<section class="note-tab">`) |
| `code-group` | one per `####` heading, or one per code block (titled with its language)    | same as `tabs`                                |
| `steps`      | one per `####` heading                                                      | ordered list (`<ol class="note-steps-list">`) |
| `accordion`  | one per `####` heading                                                      | `<details class="note-accordion-item">`       |
| `cards`      | one per list entry (a leading link is the card title), or per `####` heading | list (`<ul class="note-cards-list">`)        |

````markdown
:::tabs
#### macOS and Linux
```bash
brew install cecilapp/tap/cecil
```
#### Windows
```bash
scoop install https://cecil.app/scoop/cecil.json
```
:::

:::cards
- [Installation](installation.md): download and install Cecil.
- [Configuration](configuration.md): configure your site.
:::
````

Another type can be rendered with a `partials/notes/<type>.html.twig` template (see [templates.md](templates.md#built-in-partials-and-utilities)).

## Menus

A page joins a menu with its `menu` front matter variable; entries not linked to a page are declared in the configuration (see [configuration.md](configuration.md#menus)):

```yaml
---
title: About
menu: main                  # or a list: [main, footer]
---
```

```yaml
---
menu:
  main:
    weight: 10              # lighter first
  footer:
    name: "Who we are"      # entry name (default: page title)
---
```

A `main` menu is created automatically with the homepage and the top-level sections, unless they define their own `menu` variable.

## Taxonomies

Declare vocabularies in the configuration (`plural: singular`), then list terms in the front matter with the **plural** key:

```yaml
# cecil.yml
taxonomies:
  tags: tag
  categories: category
```

```yaml
---
tags: [PHP, Static site]
categories: Tutorials
---
```

Cecil generates the vocabulary page (`/tags/`) and a page per term (`/tags/php/`, `/tags/static-site/`), with feeds. To render them, see the `Vocabulary` and `Term` lookup rules and variables in [templates.md](templates.md#taxonomies).

## Permalinks

Set the path of all the pages of a section with `pages.paths` (not a `permalink` variable, nor a top-level `paths` key, which is deprecated):

```yaml
pages:
  paths:
    - section: blog
      path: :section/:year/:month/:day/:slug # /blog/2024/05/14/my-post/
```

- Placeholders: `:year`, `:month`, `:day`, `:section`, `:slug`.
- Every page of the section must have a valid `date` (front matter, file name prefix or file modification date), otherwise the build fails.
- Localize it under `languages[].config.pages.paths` (e.g. `path: blogue/:year/:slug` for French).
- A single page path is changed with `slug` or `path` in its front matter.
