<!--
description: "Deploy (publish) your website."
date: 2020-12-19
alias: documentation/publish
-->

# Deploy

By default your static site is built in the `_site` directory, and can be deployed as is.

Below are some recipes to automate build and/or deployment of a static site.

## [Netlify](https://www.netlify.com)

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

[context.branch-deploy.environment]
  CECIL_ENV = "branch"
```

[Official documentation](https://www.netlify.com/docs/continuous-deployment/)

## [Vercel](https://vercel.com)

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

## [Render](https://render.com)

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

## [Cloudflare Pages](https://pages.cloudflare.com)

Build configurations:

- Framework preset: `None`
- Build command: `curl -sSOL https://cecil.app/build.sh && bash ./build.sh`
- Build output directory: `_site`

[Official documentation](https://developers.cloudflare.com/pages/)

## [Surge](https://surge.sh)

Terminal:

```bash
npm install -g surge
surge _site/
```

[Official documentation](https://surge.sh/help/)

## [GitHub Pages](https://pages.github.com)

_.github/workflows/build-and-deploy.yml_:

```yml
name: Build and deploy to GitHub Pages

on:
  push:
    branches:
      - master

jobs:
  build:
    name: Build
    runs-on: ubuntu-latest
    steps:
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '7.4'
        extensions: mbstring, gd, imagick, intl, gettext

    - name: Checkout source
      uses: actions/checkout@v2

    - name: Build site
      env:
        CECIL_BASEURL: ${{ secrets.CECIL_BASEURL }}
      uses: Cecilapp/Cecil-Action@v3

    - name: Upload site to Artifacts
      uses: actions/upload-artifact@v2
      with:
        name: _site
        path: _site/
        if-no-files-found: error

  deploy:
    name: Deploy
    needs: build
    runs-on: ubuntu-latest

    steps:
    - name: Download site from Artifacts
      uses: actions/download-artifact@v2
      with:
        name: _site
        path: _site/

    - name: Deploy site to GitHub Pages
      uses: Cecilapp/GitHub-Pages-deploy@v3
      env:
        GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
      with:
        email: arnaud@ligny.org
        build_dir: _site
```

[Official documentation](https://help.github.com/en/articles/configuring-a-publishing-source-for-github-pages)

## [GitLab CI](https://about.gitlab.com/stages-devops-lifecycle/continuous-integration/)

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
