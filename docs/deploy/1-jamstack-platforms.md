<!--
title: "Jamstack platforms"
description: "Netlify, Vercel, statichost, Cloudflare Pages, Render."
date: 2020-12-19
updated: 2026-10-10
-->
# Jamstack platforms

Jamstack platforms can build and deploy your Cecil site automatically on each push to your Git repository.

## Netlify

> A powerful serverless platform with an intuitive git-based workflow. Automated deployments, shareable previews, and much more.

➡️ <https://www.netlify.com>

_netlify.toml_:

```bash
[build]
  publish = "_site"
  command = "curl -sSOL https://cecil.app/build.sh && bash ./build.sh"
[context.production.environment]
  CECIL_ENV = "production"
[context.deploy-preview.environment]
  CECIL_ENV = "preview"
```

[Official documentation](https://www.netlify.com/docs/continuous-deployment/)

## Vercel

> Vercel combines the best developer experience with an obsessive focus on end-user performance.

➡️ <https://vercel.com>

_vercel.json_:

```json
{
  "buildCommand": "curl -sSOL https://cecil.app/build.sh && bash ./build.sh",
  "outputDirectory": "_site"
}
```

[Official documentation](https://vercel.com/docs/concepts/deployments/build-step#build-command)

## statichost

> Modern static site hosting with European servers and absolutely no personal data collection!

➡️ <https://statichost.eu>

_statichost.yml_:

```yml
image: wordpress:cli-php8.4
command: curl -sSOL https://cecil.app/build.sh && bash ./build.sh
public: _site
```

[Official documentation](https://www.statichost.eu/docs/)

## Cloudflare Pages

:::caution
Cloudflare Pages no longer supports PHP.
:::

> Cloudflare Pages is a JAMstack platform for frontend developers to collaborate and deploy websites.

➡️ <https://pages.cloudflare.com>

Build configurations:

- Framework preset: `None`
- Build command: `curl -sSOL https://cecil.app/build.sh && bash ./build.sh`
- Build output directory: `_site`

[Official documentation](https://developers.cloudflare.com/pages/)

## Render

:::caution
Render no longer supports PHP.
:::

> Render is a unified cloud to build and run all your apps and websites with free TLS certificates, global CDN, private networks and auto deploys from Git.

➡️ <https://render.com>

_render.yaml_:

```yml
previewsEnabled: true
services:
  - type: web
    name: Cecil
    env: static
    buildCommand: curl -sSOL https://cecil.app/build.sh && bash ./build.sh
    staticPublishPath: _site
    pullRequestPreviewsEnabled: true
```

[Official documentation](https://render.com/docs/static-sites)
