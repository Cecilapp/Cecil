<!--
title: "Markdown"
description: "Markdown syntax and extensions: attributes, links, images, table of contents, notes, syntax highlight, etc."
date: 2021-05-07
updated: 2026-10-16
-->
# Markdown

Cecil supports [Markdown](https://daringfireball.net/projects/markdown/syntax) format, but also [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/).

Cecil also provides **extra features** to enhance your content, see below.

## Attributes

With [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/) you can set an id, class and custom attributes on certain elements using an attribute block.  
For instance, put the desired attribute(s) after a heading, a fenced code block, a link or an image at the end of the line inside curly brackets, like this:

```markdown
## Header {#id .class attribute=value}
```

:::warning
For an inline element, like a link, you must use a line break after the closing brace:

```markdown
Lorem ipsum [dolor](url){attribute=value} 
sit amet.
```

:::

## Links

You can create a link with the syntax `[Text](url)`, where `url` can be a path, a relative path to a Markdown file, an external URL, etc.

_Example:_

```markdown
[Link to a path](/about/)
[Link to a Markdown file](about.md)
[Link to Cecil website](https://cecil.app)
```

:::info
A relative link to a Markdown file is resolved from the folder of the current file (as on GitHub), then replaced by the URL of the targeted page.
:::

### Link to a page

You can easily create a link to a page with the syntax `[Page title](page:page-id)`.

_Example:_

```markdown
[Link to a blog post](page:blog/post-1)
```

### External

By default external links have the following value for `rel` attribute: `noopener noreferrer`.

:::info
A link is considered external if its URL starts with `http` but not with the [`baseurl`](../configuration/1-site.md#baseurl): `baseurl` must be defined.
:::

_Example:_

```html
<a href="<url>" rel="noopener noreferrer">Link to another website</a>
```

You can change this behavior with [`pages.body.links.external` options](../configuration/4-pages.md#pages-body-links).

### Embedded links

Cecil can try to turn a link into embedded content by using the `{embed}` attribute or by setting the global configuration option `pages.body.links.embed.enabled` to `true`.

:::important
Only **YouTube**, **Vimeo**, **Dailymotion**, and **GitHub Gists** links are supported.
:::

_Example:_

```markdown
[CECIL : LE générateur de SITES STATIQUES en PHP](https://www.youtube.com/watch?v=ur8koU0iYvc){embed}
```

[CECIL : LE générateur de SITES STATIQUES en PHP](https://www.youtube.com/watch?v=ur8koU0iYvc){embed}

#### Local video/audio files

Cecil can also create `<video>` and `<audio>` HTML elements, based on the file extension (`mp4` and `webm` for video, `mp3`, `ogg` and `wav` for audio, see [`pages.body.links.embed`](../configuration/4-pages.md#pages-body-links)).

_Example:_

```markdown
[Video file](video.mp4){embed controls poster=/images/video-test.png}
[Audio file](song.mp3){embed controls}
```

Is converted to:

```html
<video src="/video.mp4" controls poster="/images/video-test.png" style="max-width:100%;height:auto;background-color:#d8d8d8;"></video>
<audio src="/song.mp3" controls></audio>
```

:::info
Without the `controls` attribute, a video gets the `autoplay`, `loop`, `muted` and `playsinline` attributes.
:::

With `{embed=false}` (or when embedding is disabled), a local video/audio file stays a link, but its URL points to the published file (fingerprinted if [`assets.fingerprint`](../configuration/6-assets.md) is enabled).

## Images

To add an image, use an exclamation mark (`!`) followed by an alternative description in brackets (`[]`), and the path or URL to the image in parentheses (`()`).  
You can optionally add a title in quotation marks.

```markdown
![Alternative description](/image.jpg "Image title")
```

:::info
The path should be relative to the root of your website (e.g.: `/image.jpg`), however Cecil is able to normalize a path relative to _assets_ and _static_ directories (e.g.: `../../assets/image.jpg`).
:::

:::info
Remote images (e.g.: `https://example.com/image.jpg`) are downloaded and handled as _Assets_, so they can be resized and converted. You can disable this behavior, or define a fallback image, with the [`remote` option](../configuration/4-pages.md#pages-body-images).
:::

### Lazy loading

Cecil adds the attribute `loading="lazy"` to each image.

_Example:_

```markdown
![](/image.jpg)
```

Is converted to:

```html
<img src="/image.jpg" loading="lazy">
```

:::info
You can disable this behavior with the attribute `{loading=eager}` or with the [`lazy` option](../configuration/4-pages.md#pages-body-images).
:::

### Decoding

Cecil adds the attribute `decoding="async"` to each image.

_Example:_

```markdown
![](/image.jpg)
```

Is converted to:

```html
<img src="/image.jpg" decoding="async">
```

:::info
You can disable this behavior with the attribute `{decoding=auto}` or with the [`decoding` option](../configuration/4-pages.md#pages-body-images).
:::

### Resize

Each image in the _body_ can be resized automatically by setting a smaller width than the original one, with the extra attribute `{width=X}`.

_Example:_

```markdown
![](/image.jpg){width=800}
```

Is converted to:

```html
<img src="/thumbnails/800/image.jpg" width="800" height="600">
```

:::info
Ratio is preserved (`height` attribute is calculated automatically), the original file is not altered and the resized version is stored in `/thumbnails/<width>/`.
:::

:::tip
You can also resize all images to a maximum width with the [`resize` option](../configuration/4-pages.md#pages-body-images).
:::

:::important
This feature requires an image processing library: [Imagick](https://www.php.net/manual/book.imagick.php) is used first if available (and able to read JPEG and PNG), then [libvips](https://www.libvips.org/) (through the PHP [FFI](https://www.php.net/manual/book.ffi.php) extension), and finally [GD](https://www.php.net/manual/book.image.php) as fallback; otherwise it only adds a `width` HTML attribute to the `img` tag.
:::

:::info
libvips support is optional and is not bundled with `cecil.phar`. To use it, Cecil must be installed with [Composer](https://getcomposer.org), and you need:

1. [libvips](https://www.libvips.org/install.html) installed on your system
2. the PHP [FFI](https://www.php.net/manual/book.ffi.php) extension enabled
3. the `intervention/image-driver-vips` package installed alongside Cecil

If Cecil is a dependency of your project (see [Library](../developers/2-library.md#libvips-support)):

```bash
composer require intervention/image-driver-vips
```

If Cecil is installed globally:

```bash
composer global require cecil/cecil intervention/image-driver-vips
```
:::

### Formats

If the [`formats` option](../configuration/4-pages.md#pages-body-images) is defined, alternative images are created and added.

_Example:_

```markdown
![](/image.jpg)
```

Could be converted to:

```html
<picture>
  <source srcset="/image.avif" type="image/avif">
  <source srcset="/image.webp" type="image/webp">
  <img src="/image.jpg">
</picture>
```

:::important
Please note that **not all image formats** are always included in the PHP image extensions.
:::

### Responsive

If the [`responsive` option](../configuration/4-pages.md#pages-body-images) is enabled, then all images in the _body_ will be made responsive automatically.

_Example:_

```markdown
![](/image.jpg){width=800}
```

will be converted to:

```html
<img src="/thumbnails/800/image.jpg" width="800" height="600"
  srcset="/thumbnails/480/image.jpg 480w,
          /thumbnails/640/image.jpg 640w,
          /thumbnails/768/image.jpg 768w,
          /thumbnails/800/image.jpg 800w"
  sizes="100vw"
>
```

:::info
Because a body image is converted into an [Asset](../assets/index.md#asset), the different widths are defined (and can be changed) in the `assets.images.responsive.widths` option of the [assets configuration](../configuration/6-assets.md).
:::

The `sizes` attribute takes the value of the `assets.images.responsive.sizes.default` configuration option, but it can be changed by creating a new entry named after a _class_ added to the image.

_Example:_

```yaml
assets:
  images:
    responsive:
      sizes:
        default: 100vw
        my_class: "(max-width: 800px) 768px, 1024px"
```

```markdown
![](/image.jpg){.my_class}
```

:::info
You can combine `formats` and `responsive` options.
:::

### CSS class

You can set a default value to the `class` attribute of each image with the [`class` option](../configuration/4-pages.md#pages-body-images).

### Caption

The optional title can be used to create a caption (`figcaption`) automatically by enabling the [`caption` option](../configuration/4-pages.md#pages-body-images).

_Example:_

```markdown
![](/image.jpg "Title")
```

Is converted to:

```html
<figure>
  <img src="/image.jpg" title="Title">
  <figcaption>Title</figcaption>
</figure>
```

:::info
Caption supports Markdown content.
:::

### Localized image

For translated pages, Cecil first looks for a language-suffixed file when resolving Markdown image paths.

_Example:_

```markdown
![](/images/cecil-logo.png)
```

With a French page (`fr`), Cecil tries `/images/cecil-logo.fr.png` first, then falls back to `/images/cecil-logo.png`.

### Dark and mobile variants

If the [`dark_suffix` option](../configuration/4-pages.md#pages-body-images) is defined (e.g.: `.dark`), Cecil looks for a dark variant of each image (e.g.: `image.dark.jpg` alongside `image.jpg`) and adds it as a `<source media="(prefers-color-scheme: dark)">` in a `<picture>` element.

In the same way, if the [`mobile_suffix` option](../configuration/4-pages.md#pages-body-images) is defined (e.g.: `.mobile`), Cecil looks for a mobile variant of each image (e.g.: `image.mobile.jpg`) and adds it as a `<source>` with the `mobile_media_query` media query.

### Placeholder

As images are typically heavier and slower resources, and they don’t block rendering, we should attempt to give users something to look at while they wait for the image to arrive.

The `placeholder` attribute accepts two options:

1. `color`: display a colored background (based on image dominant color)
2. `lqip`: [Low-Quality Image Placeholder](https://www.guypo.com/introducing-lqip-low-quality-image-placeholders)

_Examples:_

```markdown
![](/images/img.jpg){placeholder=color}
![](/images/img.jpg){placeholder=lqip}
```

:::tip
You can set a value to the `placeholder` attribute for each image with the [`placeholder` option](../configuration/4-pages.md#pages-body-images).
:::

:::warning
Placeholders are only applied to JPEG, PNG and GIF images, and the `lqip` option is not compatible with animated GIF.
:::

## Table of contents

You can add a table of contents with the following Markdown syntax:

```markdown
[toc]
```

:::info
By default, the ToC extracts H2 and H3 headings. You can change this behavior with [body options](../configuration/4-pages.md#pages-body).
:::

## Excerpt

An excerpt can be defined in the _body_ with one of the following HTML comments: `<!-- excerpt -->` or `<!-- break -->` (see the [`pages.body.excerpt` option](../configuration/4-pages.md#pages-body-excerpt)).

_Example:_

```html
Introduction.
<!-- excerpt -->
Main content.
```

Then use the [`excerpt_html` filter](../templates/reference/3-filters.md#excerpt-html) in your template.

## Notes

Create a _Note_ block (info, tip, important, etc.).

_Example:_

```markdown
:::tip
**Tip:** This is advice.
:::
```

Is converted to:

```html
<aside class="note note-tip">
  <p>
    <strong>Tip:</strong> This is advice.
  </p>
</aside>
```

:::tip
**Tip:** This is advice.
:::

:::info
Any type name can be used: it is added to the `class` attribute as `note-<type>` (e.g.: `:::danger` gives `class="note note-danger"`).
:::

_Other examples:_

:::
empty
:::

:::info
info
:::

:::tip
tip
:::

:::important
important
:::

:::warning
warning
:::

:::caution
caution
:::

### Nested notes

A note is closed by a line made of at least as many `:` as its opening line: to nest notes, use a longer fence for the parent note (`::::`, then `:::::` for a third level, etc.).

```markdown
::::info
Before.

:::tip
**Tip:** This is advice.
:::

After.
::::
```

::::info
Before.

:::tip
**Tip:** This is advice.
:::

After.
::::

A longer fence also lets you show the note syntax in a code block, inside a note.

### Templates

A note is rendered with the `partials/notes/<type>.html.twig` template, if it exists (in the site `layouts/`, in a theme or built-in), instead of the `<aside>` element.

Cecil provides templates for the following types, rendered without JavaScript: a theme can style them, override them and add interactivity. Unlike other notes, their root element is a `<div class="note note-<type>">` (not an `<aside>`), as their content is part of the main content.

| Type         | Items                                                                       | Rendering                                       |
| ------------ | --------------------------------------------------------------------------- | ----------------------------------------------- |
| `tabs`       | one per `####` heading                                                      | stacked panels (`<section class="note-tab">`)   |
| `code-group` | one per `####` heading, or one per code block (titled with its language)    | same as `tabs`                                  |
| `steps`      | one per `####` heading                                                      | ordered list (`<ol class="note-steps-list">`)   |
| `accordion`  | one per `####` heading                                                      | `<details class="note-accordion-item">`         |
| `cards`      | one per list entry (a leading link is the card title), or per `####` heading | list (`<ul class="note-cards-list">`)           |

The examples below are themselves nested notes: a `tabs` note (with a `::::` fence) shows the Markdown source and its rendering.

#### Tabs

Alternative contents, one per `####` heading (e.g.: per operating system).

::::tabs
#### Markdown

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
````

#### Result

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
::::

#### Code group

Like `tabs`, with code blocks only: without heading, each code block is titled with its language.

::::tabs
#### Markdown

````markdown
:::code-group
```bash
curl -LO https://cecil.app/cecil.phar
```

```powershell
Invoke-WebRequest https://cecil.app/cecil.phar -OutFile cecil.phar
```
:::
````

#### Result

:::code-group
```bash
curl -LO https://cecil.app/cecil.phar
```

```powershell
Invoke-WebRequest https://cecil.app/cecil.phar -OutFile cecil.phar
```
:::
::::

#### Steps

A numbered procedure, one step per `####` heading.

::::tabs
#### Markdown

````markdown
:::steps
#### Create a site

```bash
php cecil.phar new:site
```

#### Preview it

```bash
php cecil.phar serve
```
:::
````

#### Result

:::steps
#### Create a site

```bash
php cecil.phar new:site
```

#### Preview it

```bash
php cecil.phar serve
```
:::
::::

#### Accordion

Collapsible items, one per `####` heading (e.g.: a FAQ).

::::tabs
#### Markdown

````markdown
:::accordion
#### Is PHP required?

No: the standalone binary embeds PHP.

#### Where is the generated site?

In the `_site` directory.
:::
````

#### Result

:::accordion
#### Is PHP required?

No: the standalone binary embeds PHP.

#### Where is the generated site?

In the `_site` directory.
:::
::::

#### Cards

Links to other pages, one card per list entry: a leading link is the card title.

::::tabs
#### Markdown

````markdown
:::cards
- [Installation](../getting-started/2-installation.md): download and install Cecil.
- [Configuration](../configuration/index.md): configure your site.
:::
````

#### Result

:::cards
- [Installation](../getting-started/2-installation.md): download and install Cecil.
- [Configuration](../configuration/index.md): configure your site.
:::
::::

#### Custom template

The template receives a `note` variable:

- `note.type`: note type (e.g.: `tabs`)
- `note.id`: unique ID in the page (e.g.: `note-tabs-1`)
- `note.content`: HTML of the whole note
- `note.intro`: HTML of the content before the first `####` heading
- `note.items`: list of items, each with:
  - `title`: title (HTML)
  - `heading`: heading element (HTML), with its `id` attribute
  - `id`: heading ID, if any
  - `link`: leading link element (HTML) of a list entry
  - `url`: leading link URL of a list entry
  - `language`: language of a code block
  - `content`: content (HTML)

To override the rendering of a note type, create the template in your site (or theme), for example `layouts/partials/notes/tip.html.twig`:

```twig
<div class="callout callout-{{ note.type }}">
  {{ note.content|raw }}
</div>
```

:::info
Notes are rendered when Markdown is converted, before pages rendering: a note template has access to the `note` variable and to translations (in the page language), but not to `page` or `site`.
:::

## Syntax highlight

Code block syntax highlighting is enabled by default with the [`pages.body.highlight`](../configuration/4-pages.md#pages-body-highlight) option.

If needed, you can disable it with:

```yaml
pages:
  body:
    highlight: false
```

_Example:_

<pre>
```php
echo "Hello world";
```
</pre>

Is rendered to:

```php
echo "Hello world";
```

:::info
You can customize the syntax highlighting style by creating your own theme. See the [Highlight.js Theme Guide](https://highlightjs.readthedocs.io/en/latest/theme-guide.html).
:::

## Inserted text

Represents a range of text that has been added.

```markdown
++text++
```

Is converted to:

```html
<ins>text</ins>
```
