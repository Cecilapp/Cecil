<!--
description: "Create content and organize it."
date: 2021-05-07
-->

# Content

There is 3 kinds of content in Cecil:

1. **Pages** ([Markdown](https://daringfireball.net/projects/markdown/) files in `content/`)
2. **Static files** (images, CSS, PDF, etc. in `static/`)
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
|  ├─ logo.png           <- Static file
|  └─ css
|     └─ style.scss
└─ data
   ├─ authors.yml        <- Data collection
   └─ galleries
      └─ gallery-1.json
```

Each folder in the root of `content/` is called a **_Section_** (ie: « Blog », « Project », etc.)

Files in `static/` are copied as is in the root of the built website (ie: `static/images/logo.png` -> `images/logo.png`) or manipulated by [`asset()`](3-Templates.md#asset)

Content of files in `data/` are exposed in [templates](3-Templates.md) with [`{{ site.data }}`](3-Templates.md#site-data)

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
   └─ static/
      ├─ logo.png
      └─ css
         └─ style.css
```

By default each page is generated as `filename-slugified/index.html` to get a “beautiful“ URL like `https://mywebsite.tld/blog/post-1/`.

To get an “ugly” URL, use `uglyurl: true` in front matter (ie: `404.html` instead of `404/index.html`).

You can override _Section_’s default variables by creating an `index.md` file in the _Section_’s directory (ie: `blog/index.md`).

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

Your page are composed with a front matter (“meta datas”) and a body (main content).

### Front matter

The *front matter* is the way to store variables in a _Page_, in _key/value_ format.

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

*Body* is the main content of the page, it could be written in [Markdown](http://daringfireball.net/projects/markdown/syntax), in **[Markdown Extra](https://michelf.ca/projects/php-markdown/extra/)** or in plain text.

_Example:_

```markdown
# Lorem ipsum

Lorem ipsum dolor [sit amet](https://example.com), consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.

![Alternative description](/images/img.jpg)
```

_Cecil_ provides some extra features to enhance your content, like image lazy loading, resizing and responsive.  See below for more details.

#### Images

##### Resize

Each image in the *body* can be resized by setting a smaller width than the original image with the extra attribute `{width=X}` and if the [`resize` option of the converter](4-Configuration.md#body) is enabled.

Ratio is preserved, the original file is not altered, and the resized version is stored in `/assets/thumbnails/<width>/images/img.jpg`.

This feature requires [GD extension](https://www.php.net/manual/fr/book.image.php) (otherwise it only add a `width` HTML attribute to the `img` tag).

_Example:_

```markdown
![Alternative description](/images/img.jpg 'Title'){width="800"}
```

##### Responsive

If the [`responsive` option of the converter](4-Configuration.md#body) is enabled, then all images in the *body* will be automatically _responsived_.

_Example:_

```markdown
![Alternative description](/images/img.jpg 'Title'){width="800"}
```

If `lazy`, `resize` and `responsive` options of the converter are enabled, then this Markdown line will be converted to:

```html
<img src="/assets/thumbnails/800/images/img.jpg"
  alt="Alternative description"
  title="Title"
  width="800"
  loading="lazy"
  srcset="/assets/thumbnails/320/images/img.jpg 320w,
          /assets/thumbnails/640/images/img.jpg 640w
          /assets/thumbnails/800/images/img.jpg 800w"
  sizes="100vw"
>
```

#### Excerpt

An excerpt can be defined in the *body* with one of those following tags: `excerpt` or `break`.

_Example:_

```html
Introduction.
<!-- excerpt -->
Main content.
```

## Variables

The front matter can contains custom variables or override predefined variables.

### Predefined

Predefined variables.

| Variable    | Description             | Default value                                                            |
| ----------- | ----------------------- | ------------------------------------------------------------------------ |
| `title`     | Title                   | File name without extension (ie: `Post 1`).                              |
| `layout`    | Layout                  | See [_Templates Lookup rules_](3-Templates.md#lookup-rules) (ie: `404`). |
| `date`      | Date (ie: `2019/04/15`) | File creation date (PHP _DateTime_ object).                              |
| `updated`   | Date of modification    | File modification date (PHP _DateTime_ object).                          |
| `section`   | Section                 | Page's _Section_ (ie: `blog`).                                           |
| `path`      | Path                    | Page's _path_ (ie: `blog/post-1`).                                       |
| `slug`      | Slug                    | Page's _slug_ (ie: `post-1`).                                            |
| `published` | Draft or published?     | `true`.                                                                  |
| `draft`     | Draft or published?     | `false`.                                                                 |
| `output`    | Rendered format         | `html`.                                                                  |

### menu

A _Page_ can be added to a menu, as an entry.

In the following example, the menu is `navigation`:

```yaml
---
menu: navigation
---
```

A same _Page_ could be added to severals menus, and the position of each entry could be defined with the `weight` key (the lightest first).

See [_Menus configuration_](4-Configuration.md#menus) for details.

_Examples:_

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

Each page can contain severals terms (ie: `Tag 1`) of each taxonomies’ vocabulary (ie: `tags`).

_Example:_

```yaml
---
tags:
  - "Tag 1"
  - "Tag 2"
---
```

### Section

Dedicated variables can be used in a custom _Section_ (ie: `blog/index.md`).

#### sortby

The order of *Pages* can be changed for a *Section*.

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

Global [pagination configuration](4-Configuration.md#pagination) can be overridden for a *Section*.

_Example:_

```yaml
---
pagination:
  max: 2
  path: "p"
---
```

#### cascade

Any values in `cascade` variable will be merged into front matter of all sub pages.

Note: existing sub pages variables are not overridden.

_Example:_

```yaml
---
cascade:
  banner: image.jpg
---
```

#### circular

Set `circular` to `true` to enable circular pagination with [_page.<prev/next>_](3-Templates.md#page-prev-next).

```yaml
---
circular: true
---
```

### exclude

Set `exclude` to `true` to hide the page from list pages (like _Home page_, _Section_, _Sitemap_, etc.).

```yaml
---
exclude: true
---
```

`exclude` is different from [`published`](#predefined): an excluded page is published but hidden from the section pages’ list.

### redirect

As indicated by its name, the `redirect` variable is used to redirect a page to a dedicated URL.

The default template is [`redirect.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/redirect.html.twig).

```yaml
---
redirect: "https://arnaudligny.fr/"
---
```

### alias

`alias` is used to create a redirection to the current page.

In the following example `contact/` redirects to `about/`:

```yaml
---
title: "About"
alias:
  - contact
---
```

### external

A page with an `external` variable try to fetch the content of the pointed resource.

```yaml
---
external: "https://raw.githubusercontent.com/Cecilapp/Cecil/master/README.md"
---
```

### File prefix

The filename can contain a _prefix_ to define _Page_’s `date` or `weight` (used by `sortby`).

The prefix is not included in the _Pages_’s `title` variable.  
Available prefix separator are `-`, `_` ~~and `.`~~.

#### date

The _date prefix_ is used to set the _Page_’s creation date, and must be a valid date format (`YYYY-MM-DD`).

_Example:_

In `2019-04-23-A blog post.md`:

- the prefix is `2019-04-23`
- the _Page_’s `date` is `2019-04-23`
- the _Page_’s `title` is `A blog post`

#### weight

The _weight prefix_ is used to set the _Page_’s sort order, and must be a valid integer value.

_Example:_

In `1-The first project.md`:

- the prefix is `1`
- the _Page_’s `weight` is `1`
- the _Page_’s `title` is `The first project`

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

To be able to use variables and shortcodes you must include a specific template instead of `{{ page.content }}`:

```twig
{% include page.content_template %}
```

> *Experimental*

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
