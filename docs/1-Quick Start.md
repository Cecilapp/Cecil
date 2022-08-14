<!--
description: "Create a new site and preview it locally."
date: 2020-12-19
updated: 2022-03-02
menu: home
-->

# Quick Start

Cecil is a CLI application, powered by [PHP](https://www.php.net), that merge plain text files (written in [Markdown](https://daringfireball.net/projects/markdown/)), images and [Twig](https://twig.symfony.com/) templates to generate a [static website](https://en.wikipedia.org/wiki/Static_web_page).

## Create a blog

If you want to create a no-hassle blog, the [starter blog](https://github.com/Cecilapp/the-butler#readme) is for you.

The easiest way to deploy and manage your blog is certainly with [Netlify](https://www.netlify.com) + [Netlify CMS](https://www.netlifycms.org) or [Vercel](https://vercel.com).

[![Deploy to Netlify](https://www.netlify.com/img/deploy/button.svg "Deploy to Netlify")](https://cecil.app/hosting/netlify/deploy/) [![Deploy to Vercel](https://vercel.com/button/default.svg "Deploy to Vercel")](https://cecil.app/hosting/vercel/deploy/)

If your goal is managing content quickly, and decide later where to deploy it, let [Forestry CMS](https://forestry.io) guide you.

[![Import into Forestry](https://assets.forestry.io/import-to-forestryK.svg)](https://cecil.app/cms/forestry/import/ "Import into Forestry")

----

## Create a website

How to create create a website – from scratch – in a few steps.

### Install Cecil

Download `cecil.phar` from your terminal:

```bash
curl -LO https://cecil.app/cecil.phar
```

You can also [download Cecil](https://cecil.app/download/) manually from the website.

> [PHP](https://php.net/manual/en/install.php) 7.4+ is required.

### Create a new website

Run the `new:site` command:

```bash
php cecil.phar new:site <mywebsite>
```

### Add a page

Run the `new:page` command:

```bash
php cecil.phar new:page my-first-page.md <mywebsite>
```

Now you can edit the newly created page with your favorite Markdown editor (I recommend [Typora](https://www.typora.io)): `<mywebsite>/pages/my-first-page.md`.

### Check the preview

Run the following command to create a preview of the website:

```bash
php cecil.phar serve <mywebsite>
```

Then navigate to `http://localhost:8000`.

:::info
The `serve` command run a local HTTP server and a watcher: if a file (a page, a template or the config) is modified, the browser’s current page is reloaded.
:::

### Build and deploy

When you are satisfied with the result, you can generate the website in order to deploy it on the web.

Run the following command to build the website:

```bash
php cecil.phar build <mywebsite>
```

You can now copy the content of the `_site` directory to your web server 🎉
