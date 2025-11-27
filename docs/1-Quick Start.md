<!--
description: "Create a new website and preview it locally."
date: 2020-12-19
updated: 2025-11-27
menu: home
-->
# Quick Start

Cecil is a CLI application, powered by [PHP](https://www.php.net), that merge plain text files (written in [Markdown](https://daringfireball.net/projects/markdown/)), images and [Twig](https://twig.symfony.com/) templates to generate a [static website](https://en.wikipedia.org/wiki/Static_web_page).

## Create a website

You can create a new website from scratch in a few minutes.

Follow the steps below to create your first Cecil website.

[![New website example](/docs/cecil-newsite.png)](https://cecilapp.github.io/skeleton/)

:::info
Demo of the expected result: <https://cecilapp.github.io/skeleton/>.
:::

### Prerequisites

- [PHP](https://php.net/manual/en/install.php) 8.1+
- Terminal (a basic understanding of [terminal](https://wikipedia.org/wiki/Terminal_emulator))
- Text editor, like [VS Code](https://code.visualstudio.com) and/or [Typora](https://typora.io)

### 1. Download Cecil

Download `cecil.phar` from your terminal:

```bash
curl -LO https://cecil.app/cecil.phar
```

You can also [download Cecil](https://cecil.app/download/) manually, or use:

- [Homebrew](https://brew.sh): `brew install cecilapp/tap/cecil`
- [Scoop](https://scoop.sh): `scoop install https://cecil.app/scoop/cecil.json`

### 2. Create a new site

Create a directory for the website (e.g.: `<mywebsite>`), put `cecil.phar` in it, then run the `new:site` command:

```bash
php cecil.phar new:site
```

### 3. Add a page

Run the `new:page` command:

```bash
php cecil.phar new:page --name=my-first-page.md
```

Now you can edit the newly created page with your Markdown editor: `<mywebsite>/pages/my-first-page.md`.

:::tip
We recommend you to use [Typora](https://www.typora.io) to edit your Markdown files.
:::

### 4. Check the preview

Run the following command to create a preview of the website:

```bash
php cecil.phar serve
```

Then navigate to `http://localhost:8000`.

:::info
The `serve` command run a local HTTP server and a watcher: if a file (a page, a template or the config) is modified, the browser’s current page is automatically reloaded.
:::

### 5. Build and deploy

When you are satisfied with the result, you can generate the website in order to deploy it on the Web.

Run the following command to build the website:

```bash
php cecil.phar build
```

You can now copy the content of the `_site` directory to a Web server 🎉

----

## Create a blog

If you want to create a no-hassle blog, quickly, the [starter blog](https://github.com/Cecilapp/the-butler#readme) is for you.

[![New blog example](/docs/cecil-newblog.png)](https://github.com/Cecilapp/the-butler#readme)

The easiest way to deploy and manage your blog is certainly with [Netlify](https://cecil.app/hosting/netlify/deploy/) or [Vercel](https://cecil.app/hosting/vercel/deploy/).

### Deploy to Netlify

[![Deploy to Netlify](https://www.netlify.com/img/deploy/button.svg)](https://cecil.app/hosting/netlify/deploy/)

### Deploy to Vercel

[![Deploy to Vercel](https://vercel.com/button/default.svg)](https://cecil.app/hosting/vercel/deploy/)
