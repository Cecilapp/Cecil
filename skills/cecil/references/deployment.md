# Deployment

Cecil generates a pure static site in `_site/`, compatible with any static hosting (GitHub Pages, GitLab Pages, Netlify, Vercel, Cloudflare Pages, statichost, Render, AWS S3, any web server). Never commit `_site/` nor `.cache/`: build in CI or on the platform.

## Contents

- [Before Deploying](#before-deploying)
- [GitHub Pages](#github-pages)
- [GitLab Pages](#gitlab-pages)
- [Jamstack Platforms](#jamstack-platforms)
- [Manual Deployment](#manual-deployment)
- [HTTP Headers](#http-headers)

## Before Deploying

```bash
# Check internal links (add --internal --external to also check external links); fails on broken links
php cecil.phar doctor:links

# Build for production
php cecil.phar build --optimize
```

- Set the production `baseurl` (in `cecil.yml`, or with `CECIL_BASEURL` / `--baseurl` per environment).
- `CECIL_ENV` (e.g. `production`, `preview`) and `CECIL_OUTPUT_DIR` (output directory) are used by the recipes below.

## GitHub Pages

Use a GitHub Actions workflow with the official [Cecil Action](https://github.com/Cecilapp/Cecil-Action) (set the repository Pages source to "GitHub Actions"):

```yaml
# .github/workflows/build-and-deploy.yml
name: Build and deploy to GitHub Pages
on:
  push:
    branches: [main]
  workflow_dispatch:
concurrency:
  group: pages
  cancel-in-progress: true
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout source
        uses: actions/checkout@v7
      - name: Restore Cecil cache
        uses: actions/cache/restore@v6
        with:
          path: ./.cache
          key: cecil-cache-
          restore-keys: |
            cecil-cache-
      - name: Build site
        uses: Cecilapp/Cecil-Action@v4
      - name: Save Cecil cache
        uses: actions/cache/save@v6
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

For a project site (`https://<user>.github.io/<repo>/`), the `baseurl` must include the repository path.

## GitLab Pages

GitLab Pages publishes the `public` directory:

```yaml
# .gitlab-ci.yml
image: wordpress:cli-php8.4
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
  rules:
    - if: $CI_COMMIT_BRANCH == $CI_DEFAULT_BRANCH
cache:
  paths:
    - .cache/
    - composer-cache/
```

## Jamstack Platforms

Use the official build script `curl -sSOL https://cecil.app/build.sh && bash ./build.sh` as build command, and `_site` as publish/output directory. It downloads Cecil, installs the themes and builds the site.

```toml
# netlify.toml
[build]
  publish = "_site"
  command = "curl -sSOL https://cecil.app/build.sh && bash ./build.sh"

[context.production.environment]
  CECIL_ENV = "production"

[context.deploy-preview.environment]
  CECIL_ENV = "preview"
```

```json
// vercel.json
{
  "buildCommand": "curl -sSOL https://cecil.app/build.sh && bash ./build.sh",
  "outputDirectory": "_site"
}
```

```yaml
# statichost.yml
image: wordpress:cli-php8.4
command: curl -sSOL https://cecil.app/build.sh && bash ./build.sh
public: _site
```

- **Cloudflare Pages**: framework preset `None`, build command as above, build output directory `_site`.
- **Render** (`render.yaml`): `runtime: static`, `buildCommand` as above, `staticPublishPath: ./_site`.
- Cloudflare Pages has no PHP, and Render only PHP 7.3: the script then uses the standalone binary and installs themes from `composer.lock` (commit it).
- The script deduces the build context and the base URL from the platform; a `CECIL_ENV` variable takes precedence. On Cloudflare Pages and Render, production builds keep the `baseurl` of the site configuration (set it to the custom domain).

## Manual Deployment

Upload the content of `_site/` to the web server (rsync, FTP, S3 sync…), or use a CLI such as Surge (`npm install -g surge && surge _site/`).

## HTTP Headers

`server.headers` is only applied by the local `serve` server. To deploy headers (security, cache, `llms.txt` links), install the `headers` component theme: it generates a `_headers` file for Netlify, statichost and Cloudflare Pages. Other hosts need their own configuration (e.g. `vercel.json` `headers`, web server config).
