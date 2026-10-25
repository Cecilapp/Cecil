#!/bin/bash
set -o pipefail
#export LC_ALL=$(locale -a|grep -ix 'c.utf-\?8' || echo C)
export LC_ALL=C.UTF-8
export LANG=C.UTF-8

# This script build a Cecil website (locally, on statichost / GitLab / GitHub Actions / Netlify / Vercel / Cloudflare Pages / Render).
# It is intended to be used on CI / CD.

# Requirements
export PHP_MIN_VERSION="8.3"

# Specify the PHP version with `PHP_VERSION`
if [ -z "${PHP_VERSION}" ]; then
  export PHP_VERSION="8.3"
fi
# Specify Cecil CLI options with `CECIL_CMD_OPTIONS` (e.g.: `--optimize`)
if [ -z "${CECIL_CMD_OPTIONS}" ]; then
  CECIL_CMD_OPTIONS=""
fi
# Variables prefixed with `CECIL_` override Cecil configuration (e.g.: `CECIL_VERSION` -> `version`):
# script variables must not be passed to Cecil
export -n CECIL_CMD_OPTIONS CECIL_VERSION

# Running on?
RUNNING_ON="unknown"
PLATFORM_ENV="preview"
BASE_URL="" # `URL` is a Netlify variable
if [ "$CI" = "true" ]; then
  RUNNING_ON="statichost?"
fi
if [ "$GITLAB_CI" = "true" ]; then
  RUNNING_ON="GitLab"
fi
if [ "$GITHUB_ACTIONS" = "true" ]; then
  RUNNING_ON="GitHub"
fi
if [ "$NETLIFY" = "true" ]; then
  RUNNING_ON="Netlify"
fi
if [ "$VERCEL" = "1" ]; then
  RUNNING_ON="Vercel"
fi
if [ "$CF_PAGES" = "1" ]; then
  RUNNING_ON="CFPages"
fi
if [ "$RENDER" = "true" ]; then
  RUNNING_ON="Render"
fi
echo "------------------------------------"
echo "Running Cecil build on ${RUNNING_ON}"
echo "------------------------------------"
case $RUNNING_ON in
  "GitLab")
    if [ -n "$CI_COMMIT_BRANCH" ] && [ "$CI_COMMIT_BRANCH" = "$CI_DEFAULT_BRANCH" ]; then
      PLATFORM_ENV="production"
    fi
    BASE_URL=$CI_ENVIRONMENT_URL # set by `environment:url` of the job
    ;;
  "GitHub")
    if [ "$GITHUB_REF" = "refs/heads/master" ] || [ "$GITHUB_REF" = "refs/heads/main" ]; then
      PLATFORM_ENV="production"
    fi
    ;;
  "Netlify")
    if [ "$CONTEXT" = "production" ]; then
      PLATFORM_ENV="production"
      BASE_URL=$URL
    else
      BASE_URL=$DEPLOY_PRIME_URL
    fi
    ;;
  "Vercel")
    if [ "$VERCEL_ENV" = "production" ]; then
      PLATFORM_ENV="production"
      BASE_URL="https://$VERCEL_PROJECT_PRODUCTION_URL" # see https://vercel.com/docs/environment-variables/system-environment-variables
    else
      BASE_URL="https://$VERCEL_URL"
    fi
    # Install PHP and libs
    dnf clean metadata
    echo "Installing PHP ${PHP_VERSION}..."
    dnf install -y php$PHP_VERSION php$PHP_VERSION-{common,mbstring,gd,bcmath,xml,fpm,intl,zip}
    echo "Installing libs..."
    dnf install -y gettext pngquant libwebp-tools
    npm install -g svgo
    ;;
  "CFPages")
    # in production, `CF_PAGES_URL` is the deployment URL (e.g.: `https://<hash>.<project>.pages.dev`), not the site domain
    if [ "$CF_PAGES_BRANCH" = "master" ] || [ "$CF_PAGES_BRANCH" = "main" ]; then
      PLATFORM_ENV="production"
    else
      BASE_URL=$CF_PAGES_URL
    fi
    ;;
  "Render")
    # in production, `RENDER_EXTERNAL_URL` is the `onrender.com` URL, not the custom domain
    if [ "$IS_PULL_REQUEST" = "true" ]; then
      BASE_URL=$RENDER_EXTERNAL_URL
    else
      PLATFORM_ENV="production"
    fi
    ;;
esac

# Specify build context with `CECIL_ENV` (`production` or `preview`), otherwise it's deduced from the platform
if [ -z "${CECIL_ENV}" ]; then
  export CECIL_ENV="$PLATFORM_ENV"
fi
echo "Build context: ${CECIL_ENV}"

# Checks PHP version
# Without a compatible PHP version, the Cecil standalone binary is used
USE_BINARY=0
php --version > /dev/null 2>&1
PHP_IS_INSTALLED=$?
if [ $PHP_IS_INSTALLED -ne 0 ]; then
  echo "PHP is not installed: the Cecil standalone binary will be used."
  USE_BINARY=1
else
  php -r 'echo "PHP " . PHP_VERSION . " is available." . PHP_EOL;'
  PHP_OK=$(php -r 'echo (bool) version_compare(phpversion(), getenv("PHP_MIN_VERSION"), ">=");')
  if [ "$PHP_OK" != "1" ]; then
    echo "PHP ${PHP_MIN_VERSION} or higher is required: the Cecil standalone binary will be used."
    USE_BINARY=1
  fi
fi

# Download Cecil if needed
cecil --version > /dev/null 2>&1
CECIL_IS_INSTALLED=$?
CECIL_CMD="cecil"
if [ $CECIL_IS_INSTALLED -ne 0 ]; then
  if [ $USE_BINARY -eq 1 ]; then
    case "$(uname -s)-$(uname -m)" in
      Linux-x86_64) CECIL_BIN="cecil-linux-x64" ;;
      Linux-aarch64 | Linux-arm64) CECIL_BIN="cecil-linux-arm64" ;;
      Darwin-x86_64) CECIL_BIN="cecil-macos-x64" ;;
      Darwin-arm64) CECIL_BIN="cecil-macos-arm64" ;;
      *)
        echo "No Cecil standalone binary for $(uname -s) $(uname -m). Please install PHP ${PHP_MIN_VERSION} or higher."
        exit 1
        ;;
    esac
    if [ -z "$CECIL_VERSION" ]; then
      CECIL_BIN_URL="https://github.com/Cecilapp/Cecil/releases/latest/download"
      echo "Downloading Cecil standalone binary..."
    else
      CECIL_BIN_URL="https://github.com/Cecilapp/Cecil/releases/download/$CECIL_VERSION"
      echo "Downloading Cecil ${CECIL_VERSION} standalone binary..."
    fi
    curl -sSfLO "$CECIL_BIN_URL/$CECIL_BIN" && curl -sSfLO "$CECIL_BIN_URL/cecil-binaries.sha256"
    if [ $? -ne 0 ]; then
      echo "Unable to download Cecil standalone binary (available since Cecil 9.11.0)."; exit 1
    fi
    # Verify checksum
    if sha256sum --version > /dev/null 2>&1; then
      SHA256_CMD="sha256sum"
    else
      SHA256_CMD="shasum -a 256"
    fi
    grep " $CECIL_BIN\$" cecil-binaries.sha256 | $SHA256_CMD -c - > /dev/null
    if [ $? -ne 0 ]; then
      echo "Checksum verification of Cecil standalone binary failed."; exit 1
    fi
    chmod +x "$CECIL_BIN"
    CECIL_CMD="./$CECIL_BIN"
  else
    if [ -z "$CECIL_VERSION" ]; then
      CECIL_PHAR_URL="https://cecil.app"
      echo "Downloading Cecil..."
    else
      CECIL_PHAR_URL="https://cecil.app/download/$CECIL_VERSION"
      echo "Downloading Cecil ${CECIL_VERSION}..."
    fi
    if curl -sSfLO "$CECIL_PHAR_URL/cecil.phar" && curl -sSfLO "$CECIL_PHAR_URL/cecil.phar.sha1"; then
      # Verify checksum
      if [ "$(php -r 'echo sha1_file("cecil.phar");')" != "$(tr -d '[:space:]' < cecil.phar.sha1)" ]; then
        echo "Checksum verification of Cecil failed."; exit 1
      fi
    elif [ -n "$CECIL_VERSION" ]; then
      echo "Unable to download Cecil $CECIL_VERSION from Cecil.app. Trying from GitHub's release (without checksum verification)."
      if ! curl -sSfLO "https://github.com/Cecilapp/Cecil/releases/download/$CECIL_VERSION/cecil.phar"; then
        echo "Unable to download Cecil $CECIL_VERSION from GitHub."; exit 1
      fi
    else
      echo "Unable to download Cecil."; exit 1
    fi
    CECIL_CMD="php cecil.phar"
  fi
  echo "$($CECIL_CMD --version) is available."
else
  echo "$($CECIL_CMD --version) is available."
fi

# Installs Cecil components if needed
if [ -f "./composer.json" ]; then
  if [ $USE_BINARY -eq 0 ]; then
    composer --version > /dev/null 2>&1
    COMPOSER_IS_INSTALLED=$?
    COMPOSER_CMD="composer"
    if [ $COMPOSER_IS_INSTALLED -ne 0 ]; then
      echo "Composer is required. Downloading..."
      # see https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
      COMPOSER_INSTALLER_SIG=$(curl -sSfL https://composer.github.io/installer.sig)
      curl -sSfL https://getcomposer.org/installer -o composer-setup.php
      if [ -z "$COMPOSER_INSTALLER_SIG" ] || [ "$(php -r 'echo @hash_file("sha384", "composer-setup.php");')" != "$COMPOSER_INSTALLER_SIG" ]; then
        rm -f composer-setup.php
        echo "Unable to download Composer installer (or its checksum verification failed)."; exit 1
      fi
      php composer-setup.php --quiet
      COMPOSER_SETUP_SUCCESS=$?
      rm -f composer-setup.php
      if [ $COMPOSER_SETUP_SUCCESS -ne 0 ]; then
        echo "Unable to install Composer."; exit 1
      fi
      COMPOSER_CMD="php composer.phar"
    else
      echo "$($COMPOSER_CMD --version) is available."
    fi
    echo "Installing themes..."
    # Composer cache dir: "composer-cache"
    COMPOSER_CACHE_DIR=composer-cache $COMPOSER_CMD install --prefer-dist --no-dev --no-progress --no-interaction --quiet
    if [ $? -ne 0 ]; then
      echo "Unable to install themes."; exit 1
    fi
    echo "OK"
  else
    # Composer requires PHP: themes are downloaded from GitHub, at the versions locked in composer.lock
    echo "Installing themes from composer.lock..."
    if [ ! -f "./composer.lock" ]; then
      echo "composer.lock is required to install themes without PHP."; exit 1
    fi
    if jq --version > /dev/null 2>&1; then
      THEMES=$(jq -r '.packages[] | select(.type == "cecil-theme") | "\(.extra.name // (.name | sub("^.*/(theme-)?"; ""))) \(.source.url) \(.source.reference)"' composer.lock)
    elif python3 --version > /dev/null 2>&1; then
      THEMES=$(python3 -c '
import json, re
for p in json.load(open("composer.lock"))["packages"]:
    if p.get("type") == "cecil-theme":
        print((p.get("extra") or {}).get("name") or re.sub(r"^.*/(theme-)?", "", p["name"]), p["source"]["url"], p["source"]["reference"])')
    elif node --version > /dev/null 2>&1; then
      THEMES=$(node -e '
for (const p of JSON.parse(require("fs").readFileSync("composer.lock", "utf8")).packages.filter(p => p.type === "cecil-theme")) {
  console.log((p.extra && p.extra.name) || p.name.replace(/^.*\/(theme-)?/, ""), p.source.url, p.source.reference);
}')
    else
      echo "jq, Python 3 or Node.js is required to read composer.lock."; exit 1
    fi
    while read -r THEME_NAME THEME_URL THEME_REF; do
      [ -z "$THEME_NAME" ] && continue
      THEME_REF=${THEME_REF%$'\r'}
      case "$THEME_URL" in
        https://github.com/*) THEME_REPO=${THEME_URL#https://github.com/}; THEME_REPO=${THEME_REPO%.git} ;;
        *) echo "Theme \"$THEME_NAME\" is not hosted on GitHub and can't be installed without PHP."; exit 1 ;;
      esac
      echo "- $THEME_NAME ($THEME_REPO)"
      rm -rf "themes/$THEME_NAME" && mkdir -p "themes/$THEME_NAME"
      curl -sSfL "https://codeload.github.com/$THEME_REPO/tar.gz/$THEME_REF" | tar -xz --strip-components=1 -C "themes/$THEME_NAME"
      if [ $? -ne 0 ]; then
        echo "Unable to install theme \"$THEME_NAME\"."; exit 1
      fi
    done <<< "$THEMES"
    echo "OK"
  fi
fi

# Adds CLI options
# `CECIL_CMD_OPTIONS` is split on whitespace
read -r -a CECIL_BUILD_OPTIONS <<< "$CECIL_CMD_OPTIONS"
if [ -n "${BASE_URL}" ]; then
  CECIL_BUILD_OPTIONS=("--baseurl=${BASE_URL}" "${CECIL_BUILD_OPTIONS[@]}")
fi
if [ "$CECIL_ENV" = "production" ]; then
  CECIL_BUILD_OPTIONS=(-v "${CECIL_BUILD_OPTIONS[@]}")
else
  CECIL_BUILD_OPTIONS=(-vv "${CECIL_BUILD_OPTIONS[@]}")
fi

# Run build
echo "Running \"${CECIL_CMD} build ${CECIL_BUILD_OPTIONS[*]}\"";
$CECIL_CMD build "${CECIL_BUILD_OPTIONS[@]}"
BUILD_SUCCESS=$?

# Build success? Can deploy?
if [ $BUILD_SUCCESS -ne 0 ]; then
  echo "Build fail."; exit $BUILD_SUCCESS
fi

exit 0
