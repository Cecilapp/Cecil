<!--
title: "Jamstack platforms"
description: "Netlify, Vercel, statichost, Cloudflare Pages, Render."
date: 2020-12-19
updated: 2026-10-25
-->
# Jamstack platforms

Jamstack platforms can build and deploy your Cecil site automatically on each push to your Git repository.

The build script deduces the build context (`production` or `preview`) and the base URL from the platform. A `CECIL_ENV` environment variable you define takes precedence over the deduced build context.

## Netlify

> A powerful serverless platform with an intuitive git-based workflow. Automated deployments, shareable previews, and much more.

➡️ <https://www.netlify.com>

_netlify.toml_:

```toml
[build]
  publish = "_site"
  command = "curl -sSOL https://cecil.app/build.sh && bash ./build.sh"
[context.production.environment]
  CECIL_ENV = "production"
[context.deploy-preview.environment]
  CECIL_ENV = "preview"
```

[Official documentation](https://docs.netlify.com/build/configure-builds/overview/)

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

[Official documentation](https://vercel.com/docs/builds/configure-a-build)

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

:::info
Cloudflare Pages doesn’t provide PHP: the build script uses the Cecil [standalone binary](../getting-started/2-installation.md#standalone-binary) instead, and installs themes from `composer.lock`.

In production (`main` or `master` branch), the `baseurl` of the site configuration is used: preview deployments use their own URL.
:::

> Cloudflare Pages is a Jamstack platform for frontend developers to collaborate and deploy websites.

➡️ <https://www.cloudflare.com/products/pages/>

Build configurations:

- Framework preset: `None`
- Build command: `curl -sSOL https://cecil.app/build.sh && bash ./build.sh`
- Build output directory: `_site`

[Official documentation](https://developers.cloudflare.com/pages/)

## Render

:::info
Render only provides PHP 7.3: the build script uses the Cecil [standalone binary](../getting-started/2-installation.md#standalone-binary) instead, and installs themes from `composer.lock`.

In production, the `baseurl` of the site configuration is used: pull request previews use their own URL.
:::

> Render is a unified cloud to build and run all your apps and websites with free TLS certificates, global CDN, private networks and auto deploys from Git.

➡️ <https://render.com>

_render.yaml_:

```yml
services:
  - type: web
    runtime: static
    name: Cecil
    buildCommand: curl -sSOL https://cecil.app/build.sh && bash ./build.sh
    staticPublishPath: ./_site
    autoDeployTrigger: commit
    previews:
      generation: automatic
```

[Official documentation](https://render.com/docs/static-sites)
