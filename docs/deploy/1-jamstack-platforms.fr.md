<!--
title: "Plateformes Jamstack"
description: "Netlify, Vercel, statichost, Cloudflare Pages, Render."
date: 2026-03-27
updated: 2026-10-25
path: documentation/deployer/plateformes-jamstack
-->
# Plateformes Jamstack

Les plateformes Jamstack peuvent générer et déployer automatiquement votre site Cecil à chaque push dans votre dépôt Git.

Le script de build déduit le contexte de build (`production` ou `preview`) et l’URL de base à partir de la plateforme. Une variable d’environnement `CECIL_ENV` que vous définissez est prioritaire sur le contexte de build déduit.

## Netlify

> Une puissante plateforme serverless avec un flux de travail intuitif basé sur Git. Déploiements automatisés, aperçus partageables, et bien plus encore.

➡️ <https://www.netlify.com>

_netlify.toml_ :

```toml
[build]
  publish = "_site"
  command = "curl -sSOL https://cecil.app/build.sh && bash ./build.sh"
[context.production.environment]
  CECIL_ENV = "production"
[context.deploy-preview.environment]
  CECIL_ENV = "preview"
```

[Documentation officielle](https://docs.netlify.com/build/configure-builds/overview/)

## Vercel

> Vercel associe une excellente expérience développeur à une attention obsessionnelle portée aux performances côté utilisateur final.

➡️ <https://vercel.com>

_vercel.json_ :

```json
{
  "buildCommand": "curl -sSOL https://cecil.app/build.sh && bash ./build.sh",
  "outputDirectory": "_site"
}
```

[Documentation officielle](https://vercel.com/docs/builds/configure-a-build)

## statichost

> Hébergement moderne de sites statiques avec des serveurs européens et absolument aucune collecte de données personnelles !

➡️ <https://statichost.eu>

_statichost.yml_ :

```yml
image: wordpress:cli-php8.4
command: curl -sSOL https://cecil.app/build.sh && bash ./build.sh
public: _site
```

[Documentation officielle](https://www.statichost.eu/docs/)

## Cloudflare Pages

:::info
Cloudflare Pages ne fournit pas PHP : le script de build utilise alors le [binaire autonome](../getting-started/2-installation.fr.md#standalone-binary) de Cecil, et installe les thèmes à partir de `composer.lock`.

En production (branche `main` ou `master`), le `baseurl` de la configuration du site est utilisé : les déploiements de prévisualisation utilisent leur propre URL.
:::

> Cloudflare Pages est une plateforme Jamstack qui permet aux développeurs frontend de collaborer et de déployer des sites web.

➡️ <https://www.cloudflare.com/products/pages/>

Configuration du build :

- Préréglage de framework : `None`
- Commande de build : `curl -sSOL https://cecil.app/build.sh && bash ./build.sh`
- Répertoire de sortie du build : `_site`

[Documentation officielle](https://developers.cloudflare.com/pages/)

## Render

:::info
Render ne fournit que PHP 7.3 : le script de build utilise alors le [binaire autonome](../getting-started/2-installation.fr.md#standalone-binary) de Cecil, et installe les thèmes à partir de `composer.lock`.

En production, le `baseurl` de la configuration du site est utilisé : les prévisualisations de pull request utilisent leur propre URL.
:::

> Render est un cloud unifié pour créer et exécuter toutes vos applications et tous vos sites web, avec certificats TLS gratuits, CDN global, réseaux privés et déploiements automatiques depuis Git.

➡️ <https://render.com>

_render.yaml_ :

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

[Documentation officielle](https://render.com/docs/static-sites)
