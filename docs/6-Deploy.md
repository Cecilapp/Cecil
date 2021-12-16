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
  command = "chmod +x ./scripts/netlify-build.sh && bash ./scripts/netlify-build.sh"
[build.environment]
  CECIL_ENV = "production"

[context.deploy-preview.environment]
  CECIL_ENV = "preview"

[context.branch-deploy.environment]
  CECIL_ENV = "branch"
```

_scripts/netlify-build.sh_:

```bash
echo "Downloading Cecil"
if [ -z $CECIL_VERSION ]; then
  curl -sSOL https://cecil.app/cecil.phar
else
  curl -sSOL https://cecil.app/download/$CECIL_VERSION/cecil.phar
fi
php cecil.phar --version

if [[ $CECIL_ENV == "production" ]]; then
  php cecil.phar build -v --baseurl=$URL
else
  php cecil.phar build -vv --baseurl=$DEPLOY_PRIME_URL --drafts
fi

# build success? can deploy?
if [ $? = 0 ]; then echo "Finished Cecil build"; exit 0; fi

echo "Interrupted Cecil build"; exit 1
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
    "build": "bash ./scripts/vercel-install.sh && bash ./scripts/vercel-build.sh"
  }
}
```

_scripts/vercel-install.sh_:

```bash
#!/bin/bash

if [ -z $PHP_VERSION ]; then
  PHP_VERSION='7.4'
fi
echo "================================================================================"
echo "Installing PHP $PHP_VERSION..."
amazon-linux-extras install -y php$PHP_VERSION
yum install -y php-{cli,mbstring,dom,xml,intl,gettext,gd,imagick}
php --version

echo "================================================================================"
echo 'Downloading Cecil...'
if [ -z $CECIL_VERSION ]; then
  curl -sSOL https://cecil.app/cecil.phar
else
  curl -sSOL https://cecil.app/download/$CECIL_VERSION/cecil.phar
fi
php cecil.phar --version

echo "================================================================================"
echo 'Installing theme(s)...'
curl -sS https://getcomposer.org/installer | php
php composer.phar install --prefer-dist --no-dev --no-progress --no-interaction
```

_scripts/vercel-build.sh_:

```bash
#!/bin/bash

echo "================================================================================"
if [[ $VERCEL_ENV == "production" ]]; then
  CMD="cecil.phar build -v --postprocess"
else
  CMD="cecil.phar build -vv --drafts"
fi

if [ -z $VERCEL_URL ]; then
    php $CMD
  else
    php $CMD --baseurl=https://$VERCEL_URL
    echo "URL: https://$VERCEL_URL"
  fi

# build success? can deploy?
if [ $? = 0 ]; then
  exit 0
fi
echo "Build fail."; exit 1
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
  buildCommand: curl -LO https://cecil.app/cecil.phar && composer install --prefer-dist --no-dev --no-progress --no-interaction && php cecil.phar build -v
  staticPublishPath: _site
  pullRequestPreviewsEnabled: true
```

[Official documentation](https://render.com/docs/static-sites)

## [Cloudflare Pages](https://pages.cloudflare.com)

_scripts/build.sh_:

```bash
# What this script do?
#  1. install Cecil if `cecil.phar` is not found (set the `CECIL_VERSION` variable to use a specific version)
#  2. install Composer if `composer.phar` is not found
#  3. install theme(s) if `composer.json` is found
#  4. run `php cecil.phar build -v`

if [ ! -f "./cecil.phar" ]; then
  echo "Downloading Cecil"
  if [ -z $CECIL_VERSION ]; then
    curl -sSOL https://cecil.app/cecil.phar
  else
    curl -sSOL https://cecil.app/download/$CECIL_VERSION/cecil.phar
  fi
fi
php cecil.phar --version

if [ -f "./composer.json" ]; then
  echo "Installing theme(s)"
  if [ ! -f "./composer.phar" ]; then
    curl -sS https://getcomposer.org/installer | php
  fi
  php composer.phar install --prefer-dist --no-dev --no-progress --no-interaction
fi

php cecil.phar build -v

# build success? can deploy?
if [ $? = 0 ]; then
  exit 0
fi
echo "Build fail."; exit 1
```

[Official documentation](https://developers.cloudflare.com/pages/)

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
