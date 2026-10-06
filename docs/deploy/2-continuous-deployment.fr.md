<!--
title: "Build et déploiement continus"
description: "GitHub Pages et GitLab CI."
date: 2026-03-27
updated: 2026-10-02
path: documentation/deployer/deploiement-continu
-->
# Build et déploiement continus

## GitHub Pages

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
        uses: actions/checkout@v6
      - name: Restore Cecil cache
        uses: actions/cache/restore@v5
        with:
          path: ./.cache
          key: cecil-cache-
          restore-keys: |
            cecil-cache-
      - name: Build site
        uses: Cecilapp/Cecil-Action@v4
      - name: Save Cecil cache
        uses: actions/cache/save@v5
        with:
          path: ./.cache
          key: cecil-cache-${{ hashFiles('./.cache/**/*') }}
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
        uses: actions/deploy-pages@v5
```

[Documentation officielle](https://docs.github.com/en/pages)

## GitLab CI

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
