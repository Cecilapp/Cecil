<!--
title: "Directory structure"
description: "How source files are organized, how the built website looks like and how files are routed to URLs."
date: 2021-05-07
updated: 2026-10-03
-->
# Directory structure

## File system tree

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

## Built website tree

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

To get an “ugly” URL (like `404.html` instead of `404/`), set `uglyurl: true` in page [front matter](../content/5-pages.md#front-matter).
:::

## File based routing

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
Two kinds of prefixes can alter the URL. See the [File prefix section](../content/5-pages.md#file-prefix) below.
:::
