<!--
description: "Create a new static site and preview it locally."
date: 2020-12-19
menu: home
-->

# Quick Start

Cecil is a CLI application, powered by [PHP](https://www.php.net), that merge plain text files (written in [Markdown](https://daringfireball.net/projects/markdown/)), images and [Twig](https://twig.symfony.com/) templates to generate a [static website](https://en.wikipedia.org/wiki/Static_web_page).

## Create a blog

If you want to create a no-hassle blog, the [starter blog](https://github.com/Cecilapp/the-butler#readme) is for you!

Click on the button below and let [Forestry CMS](https://forestry.io) guide you.

[![Import this project into Forestry](https://assets.forestry.io/import-to-forestryK.svg)](https://cecil.app/cms/forestry/import/)

----

## Create a website

Create a website – from scratch – in 4 steps!

### Step 1: Install Cecil

Download `cecil.phar` from your terminal:

```bash
curl -LO https://cecil.app/cecil.phar
```

You can also [download Cecil](https://cecil.app/download/) manually from the website.

> [PHP](http://php.net/manual/en/install.php) 7.1+ is required.

### Step 2: Create a new website

Run the `new:site` command:

```bash
php cecil.phar new:site <mywebsite>
```

### Step 3: Add some content

Run the `new:page` command:

```bash
php cecil.phar new:page blog/my-first-post.md <mywebsite>
```

Now you can edit the newly created page with your favorite Markdown editor (I recommend [Typora](https://www.typora.io)): `<mywebsite>/content/blog/my-first-post.md`.

### Step 4: Check the preview

Run the following command to build and serve the website:

```bash
php cecil.phar serve --drafts <mywebsite>
```

Then navigate to your new website at `http://localhost:8000`.

**Notes:**

- `serve` command run a local HTTP server and a watcher: if a file (a page, a template or the config) is modified, the browser’s current page is reloaded.
- `--drafts` option is used to include drafts.
