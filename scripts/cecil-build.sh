#!/bin/bash

# This script build a Cecil website (locally, on Netlify / Vercel / Cloudflare Pages / Render).
# It is intended to be used on CI / CD.

# Requirements
export PHP_MIN_VERSION="8.1"

# Specify the PHP version with `PHP_VERSION`
if [ -z "${PHP_VERSION}" ]; then
  export PHP_VERSION="8.2"
fi
# Specify Cecil CLI options with `CECIL_CMD_OPTIONS` (e.g.: `--optimize`)
if [ -z "${CECIL_CMD_OPTIONS}" ]; then
  export CECIL_CMD_OPTIONS=""
fi
# Enable installation of images optimization libraries on Vercel
if [ -z "${VERCEL_INSTALL_OPTIM}" ]; then
  export VERCEL_INSTALL_OPTIM="false"
fi

# Running on?
RUNNING_ON="unknown"
URL=""
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
echo "Running on ${RUNNING_ON}"
case $RUNNING_ON in
  "Netlify")
    if [ "$CONTEXT" = "production" ]; then
      URL=$URL
    else
      URL=$DEPLOY_PRIME_URL
    fi
    ;;
  "Vercel")
    dnf clean metadata
    echo "Installing PHP ${PHP_VERSION}..."
    dnf install -y php$PHP_VERSION php$PHP_VERSION-{common,mbstring,gd,bcmath,xml,fpm,intl,zip}
    echo "Installing Gettext..."
    dnf install -y gettext
    echo "Installing AVIF lib..."
    dnf install -y libavif
    if [ "$VERCEL_INSTALL_OPTIM" = "true" ]; then
      echo "Installing images optimization libraries..."
      dnf install -y epel-release
      dnf install -y jpegoptim
      dnf install -y optipng
      dnf install -y pngquant
      npm install -y -g svgo
      dnf install -y gifsicle
      dnf install -y libwebp-tools
      dnf install -y libavif-tools
    fi
    if [ "$VERCEL_ENV" = "production" ]; then
      CONTEXT="production"
    else
      URL="https://$VERCEL_URL" # see https://vercel.com/docs/concepts/projects/environment-variables#system-environment-variables
    fi
    ;;
  "CFPages")
    if [ "$CF_PAGES_BRANCH" = "master" ] || [ "$CF_PAGES_BRANCH" = "main" ]; then
      CONTEXT="production"
    else
      URL=$CF_PAGES_URL
    fi
    ;;
  "Render")
    CONTEXT="production"
    URL=$RENDER_EXTERNAL_URL
    if [ "$IS_PULL_REQUEST" = "true" ]; then
      CONTEXT="preview"
    fi
    ;;
esac

# Checks PHP version
php --version > /dev/null 2>&1
PHP_IS_INSTALLED=$?
if [ $PHP_IS_INSTALLED -ne 0 ]; then
  echo "PHP is not installed. Please install PHP ${PHP_MIN_VERSION} or higher before running this script."
  exit 1;
else
  php -r 'echo "PHP " . PHP_VERSION . " is available." . PHP_EOL;'
fi
PHP_OK=$(php -r 'echo (bool) version_compare(phpversion(), getenv("PHP_MIN_VERSION"), ">=");')
if [ "$PHP_OK" != "1" ]; then
  echo "PHP version is not compatible. Please install PHP ${PHP_MIN_VERSION} or higher."
  exit 1;
fi

# Download Cecil if needed
cecil --version > /dev/null 2>&1
CECIL_IS_INSTALLED=$?
CECIL_CMD="cecil"
if [ $CECIL_IS_INSTALLED -ne 0 ]; then
  if [ -z $CECIL_VERSION ]; then
    echo "Downloading Cecil..."
    curl -sSOL https://cecil.app/cecil.phar
  else
    echo "Downloading Cecil ${CECIL_VERSION}..."
    if [ $(curl -LI https://cecil.app/download/$CECIL_VERSION/cecil.phar -o /dev/null -w '%{http_code}\n' -s) == '200' ]; then
      curl -sSOL https://cecil.app/download/$CECIL_VERSION/cecil.phar
    else
      echo "Can't download Cecil $CECIL_VERSION from Cecil.app. Trying from GitHub's release.";
      if [ $(curl -LI https://github.com/Cecilapp/Cecil/releases/download/$CECIL_VERSION/cecil.phar -o /dev/null -w '%{http_code}\n' -s) != '200' ]; then
      echo "Can't download Cecil $CECIL_VERSION from GitHub"; exit 1
      fi
      curl -sSOL https://github.com/Cecilapp/Cecil/releases/download/$CECIL_VERSION/cecil.phar
    fi
  fi
  CECIL_CMD="php cecil.phar"
  echo "$($CECIL_CMD --version) is available."
else
  echo "$($CECIL_CMD --version) is available."
fi

# Installs Cecil components if needed
if [ -f "./composer.json" ]; then
  composer --version > /dev/null 2>&1
  COMPOSER_IS_INSTALLED=$?
  COMPOSER_CMD="composer"
  if [ $COMPOSER_IS_INSTALLED -ne 0 ]; then
    echo "Composer is required. Downloading..."
    curl -sS https://getcomposer.org/installer | php
    COMPOSER_CMD="php composer.phar"
  else
    echo "$($COMPOSER_CMD --version) is available."
  fi
  echo "Installing themes..."
  $COMPOSER_CMD install --prefer-dist --no-dev --no-progress --no-interaction --quiet
fi

# Adds CLI options
if [ ! -z "${URL}" ]; then
  CECIL_CMD_OPTIONS="--baseurl=${URL} ${CECIL_CMD_OPTIONS}"
fi
if [ "$CONTEXT" = "production" ]; then
  export CECIL_ENV="production"
  CECIL_CMD_OPTIONS="-v ${CECIL_CMD_OPTIONS}"
else
  CECIL_CMD_OPTIONS="-vv ${CECIL_CMD_OPTIONS}"
fi

# Run build
CECIL_CMD_OPTIONS="${CECIL_CMD_OPTIONS%[[:space:]]}"
echo "Running \"${CECIL_CMD} build ${CECIL_CMD_OPTIONS}\"";
$CECIL_CMD build $CECIL_CMD_OPTIONS
BUILD_SUCCESS=$?

# Build success? Can deploy?
if [ $BUILD_SUCCESS -ne 0 ]; then
  echo "Build fail."; exit 1
fi

exit 0
