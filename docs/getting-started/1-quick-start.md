<!--
title: "Quick start"
description: "Install Cecil, create a new website, add pages, preview it locally, then build and deploy it."
date: 2020-12-19
updated: 2026-10-16
alias: documentation/quick-start
menu: home
-->
# Quick start

This guide walks you through creating your first Cecil website, previewing it locally, then building and deploying it.

## Create a website

You can create a new website from scratch in a few minutes.

Follow the steps below to create your first Cecil website.

[![New website example](/docs/cecil-newsite.png)](https://cecilapp.github.io/skeleton/)

:::info
Demo of the expected result: <https://cecilapp.github.io/skeleton/>.
:::

### Prerequisites

- [PHP](https://php.net/manual/en/install.php) 8.3+
- Terminal (a basic understanding of the [terminal](https://wikipedia.org/wiki/Terminal_emulator))
- Text editor, such as [VS Code](https://code.visualstudio.com) and/or [Typora](https://typora.io)

### Step by step

::::steps
#### Download Cecil

Download `cecil.phar` from your terminal:

```bash
curl -LO https://cecil.app/cecil.phar
```

You can also [download Cecil manually](https://cecil.app/download/), or install it globally with a package manager, then run `cecil` instead of `php cecil.phar`:

:::code-group
#### Homebrew

```bash
brew install cecilapp/tap/cecil
```

#### Scoop

```bash
scoop install https://cecil.app/scoop/cecil.json
```
:::

See [Installation](2-installation.md) for other methods.

#### Create a new site

Create a directory for the website (e.g. `<mywebsite>`), put `cecil.phar` in it, then run the `new:site` command:

```bash
php cecil.phar new:site
```

#### Add a page

Run the `new:page` command:

```bash
php cecil.phar new:page
```

Now you can edit the newly created page with your Markdown editor: `<mywebsite>/pages/<new-page>.md`.

:::tip
We recommend using [Typora](https://www.typora.io) to edit your Markdown files.
:::

#### Check the preview

Run the following command to create a preview of the website:

```bash
php cecil.phar serve
```

Then navigate to `http://localhost:8000`.

:::info
The `serve` command runs a local HTTP server and a watcher: if a file (a page, a template or the config) is modified, the browser’s current page is automatically reloaded.
:::

#### Build and deploy

When you are satisfied with the result, you can generate the website in order to deploy it to the Web.

Run the following command to build the website:

```bash
php cecil.phar build
```

You can now copy the content of the `_site` directory to a Web server 🎉
::::

:::tip
To deploy automatically on each push to your Git repository, see [Deploy](../deploy/index.md).
:::

----
