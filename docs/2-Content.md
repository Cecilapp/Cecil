<!--
description: "Create content and organize it."
date: 2021-05-07
updated: 2022-06-20
-->

# Content

There is different kinds of content in Cecil:

**Pages**
: Markdown (or plain text) files (stored in `content/`).

**Assets**
: Handled files like resized images, compiled Sass, minified scripts, etc.

**Static**
: Files copied as is.

**Data**
: Custom variables collections.

## Files organization

Your content should be organized in a manner that reflects the rendered website.

### File system tree

```plaintext
<mywebsite>
├─ content
|  ├─ blog            <- Section
|  |  ├─ post-1.md    <- Page in Section
|  |  └─ post-2.md
|  ├─ projects
|  |  └─ project-1.md
|  └─ about.md        <- Page in the root
├─ assets
|  ├─ styles.scss     <- Asset file
|  └─ logo.png
├─ static
|  └─ video.mp4       <- Static file
└─ data
   └─ authors.yml     <- Data collection
```

**Explanation:**

- Each folder in the root of `content/` is called a **_Section_** (e.g.: “Blog“, “Project“, etc.)
- You can override _Section_’s default variables by creating an `index.md` file in its directory (e.g.: `blog/index.md`)
- Files in `assets/` are handled with the [`asset()`](3-Templates.md#asset) function in templates
- Files in `static/` are copied as is in the root of the built website (e.g.: `static/video.mp4` -> `video.mp4`)
- Content of files in `data/` is exposed in [templates](3-Templates.md) with [`{{ site.data }}`](3-Templates.md#site-data)

### Built website tree

```plaintext
<mywebsite>
└─ _site
   ├─ index.html               <- Generated home page
   ├─ blog/
   |  ├─ index.html            <- Generated list of posts
   |  ├─ post-1/index.html     <- A blog post
   |  └─ post-2/index.html
   ├─ projects/
   |  ├─ index.html            <- Generated list of projects
   |  └─ project-1/index.html
   ├─ about/index.html
   ├─ styles.css
   ├─ logo.png
   └─ video.mp4
```

By default each _Page_ is generated as `slugified-filename/index.html` to get a “beautiful“ URL like `https://mywebsite.tld/blog/post-1/`.  
To get an “ugly” URL (like `404.html` instead of `404/`), set `uglyurl: true` in front matter.

### File VS URL

```plaintext
File:
                 content/my-projects/project-1.md
                        └───── filepath ──────┘
URL:
    ┌───── baseurl ─────┬─────── path ────────┐
     https://example.com/my-projects/project-1/index.html
                        └─ section ─┴─ slug ──┘
```

## Page anatomy

A _Page_ is a file made up of a **front matter** and a **body**.

### Front matter

The _front matter_ is a collection of [variables](#variables) (in _key/value_ format) surrounded by `---`.

_Example:_

```yaml
---
title: "The title"
date: 2019-02-21
tags: [tag 1, tag 2]
customvar: "Value of customvar"
---
```

:::info
**Info:** You can also use `<!-- -->` or `+++` as separator.
:::

### Body

_Body_ is the main content of a _Page_, it could be written in [Markdown](#markdown) or in plain text.

_Example:_

```markdown
# Header

[toc]

## Sub-Header 1

Lorem ipsum dolor [sit amet](https://example.com), consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
<!-- excerpt -->
Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.

## Sub-Header 2

![Description](/image.jpg "Title")

## Sub-Header 3

:::tip
**Tip:** This is an advice.
:::
```

## Markdown

Cecil supports [Markdown](http://daringfireball.net/projects/markdown/syntax) format but also [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/).

Cecil also provides **extra features** to enhance your content, see below.

### Table of contents

You can add a table of contents with the following Markdown syntax:

```markdown
[toc]
```

### Excerpt

An excerpt can be defined in the _body_ with one of those following tags: `excerpt` or `break`.

_Example:_

```html
Introduction.
<!-- excerpt -->
Main content.
```

### Notes

Create a _Note_ block (info, tips, important, etc.).

_Example:_

```markdown
:::tip
**Tip:** This is an advice.
:::
```

Is converted to:

```html
<div class="note note-tip">
  <p>
    <strong>Tip:</strong> This is an advice.
  </p>
</div>
```

### Syntax highlight

Enables code block syntax highlighter by setting the [body.highlight.enabled](4-Configuration.md#body) option to `true`.

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

### Inserted text

```markdown
++text++
```

Is converted to:

```html
<ins>text</ins>
```

### Images

#### Lazy loading

By default Cecil apply the attribute `loading="lazy"` on each images.

_Example:_

```markdown
![](/image.jpg)
```

Is converted to:

```html
<img src="/image.jpg" loading="lazy">
```

:::info
**Info:** You can disable the [`lazy` option in the body configuration](4-Configuration.md#body).
:::

#### Resize

Each image in the _body_ can be resized by setting a smaller width than the original one with the extra attribute `{width=X}` (the [`resize` option in the body configuration](4-Configuration.md#body) must be enabled).

_Example:_

```markdown
![](/image.jpg){width=800}
```

Is converted to:

```html
<img src="/assets/thumbnails/800/image.jpg" width="800" height="600">
```

:::info
**Info:** Ratio is preserved, the original file is not altered, and the resized version is stored in `/assets/thumbnails/<width>/image.jpg`.  
:::

:::important
**Important:** This feature requires [GD extension](https://www.php.net/manual/book.image.php) (otherwise it only add a `width` HTML attribute to the `img` tag).
:::

#### Responsive

If the [`responsive` option in the body configuration](4-Configuration.md#body) is enabled, then all images in the _body_ will be automatically _responsived_.

_Example:_

```markdown
![](/image.jpg){width=800}
```

If `resize` and `responsive` options are enabled, then this Markdown line will be converted to:

```html
<img src="/assets/thumbnails/800/image.jpg" width="800" height="600"
  srcset="/assets/thumbnails/320/image.jpg 320w,
          /assets/thumbnails/640/image.jpg 640w,
          /assets/thumbnails/800/image.jpg 800w"
  sizes="100vw"
>
```

:::info
**Info:** Differents widths can be defined in [assets configuration](4-Configuration.md#assets).  
:::

#### WebP

If the [`webp` option in the body configuration](4-Configuration.md#body) is enabled, an alterative image in the [WebP](https://developers.google.com/speed/webp) format is created.

_Example:_

```markdown
![](/image.jpg)
```

Is converted to:

```html
<picture>
  <source srcset="/image.webp" type="image/webp">
  <img src="/image.jpg">
</picture>
```

:::important
**Important:** This feature requires [WebP](https://developers.google.com/speed/webp) be supported by PHP installation.
:::

#### Caption

You can automatically add a caption (`figcaption`) to an image with the optional title.

_Example:_

```markdown
![](/images/img.jpg "Optional title")
```

Is converted to:

```html
<figure>
  <img src="/image.jpg" title="Title">
  <figcaption>Title</figcaption>
</figure>
```

:::info
**Info:** You can disable the [`caption` option in the body configuration](4-Configuration.md#body).
:::

### Audio and video

Cecil can generate audio and video HTML elements, based on the Markdown image markup, with a special alternative text as a keyword.

#### Audio

_Example:_

```markdown
![audio](/audio/test.mp3 "Audio asset")
```

#### Video

_Example:_

```markdown
![video](/video/test.mp4 "Video asset"){poster=/images/cecil-logo.png style="width:100%;"}
```

## Variables

The _front matter_ can contains custom variables applied to the current _Page_.

It must be the first thing in the file and must be a valid [YAML](https://en.wikipedia.org/wiki/YAML).

### Predefined variables

| Variable    | Description       | Default value                                      | Example       |
| ----------- | ----------------- | -------------------------------------------------- | ------------- |
| `title`     | Title             | File name without extension.                       | `Post 1`      |
| `layout`    | Template          | See [_Lookup rules_](3-Templates.md#lookup-rules). | `404`         |
| `date`      | Creation date     | File creation date (PHP _DateTime_ object).        | `2019/04/15`  |
| `updated`   | Modification date | File modification date (PHP _DateTime_ object).    | `2021/11/19`  |
| `section`   | Section           | Page's _Section_.                                  | `blog`        |
| `path`      | Path              | Page's _path_.                                     | `blog/post-1` |
| `slug`      | Slug              | Page's _slug_.                                     | `post-1`      |
| `published` | Published or not  | `true`.                                            | `false`       |
| `draft`     | Published or not  | `false`.                                           | `true`        |

:::info
**Info:** All the predefined variables can be overridden except `section`.
:::

### menu

A _Page_ can be added to a menu.

A same _Page_ could be added to severals menus, and the position of each entry can be defined with the `weight` key (the lightest first).

See [_Menus configuration_](4-Configuration.md#menus) for details.

_Examples:_

```yaml
---
menu: main
---
```

```yaml
---
menu: [main, navigation]
---
```

```yaml
---
menu:
  main:
    weight: 10
  navigation:
    weight: 20
---
```

### Taxonomy

Taxonomies are declared in the [_Configuration_](4-Configuration.md#taxonomies).

A _Page_ can contain several vocabularies (e.g.: `tags`) and terms (e.g.: `Tag 1`).

_Example:_

```yaml
---
tags: ["Tag 1", "Tag 2"]
---
```

### Schedule

Schedules pages’ publication.

_Example:_

The page will be published if current date is >= 2023-02-07:

```yaml
schedule:
  publish: 2023-02-07
```

This page is published if current date is <= 2022-04-28:

```yaml
schedule:
  expiry: 2022-04-28
```

### redirect

As indicated by its name, the `redirect` variable is used to redirect a page to a dedicated URL.

_Example:_

```yaml
---
redirect: "https://arnaudligny.fr/"
---
```

:::info
**Info:** Redirect works with the [`redirect.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/redirect.html.twig) template.
:::

### alias

Alias is a redirection to the current page

_Example:_

```yaml
---
title: "About"
alias:
  - contact
---
```

In the previous example `contact/` redirects to `about/`.

### external

A _Page_ with an `external` variable try to fetch the content of the pointed resource.

_Example:_

```yaml
---
external: "https://raw.githubusercontent.com/Cecilapp/Cecil/master/README.md"
---
```

### File prefix

The filename can contain a prefix to define `date` or `weight` of the _Page_ (used by [`sortby`](3-TEmplates.md#sort-by-date)).

:::info
**Info:**

- The prefix is not included in the `title` of the _Page_
- Available prefix separator are `-`, `_` ~~and `.`~~
:::

#### date

The _date prefix_ is used to set the creation date of the _Page_, and must be a valid date format (`YYYY-MM-DD`).

_Example:_

In `2019-04-23-My blog post.md`:

- the prefix is `2019-04-23`
- the `date` of the _Page_ is `2019-04-23`
- the `title` of the _Page_ is `My blog post`

#### weight

The _weight prefix_ is used to set the sort order of the _Page_, and must be a valid integer value.

_Example:_

In `1-The first project.md`:

- the prefix is `1`
- the `weight` of the _Page_ is `1`
- the `title` of the _Page_ is `The first project`

### Section

Some dedicated variables can be used in a custom _Section_ (e.g.: `blog/index.md`).

#### sortby

The order of _Pages_ can be changed in a _Section_.

Available values are:

- `date`: more recent first
- `title`: alphabetic order
- `weight`: lightest first

_Example:_

```yaml
---
sortby: title
---
```

#### pagination

Global [pagination configuration](4-Configuration.md#pagination) can be overridden in a _Section_.

_Example:_

```yaml
---
pagination:
  max: 2
  path: "p"
---
```

#### cascade

Any values in `cascade` will be merged into the front matter of all _sub pages_.

_Example:_

```yaml
---
cascade:
  banner: image.jpg
---
```

:::info
**Info:** Existing variables are not overridden.
:::

#### circular

Set `circular` to `true` to enable circular pagination with [_page.<prev/next>_](3-Templates.md#page-prev-next).

_Example:_

```yaml
---
circular: true
---
```

### Home page

Like another section Home page support `sortby` and `pagination` configuration.

#### pagesfrom

Set a valid section’s name to `pagesfrom` to use pages collection from this section.

### exclude

Set `exclude` to `true` to hide a _Page_ from lists (like _Home page_, _Section_, _Sitemap_, etc.).

_Example:_

```yaml
---
exclude: true
---
```

:::info
**Info:** `exclude` is different from [`published`](#predefined): an excluded page is published but it’s hidden from the _Section_ list.
:::

## Multilingual

If your content is available in multiple [languages](4-Configuration.md#languages) there is 2 ways to define it:

### Language in the file name

Defines the page’s language by adding the language `code` as a suffix in the file name.

_Example:_

```plaintext
about.fr.md
```

### Language in the front matter

Defines the page’s language by setting the `language` variable with language `code` as value in the front matter.

_Example:_

```yml
---
language: fr
---
```

### Reference between translated pages

Each page reference pages in others languages with the `langref` variable.

The `langref` variable is provided by default, but you can change it in the front matter:

```yml
---
langref: my-page-ref
---
```

## Dynamic content

You can use [variables](3-Templates.md#variables) and shortcodes in the body content.

To do this you must include a specific template instead of `{{ page.content }}`:

```twig
{% include page.content_template %}
```

> _Experimental_

### Display variables

Front matter variables can be use in the body with the template’s syntax `{{ page.variable }}`.

_Example:_

```twig
--
var: 'value'
---
The value of `var` is {{ page.var }}.
```

### Shortcodes

Shortcodes are helpers to create dynamic content.

#### Built-in shortcodes

2 shortcodes are available by default:

##### YouTube

```twig
{{ shortcode.youtube(id) }}
```

- `id`: YouTube video ID

_Example:_

```twig
{{ shortcode.youtube('NaB8JBfE7DY') }}
```

##### GitHub Gist

```twig
{{ shortcode.gist(user, id) }}
```

- `user`: GitHub user name
- `id`: Gist ID

_Example:_

```twig
{{ shortcode.gist('Narno', 'fbe791e05b93951ffc1f6abda8ee88f0') }}
```

#### Custom shortcode

A shortcode is a [Twig macro](https://twig.symfony.com/doc/tags/macro.html) you must add in a template named `shortcodes.twig`.

_Example:_

`shortcodes.twig`:

```twig
{% extends 'macros.twig' %}

{% block macros %}

{# the "foo" shortcode #}
{% macro foo(bar = 'bar') %}
<strong>{{ bar }}</strong>
{% endmacro %}

{% endblock %}
```
