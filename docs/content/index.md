<!--
title: "Content"
description: "Create and organize your content: pages, front matter, Markdown, multilingual and dynamic content."
date: 2021-05-07
updated: 2026-10-03
weight: 2
sortby: weight
-->
# Content

There are different kinds of content in Cecil:

**Pages**
: Pages are the main content of the site, written in [Markdown](7-markdown.md).
: Pages should be organized in a manner that reflects the rendered website.
: Pages can be organized in _Sections_ (root folders) (e.g.: “Blog“, “Project“, etc.).

**Assets**
: Assets are manipulated files (i.e.: resized images, compiled Sass, minified scripts, etc.) with the template [`asset()`](../assets/index.md#asset) function.

**Static files**
: Static files are copied as is in the built site (e.g.: `static/file.pdf` -> `file.pdf`).

**Data files**
: Data files are custom variables collections, exposed in [templates](../templates/index.md) with [`site.data`](../templates/11-variables.md#site-data).
