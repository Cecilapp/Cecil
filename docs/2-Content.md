<!--
description: "Create content and organize it."
date: 2021-05-07
updated: 2022-03-10
-->

# Content

There is 3 kinds of content in Cecil:

1. **_Pages_** ([Markdown](https://daringfireball.net/projects/markdown/) files in `content/`)
2. **Static files** (images, PDF, etc. in `static/`)
3. **Data files** (custom variables collections in `data/`)

## Files organization

Your content should be organized in a manner that reflects the rendered website.

### File system tree

```plaintext
<mywebsite>
├─ content
|  ├─ blog               <- Section
|  |  ├─ post-1.md       <- Page in Section
|  |  └─ post-2.md
|  ├─ projects
|  |  └─ project-1.md
|  └─ about.md           <- Page in the root
├─ static
|  └─ logo.png           <- Static file
├─ assets
|  └─ styles.scss        <- Asset file
└─ data
   └─ authors.yml        <- Data collection
```

- Each folder in the root of `content/` is called a **_Section_** (e.g.: “Blog“, “Project“, etc.)
- You can override _Section_’s default variables by creating ana file `index.md` in its directory (e.g.: `blog/index.md`)
- Files in `static/` are copied as is in the root of the built website (e.g.: `static/images/logo.png` -> `images/logo.png`)
- Files in `assets/` are handled with the [`asset()`](3-Templates.md#asset) function
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
   ├─ logo.png
   └─ styles.css
```

By default each _Page_ is generated as `filename-slugified/index.html` to get a “beautiful“ URL like `https://mywebsite.tld/blog/post-1/`. To get an “ugly” URL (`404.html` instead of `404/`), set `uglyurl: true` in front matter.

### File VS URL structure

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

The _front matter_ is used to store [variables](#variables) in a _Page_, in _key/value_ format.

It must be the first thing in the file and must be a valid [YAML](https://en.wikipedia.org/wiki/YAML).  
Separators must be `---`, `<!-- -->` or `+++`.

_Example:_

```yaml
---
title: "The title"
date: 2019-02-21
tags: [tag 1, tag 2]
customvar: "Value of customvar"
---
```

### Body

_Body_ is the main content of a _Page_, it could be written in [Markdown](http://daringfireball.net/projects/markdown/syntax), in **[Markdown Extra](https://michelf.ca/projects/php-markdown/extra/)** or in plain text.

_Cecil_ provides extra features to enhance your content (image caption, image lazy loading, image resizing, responsive image, text excerpt, table of contents).  
See below for more details.

_Example:_

```markdown
# Header

[toc]

## Header 1

Lorem ipsum dolor [sit amet](https://example.com), consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
<!-- excerpt -->
Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.

## Header 2

![Description](/image.jpg 'Title')
```

#### Images

##### Lazy loading

By default Cecil apply the attribute `loading="lazy"` on each images.  
So you can disable it [in configuration](4-Configuration.md#body).

```markdown
![](/image.jpg)
```

Is converted to :

```html
<img src="/image.jpg" loading="lazy">
```

##### Caption

You can autommatically add a caption (`figcaption`) to an image by adding a title.

```markdown
![](/images/img.jpg 'Title')
```

Is converted to :

```html
<figure>
  <img src="/image.jpg" title="Title">
  <figcaption>Title</figcaption>
</figure>
```

##### Resize

Each image in the _body_ can be resized by setting a smaller width than the original image with the extra attribute `{width=X}` and if the [`resize` option of the converter](4-Configuration.md#body) is enabled.

Ratio is preserved, the original file is not altered, and the resized version is stored in `/assets/thumbnails/<width>/image.jpg`.  
This feature requires [GD extension](https://www.php.net/manual/book.image.php) (otherwise it only add a `width` HTML attribute to the `img` tag).

```markdown
![](/image.jpg){width=800}
```

Is converted to :

```html
<img src="/assets/thumbnails/800/image.jpg" width="800" height="600">
```

##### Responsive

If the [`responsive` option of the converter](4-Configuration.md#body) is enabled, then all images in the _body_ will be automatically _responsived_.

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

#### Excerpt

An excerpt can be defined in the _body_ with one of those following tags: `excerpt` or `break`.

_Example:_

```html
Introduction.
<!-- excerpt -->
Main content.
```

#### Table of contents

You can add a table of contents with the following Markdown syntax:

```markdown
[toc]
```

## Variables

The front matter can contains custom variables applied to the current _Page_.

### Predefined

All the predefined variables can be overridden except `section`.

| Variable    | Description       | Default value                                      | Example       |
| ----------- | ----------------- | -------------------------------------------------- | ------------- |
| `title`     | Title             | File name without extension.                       | `Post 1`      |
| `layout`    | Template          | See [_Lookup rules_](3-Templates.md#lookup-rules). | `404`         |
| `date`      | Creation date     | File creation date (PHP _DateTime_ object).        | `2019/04/15`  |
| `updated`   | Modification date | File modification date (PHP _DateTime_ object).    | `2021/11/19`  |
| `section`   | Section           | Page's _Section_.                                  | `blog`        |
| `path`      | Path              | Page's _path_.                                     | `blog/post-1` |
| `slug`      | Slug              | Page's _slug_.                                     | `post-1`      |
| `published` | Published or not  | `false`.                                           | `true`        |
| `draft`     | Published or not  | `true`.                                            | `false`       |

### menu

A _Page_ can be added to a menu.

A same _Page_ could be added to severals menus, and the position of each entry can be defined with the `weight` key (the lightest first).

See [_Menus configuration_](4-Configuration.md#menus) for details.

_Examples:_

```yaml
---
menu: navigation
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

Each _Page_ can contain severals terms (e.g.: `Tag 1`) of each taxonomies’ vocabulary (e.g.: `tags`).

_Example:_

```yaml
---
tags: ["Tag 1", "Tag 2"]
---
```

### redirect

As indicated by its name, the `redirect` variable is used to redirect a page to a dedicated URL.

It use the template [`redirect.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/redirect.html.twig).

_Example:_

```yaml
---
redirect: "https://arnaudligny.fr/"
---
```

### alias

`alias` is used to create redirections to the current page.

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

The filename can contain a prefix to define `date` or `weight` (used by `sortby`) of the _Page_.

The prefix is not included in the `title` of the _Page_.  
Available prefix separator are `-`, `_` ~~and `.`~~.

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

The order of _Pages_ can be changed for a _Section_.

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

Global [pagination configuration](4-Configuration.md#pagination) can be overridden for a _Section_.

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
Existing variables are not overridden.

_Example:_

```yaml
---
cascade:
  banner: image.jpg
---
```

#### circular

Set `circular` to `true` to enable circular pagination with [_page.<prev/next>_](3-Templates.md#page-prev-next).

_Example:_

```yaml
---
circular: true
---
```

### Home page

Home page support `sortby` and `pagination` configuration.

#### pagesfrom

Set `pagesfrom` to a valid section to use pages collection from a section.

### exclude

Set `exclude` to `true` to hide the _Page_ from lists (like _Home page_, _Section_, _Sitemap_, etc.).

_Example:_

```yaml
---
exclude: true
---
```

`exclude` is different from [`published`](#predefined): an excluded page is published but it’s hidden from the _Section_.

## Multilingual

If your content is available in multiple [languages](4-Configuration.md#languages) there is 2 ways to define it:

### Language in the file name

Defines the page’s language by adding the language `code` in the file name.

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
