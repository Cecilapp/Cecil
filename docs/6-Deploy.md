<!--
description: "Deploy (publish) your website."
date: 2020-12-19
updated: 2023-02-13
alias: documentation/publish
-->
# Deploy

By default your static site is built in the `_site` directory, and can be deployed as is.

Below are some recipes to automate build and/or deployment of a static site.

## Jamstack platforms

### [Netlify](https://www.netlify.com)

> A powerful serverless platform with an intuitive git-based workflow. Automated deployments, shareable previews, and much more.

_netlify.toml_:

```bash
[build]
  publish = "_site"
  command = "curl -sSOL https://cecil.app/build.sh && bash ./build.sh"

[build.environment]
  PHP_VERSION = "7.4"

[context.production.environment]
  CECIL_ENV = "production"

[context.deploy-preview.environment]
  CECIL_ENV = "preview"
```

[Official documentation](https://www.netlify.com/docs/continuous-deployment/)

### [Vercel](https://vercel.com)

> Vercel combines the best developer experience with an obsessive focus on end-user performance.

_vercel.json_:

```json
{
  "builds": [{
    "src": "package.json",
    "use": "@vercel/static-build",
    "config": { "distDir": "_site" }
  }]
}
```

_package.json_:

```json
{
  "scripts": {
    "build": "curl -sSOL https://cecil.app/build.sh && bash ./build.sh"
  }
}
```

[Official documentation](https://vercel.com/docs/concepts/deployments/build-step#build-command)

### [Cloudflare Pages](https://pages.cloudflare.com)

> Cloudflare Pages is a JAMstack platform for frontend developers to collaborate and deploy websites.

Build configurations:

- Framework preset: `None`
- Build command: `curl -sSOL https://cecil.app/build.sh && bash ./build.sh`
- Build output directory: `_site`

[Official documentation](https://developers.cloudflare.com/pages/)

### [Render](https://render.com)

> Render is a unified cloud to build and run all your apps and websites with free TLS certificates, global CDN, private networks and auto deploys from Git.

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

## Continuous build & hosting

### [GitHub Pages](https://pages.github.com)

> Websites for you and your projects, hosted directly from your GitHub repository. Just edit, push, and your changes are live.

_.github/workflows/build-and-deploy.yml_:

```yml
name: Build and deploy to GitHub Pages

on:
  push:
    branches: [master]
  workflow_dispatch:

permissions:
  contents: read
  pages: write
  id-token: write

concurrency:
  group: "pages"
  cancel-in-progress: true

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout source
        uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: mbstring, gd, imagick, intl, gettext
      - name: Build with Cecil
        uses: Cecilapp/Cecil-Action@v3
        env:
          CECIL_BASEURL: ${{ secrets.CECIL_BASEURL }}
      - name: Upload artifact
        uses: actions/upload-pages-artifact@v1
        with:
          path: _site
  deploy:
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    runs-on: ubuntu-latest
    needs: build
    steps:
      - name: Deploy to GitHub Pages
        id: deployment
        uses: actions/deploy-pages@v1
```

[Official documentation](https://about.gitlab.com/stages-devops-lifecycle/pages/)

### [GitLab CI](https://about.gitlab.com/stages-devops-lifecycle/continuous-integration/)

> With GitLab Pages, you can publish static websites directly from a repository in GitLab.

_.gitlab-ci.yml_:

```yml
image: phpdocker/phpdocker:7.4

before_script:
  - |
    echo "Downloading Cecil..."
    if [[ -z "$CECIL_VERSION" ]]; then
      curl -sSOL https://cecil.app/cecil.phar
    else
      curl -sSOL https://cecil.app/download/$CECIL_VERSION/cecil.phar
    fi
  - php cecil.phar --version
  - COMPOSER_CACHE_DIR=composer-cache composer install --prefer-dist --no-dev --no-progress --no-interaction

test:
  stage: test
  script:
    - php cecil.phar build --verbose --output=test
  artifacts:
    paths:
      - test
  except:
    - master

pages:
  stage: deploy
  script:
    - php cecil.phar build --output=public
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

[Official documentation](https://about.gitlab.com/stages-devops-lifecycle/continuous-integration/)

## Static hosting

### [Surge](https://surge.sh)

> Shipping web projects should be fast, easy, and low risk. Surge is static web publishing for Front-End Developers, right from the CLI.

Terminal:

```bash
npm install -g surge
surge _site/
```

[Official documentation](https://surge.sh/help/)
