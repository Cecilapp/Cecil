<!--
description: "Create content and organize it."
date: 2021-05-07
updated: 2025-02-26
-->
# Content

There is different kinds of content in Cecil:

**Pages**
: Pages are the main content of the site, written in [Markdown](#markdown).
: Pages should be organized in a manner that reflects the rendered website.
: Pages can be organized in _Sections_ (root folders) (e.g.: “Blog“, “Project“, etc.).

**Assets**
: Assets are manipulated files (i.e.: resized images, compiled Sass, minified scripts, etc.) with the template [`asset()`](3-Templates.md#asset) function.

**Static files**
: Static files are copied as is in the built site (e.g.: `static/file.pdf` -> `file.pdf`).

**Data files**
: Data files are custom variables collections, exposed in [templates](3-Templates.md) with [`site.data`](3-Templates.md#site-data).

## Files organization

### File system tree

Project files organization.

```plaintext
<mywebsite>
├─ pages
|  ├─ blog            <- Section
|  |  ├─ post-1.md    <- Page in Section
|  |  └─ post-2.md
|  ├─ projects
|  |  └─ project-a.md
|  └─ about.md        <- Root page
├─ assets
|  ├─ styles.scss     <- Asset file
|  └─ logo.png
├─ static
|  └─ file.pdf        <- Static file
└─ data
   └─ authors.yml     <- Data collection
```

### Built website tree

Result of the build.

```plaintext
<mywebsite>
└─ _site
   ├─ index.html               <- Generated home page
   ├─ blog/
   |  ├─ index.html            <- Generated list of posts
   |  ├─ post-1/index.html     <- A blog post
   |  └─ post-2/index.html
   ├─ projects/
   |  ├─ index.html
   |  └─ project-a/index.html
   ├─ about/index.html
   ├─ styles.css
   ├─ logo.png
   └─ file.pdf
```

:::info
By default each page is generated as `slugified-filename/index.html` to get a “beautiful“ URL like `https://mywebsite.tld/section/slugified-filename/`.

To get an “ugly” URL (like `404.html` instead of `404/`), set `uglyurl: true` in page [front matter](#front-matter).
:::

### File based routing

Markdown files in the `pages` directory enable file based routing. Meaning that adding a `pages/my-projects/project-a.md` for instance will make it available at `/project-a` in your browser.

```plaintext
File:
                   pages/my-projects/project-a.md
                        └───── filepath ──────┘
URL:
    ┌───── baseurl ─────┬─────── path ────────┐
     https://example.com/my-projects/project-a/index.html
                        └─ section ─┴─ slug ──┘
```

:::important
Two kind of prefix can alter URL, see [File prefix section](#file-prefix) below.
:::

## Pages

A page is a file made up of a [**front matter**](#front-matter) and a [**body**](#body).

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
You can also use `<!-- -->` or `+++` as separator.
:::

### Body

_Body_ is the main content of a page, it could be written in [Markdown](#markdown) or in plain text.

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
This is an advice.
:::
```

## Markdown

Cecil supports [Markdown](http://daringfireball.net/projects/markdown/syntax) format, but also [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/).

Cecil also provides **extra features** to enhance your content, see below.

### Attributes

With [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/) you can set an id, class and custom attributes on certain elements using an attribute block.  
For instance, put the desired attribute(s) after a header, a fenced code block, a link or an image at the end of the line inside curly brackets, like this:

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

### Links

You can create a link with the syntax `[Text](url)` with "url" can be a path, a relative path to a Markdown file, an external URL, etc.

_Example:_

```markdown
[Link to a path](/about/)
[Link to a Markdown file](../about.md)
[Link to Cecil website](https://cecil.app)
```

#### Link to a page

You can easily create a link to a page with the syntax `[Page title](page:page-id)`.

_Example:_

```markdown
[Link to a blog post](page:blog/post-1)
```

#### External

By default external links have the following value for `rel` attribute: "noopener noreferrer nofollow".

_Example:_

```html
<a href="<url>" rel="noopener noreferrer nofollow">Link to another website</a>
```

You can change this behavior with [`pages.body.links.external` options](4-Configuration.md#body).

#### Embedded links

You can let Cecil tries to turns a link into an embedded content by using the `{embed}` attribute or by setting the global configuration option `pages.body.links.embed.enabled` to `true`.

_Example:_

```markdown
[An example YouTube video](https://www.youtube.com/watch?v=Dj-rKHmLp5w){embed}
```

:::important
Only **YouTube** and **GitHub Gits** links are supported for the moment.
:::

Cecil can also create a video or audio HTML elements, through the file extension.

##### Video

_Example:_

```markdown
[The video](/video/test.mp4){embed controls poster=/images/video-test.png style="width:100%;"}
```

Is converted to:

```html
<video src="/video/test.mp4" controls poster="/images/video-test.png" style="width:100%;"></video>
```

##### Audio

_Example:_

```markdown
[The audio file](/audio/test.mp3){embed controls}
```

Is converted to:

```html
<audio src="/video/test.mp3" controls></audio>
```

### Images

To add an image, use an exclamation mark (`!`) followed by alternative description in brackets (`[]`), and the path or URL to the image in parentheses (`()`).  
You can optionally add a title in quotation marks.

```markdown
![Alternative description](/image.jpg "Image title")
```

:::info
The path should be relative to the root of your website (e.g.: `/image.jpg`), however Cecil is able to normalize a path relative to _assets_ and _static_ directories (e.g.: `../../assets/image.jpg`).
:::

#### Lazy loading

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
You can disable this behavior with the attribute `{loading=eager}` or with the [`lazy` option](4-Configuration.md#body).
:::

#### Decoding

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
You can disable this behavior with the attribute `{decoding=auto}` or with the [`decoding` option](4-Configuration.md#body).
:::

#### Resize

Each image in the _body_ can be resized automatically by setting a smaller width than the original one, with the extra attribute `{width=X}` (the [`resize` option](4-Configuration.md#body) must be enabled).

_Example:_

```markdown
![](/image.jpg){width=800}
```

Is converted to:

```html
<img src="/assets/thumbnails/800/image.jpg" width="800" height="600">
```

:::info
Ratio is preserved (`height` attribute is calculated automatically), the original file is not altered and the resized version is stored in `/assets/thumbnails/<width>/`.
:::

:::important
This feature requires [GD extension](https://www.php.net/manual/book.image.php) (otherwise it only add a `width` HTML attribute to the `img` tag).
:::

#### Formats

If the [`formats` option](4-Configuration.md#body) is defined, alternatives images are created and added.

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

#### Responsive

If the [`responsive` option](4-Configuration.md#body) is enabled, then all images in the _body_ will be automatically "responsived".

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
Because a body image is converted into an [Asset](3-Templates.md#asset), the different widths must be defined in [assets configuration](4-Configuration.md#assets).
:::

The `sizes` attribute take the value of the `assets.images.responsive.sizes.default` configuration option, but can be changed by creating a new entry named with a _class_ added to the image.

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

#### CSS class

You can set a default value to the `class` attribute of each image with the [`class` option](4-Configuration.md#body).

#### Caption

The optional title can be used to create a caption (`figcaption`) automatically by enabling the [`caption` option](4-Configuration.md#body).

_Example:_

```markdown
![](/images/img.jpg "Title")
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

#### Placeholder

As images are typically heavier and slower resources, and they don’t block rendering, we should attempt to give users something to look at while they wait for the image to arrive.

The `placeholder` attribute accept 2 options:

1. `color`: display a colored background (based on image dominant color)
2. `lqip`: [Low-Quality Image Placeholder](https://www.guypo.com/introducing-lqip-low-quality-image-placeholders)

_Examples:_

```markdown
![](/images/img.jpg){placeholder=color}
![](/images/img.jpg){placeholder=lqip}
```

:::tips
You can set a value to the `placeholder` attribute for each image with the [`placeholder` option](4-Configuration.md#body).
:::

:::warning
The `lqip` option is not compatible with animated GIF.
:::

### Table of contents

You can add a table of contents with the following Markdown syntax:

```markdown
[toc]
```

:::info
By default the ToC extract H2 et H3 headers. You can change this behavior with [body options](4-Configuration.md#body).
:::

### Excerpt

An excerpt can be defined in the _body_ with one of those following tags: `excerpt` or `break`.

_Example:_

```html
Introduction.
<!-- excerpt -->
Main content.
```

Then use the [`excerpt_html` filter](3-Templates.md#excerpt-html) in your template.

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
<aside class="note note-tip">
  <p>
    <strong>Tip:</strong> This is an advice.
  </p>
</aside>
```

:::tip
**Tip:** This is an advice.
:::

_Others examples:_

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

### Syntax highlight

Enables code block syntax highlighter by setting the [pages.body.highlight.enabled](4-Configuration.md#body) option to `true`.

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

:::important
You must add the [StyleSheet](https://highlightjs.org/download/) in the head of your template.
:::

### Inserted text

Represents a range of text that has been added.

```markdown
++text++
```

Is converted to:

```html
<ins>text</ins>
```

## Variables

The _front matter_ can contains custom variables applied to the current page.

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
All the predefined variables can be overridden except `section`.
:::

### menu

A page can be added to a [menu](4-Configuration.md#menus).

The entry name is the page `title` and the URL is the page `path`.

A same page could be added to severals menus, and the position of each entry can be defined with the `weight` key (the lightest first).

_Examples:_

```yaml
---
menu: main
---
```

```yaml
---
menu: [main, navigation] # same page in multiple menus
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

Taxonomy allows you to connect, relate and classify your website’s content.  
In Cecil, these terms are gathered within vocabularies.

Vocabularies are declared in the [_Configuration_](4-Configuration.md#taxonomies).

A page can contain several vocabularies (e.g.: `tags`) and terms (e.g.: `Tag 1`).

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
redirect: "https://arnaudligny.fr"
---
```

:::info
Redirect works with the [`redirect.html.twig`](https://github.com/Cecilapp/Cecil/blob/master/resources/layouts/redirect.html.twig) template.
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

### output

Defines the output (rendered) format(s). See [`formats` configuration](4-Configuration.md#formats) for more details.

_Example:_

```yaml
---
output: [html, atom]
---
```

### external

A page with an `external` variable try to fetch the content of the pointed resource.

_Example:_

```yaml
---
external: "https://raw.githubusercontent.com/Cecilapp/Cecil/master/README.md"
---
```

### File prefix

The filename can contain a prefix to define `date` or `weight` variables of the page (used by [`sortby`](3-Templates.md#sort-by-date)).

:::info
Available prefix separator are `_` and `-`.
:::

#### date

The _date prefix_ is used to set the `date` of the page, and must be a valid date format (i.e.: « YYYY-MM-DD »).

_Example:_

In « 2019-04-23_My blog post.md »:

- the prefix is « 2019-04-23 »
- the `date` of the page is « 2019-04-23 »
- the `title` of the page is « My blog post »

#### weight

The _weight prefix_ is used to set the sort order of the page, and must be a valid integer value.

_Example:_

In « 1_The first project.md »:

- the prefix is « 1 »
- the `weight` of the page is « 1 »
- the `title` of the page is « The first project »

### Section

Some dedicated variables can be used in a custom _Section_ (i.e.: `<section>/index.md`).

#### sortby

The order of pages in a _Section_ can be changed.

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

**More options:**

```yaml
---
sortby:
  variable: date    # "date", "updated", "title" or "weight"
  desc_title: false # used with "date" or "updated" variable value to sort by desc title order if items have the same date
  reverse: false    # reversed if true
---
```

#### pagination

Global [pagination configuration](4-Configuration.md#pagination) can be overridden in each _Section_.

_Example:_

```yaml
---
pagination:
  max: 5
  path: "page"
---
```

#### cascade

Any variables in `cascade` are added to the front matter of all _sub pages_.

_Example:_

```yaml
---
cascade:
  banner: image.jpg
---
```

:::info
Existing variables are not overridden.
:::

#### circular

Set `circular` to `true` to enable circular navigation with [_page.<prev/next>_](3-Templates.md#page-prev-next).

_Example:_

```yaml
---
circular: true
---
```

### Home page

Like another section, _Home page_ support `sortby` and `pagination` configuration.

#### pagesfrom

Set a valid _Section_ name in `pagesfrom` to use pages collection from this _Section_ in _Home page_.

_Example:_

```yaml
---
pagesfrom: blog
---
```

### exclude

Set `exclude` to `true` to hide a page from list pages (i.e.: _Home page_, _Section_, _Sitemap_, etc.).

_Example:_

```yaml
---
exclude: true
---
```

:::info
`exclude` is different from [`published`](#predefined): an excluded page is published but hidden from list pages.
:::

## Multilingual

If your pages are available in multiple [languages](4-Configuration.md#languages) there is 2 differents ways to define it:

### Language in the file name

This is the common way to translate a page from the main [language](4-Configuration.md#language) to another language.

You just need to duplicate the reference page and suffix it with the target language `code` (e.g.: `fr`).

_Example:_

```plaintext
├─ about.md    # the reference page
└─ about.fr.md # the french version (`fr`)
```

:::tip
You can change the URL of the translated page with the `slug` variable in the front matter. For example:

```yml
---
slug: a-propos
---
# about.md    -> /about/
# about.fr.md -> /fr/a-propos/
```

:::

### Language in the front matter

If you want to create a page in a language other than the main language, without it being a translation of an existing page, you can use the `language` variable in its front matter.

_Example:_

```yml
---
language: fr
---
```

### Link translations of a page

Each translated page reference the pages in others languages.

Those pages collection is available in [templates](3-Templates.md#page) with the following variable:

```twig
{{ page.translations }}
```

:::info
The `langref` variable is provided by default, but you can change it in the front matter:

```yml
---
langref: my-page-ref
---
```

:::

## Dynamic content

With this **_experimental_** feature you can use **[variables](3-Templates.md#variables)** and **shortcodes** in the [body](#body).

:::important
To do this you must include a specific template:

```twig
{{ include(page.content_template) }}
```

(instead of `{{ page.content }}`)
:::

### Display variables

Front matter variables can be use in the body with the template’s syntax `{{ page.variable }}`.

_Example:_

```twig
--
var: 'value'
---
The value of `var` is {{ page.var }}.
```

> Experimental

### Shortcodes

Shortcodes are helpers to create dynamic content.

> Experimental

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
{{ shortcode.gist('ArnaudLigny', 'fbe791e05b93951ffc1f6abda8ee88f0') }}
```

#### Custom shortcode

A shortcode is a [Twig macro](https://twig.symfony.com/doc/tags/macro.html) you must add in a template named `shortcodes.twig`.

_Example:_

`shortcodes.twig`:

```twig
{% extends 'extended/macros.twig' %}

{% block macros %}

{# the "foo" shortcode #}
{% macro foo(bar = 'bar') %}
<strong>{{ bar }}</strong>
{% endmacro %}

{% endblock %}
```
