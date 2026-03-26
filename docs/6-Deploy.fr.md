<!--
description: "Déployer (publier) votre site web."
date: 2020-12-19
updated: 2026-01-14
alias: documentation/publish
-->
# Déployer

Par défaut, votre site statique est généré dans le répertoire **`_site`** et **peut être déployé tel quel**.

Vous trouverez ci-dessous quelques recettes pour automatiser la génération et/ou le déploiement d’un site statique.

## Plateformes Jamstack

### Netlify

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

### Vercel

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

### statichost

> Hébergement moderne de sites statiques avec des serveurs européens et absolument aucune collecte de données personnelles !

➡️ <https://statichost.eu>

_statichost.yml_:

```yml
image: wordpress:cli-php8.4
command: curl -sSOL https://cecil.app/build.sh && bash ./build.sh
public: _site
```

[Documentation officielle](https://www.statichost.eu/docs/)

### Cloudflare Pages

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

### Render

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

## Build et déploiement continus

### GitHub Pages

> Des sites web pour vous et vos projets, hébergés directement depuis votre dépôt GitHub. Il suffit de modifier, pousser, et vos changements sont en ligne.

➡️ <https://pages.github.com>

_.github/workflows/build-and-deploy.yml_:

```yml
name: Build and deploy to GitHub Pages

on:
  push:
    branches: [master, main]
  workflow_dispatch:

concurrency:
  group: pages
  cancel-in-progress: true

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout source
        uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: pre-installed
          extensions: mbstring, fileinfo, gd, imagick, intl, gettext
      - name: Restore Cecil cache
        uses: actions/cache/restore@v4
        with:
          path: ./.cache
          key: cecil-cache-
          restore-keys: |
            cecil-cache-
      - name: Setup Pages
        id: pages
        uses: actions/configure-pages@v5
      - name: Build with Cecil
        uses: Cecilapp/Cecil-Action@v3
        with:
          args: '-v --baseurl="${{ steps.pages.outputs.base_url }}/"'
      - name: Save Cecil cache
        uses: actions/cache/save@v4
        with:
          path: ./.cache
          key: cecil-cache-${{ hashFiles('./.cache/**/*') }}
      - name: Upload artifact
        uses: actions/upload-pages-artifact@v3

  deploy:
    needs: build
    permissions:
      pages: write
      id-token: write
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to GitHub Pages
        id: deployment
        uses: actions/deploy-pages@v4
```

[Documentation officielle](https://about.gitlab.com/stages-devops-lifecycle/pages/)

### GitLab CI

> Avec GitLab Pages, vous pouvez publier des sites web statiques directement depuis un dépôt GitLab.

➡️ <https://about.gitlab.com/solutions/continuous-integration/>

_.gitlab-ci.yml_:

```yml
image: wordpress:cli-php8.4

test:
  stage: test
  variables:
    CECIL_OUTPUT_DIR: test
  script:
    - curl -sSOL https://cecil.app/build.sh && bash ./build.sh
  artifacts:
    paths:
     - test
  except:
   - master

pages:
  stage: deploy
  variables:
    CECIL_ENV: production
    CECIL_OUTPUT_DIR: public
  script:
    - curl -sSOL https://cecil.app/build.sh && bash ./build.sh
  artifacts:
    paths:
      - public
  only:
    - master

cache:
  paths:
    - composer-cache/
    - vendor/
    - .cache/
```

[Documentation officielle](https://about.gitlab.com/stages-devops-lifecycle/continuous-integration/)

## Hébergement statique

### Surge

> Publier des projets web doit être rapide, simple et peu risqué. Surge est un service de publication de sites statiques pour les développeurs frontend, directement depuis la CLI.

➡️ <https://surge.sh>

Terminal:

```bash
npm install -g surge
surge _site/
```

[Documentation officielle](https://surge.sh/help/)
