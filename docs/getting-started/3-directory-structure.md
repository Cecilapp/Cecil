<!--
title: "Directory structure"
description: "How source files are organized, what the built website looks like and how files are routed to URLs."
date: 2021-05-07
updated: 2026-10-16
-->
# Directory structure

This page describes how a Cecil project is organized, what the built website looks like and how files are routed to URLs.

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
   |  ├─ index.html            <- Generated list of projects
   |  └─ project-a/index.html  <- A project
   ├─ about/index.html         <- “About” page
   ├─ styles.css               <- Compiled asset
   ├─ logo.png
   └─ file.pdf
```

:::info
By default, each page is generated as `slugified-filename/index.html` to get a “beautiful” URL like `https://mywebsite.tld/section/slugified-filename/`.

To get an “ugly” URL (like `404.html` instead of `404/`), set `uglyurl: true` in the page’s [front matter](../content/1-pages.md#front-matter).

Files of the `assets` directory are published only when they are used in templates (with the `asset()` function), whereas files of the `static` directory are copied as is.
:::

## File-based routing

Markdown files in the `pages` directory enable file-based routing. This means that adding `pages/my-projects/project-a.md`, for instance, makes it available at `/my-projects/project-a/` in your browser.

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
Two kinds of prefixes can alter the URL. See the [File prefix section](../content/1-pages.md#file-prefix).
:::
