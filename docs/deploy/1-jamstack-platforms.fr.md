<!--
title: "Plateformes Jamstack"
description: "Netlify, Vercel, statichost, Cloudflare Pages, Render."
date: 2026-03-27
updated: 2026-10-10
path: documentation/deployer/plateformes-jamstack
-->
# Plateformes Jamstack

Les plateformes Jamstack peuvent construire et déployer automatiquement votre site Cecil à chaque push dans votre dépôt Git.

## Netlify

> Une puissante plateforme serverless avec un flux de travail intuitif basé sur Git. Déploiements automatisés, aperçus partageables, et bien plus encore.

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

[Documentation officielle](https://www.netlify.com/docs/continuous-deployment/)

## Vercel

> Vercel associe une excellente expérience développeur à une attention obsessionnelle portée aux performances côté utilisateur final.

➡️ <https://vercel.com>

_vercel.json_:

```json
{
  "buildCommand": "curl -sSOL https://cecil.app/build.sh && bash ./build.sh",
  "outputDirectory": "_site"
}
```

[Documentation officielle](https://vercel.com/docs/concepts/deployments/build-step#build-command)

## statichost

> Hébergement moderne de sites statiques avec des serveurs européens et absolument aucune collecte de données personnelles !

➡️ <https://statichost.eu>

_statichost.yml_:

```yml
image: wordpress:cli-php8.4
command: curl -sSOL https://cecil.app/build.sh && bash ./build.sh
public: _site
```

[Documentation officielle](https://www.statichost.eu/docs/)

## Cloudflare Pages

:::caution
Cloudflare Pages ne prend plus en charge PHP.
:::

> Cloudflare Pages est une plateforme JAMstack qui permet aux développeurs frontend de collaborer et de déployer des sites web.

➡️ <https://pages.cloudflare.com>

Configurations de build :

- Préréglage de framework : `None`
- Commande de build : `curl -sSOL https://cecil.app/build.sh && bash ./build.sh`
- Répertoire de sortie du build : `_site`

[Documentation officielle](https://developers.cloudflare.com/pages/)

## Render

:::caution
Render ne prend plus en charge PHP.
:::

> Render est un cloud unifié pour créer et exécuter toutes vos applications et tous vos sites web, avec certificats TLS gratuits, CDN global, réseaux privés et déploiements automatiques depuis Git.

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

[Documentation officielle](https://render.com/docs/static-sites)
