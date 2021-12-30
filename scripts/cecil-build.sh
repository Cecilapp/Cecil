#!/bin/bash

# This script build a Cecil website (locally, on Netlify / Vercel / Cloudflare Pages / Render).
# It is intended to be used on CI / CD.
export PHP_MIN_VERSION="7.1"

# Default variables
if [ -z "${PHP_VERSION}" ]; then
  export PHP_VERSION="7.4"
fi
if [ -z "${CECIL_INSTALL_OPTIM}" ]; then
  export CECIL_INSTALL_OPTIM="false"
fi
if [ -z "${CECIL_CMD_OPTIONS}" ]; then
  export CECIL_CMD_OPTIONS=""
fi

# Running on
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
    echo "Installing PHP ${PHP_VERSION}..."
    amazon-linux-extras install -y php$PHP_VERSION
    echo "Installing Gettext..."
    yum install -y gettext
    echo "Installing PHP extensions..."
    yum install -y php-{cli,mbstring,dom,xml,intl,gettext,gd,imagick}
    if [ "$CECIL_INSTALL_OPTIM" = "true" ]; then
      echo "Installing images optimization libraries..."
      yum install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
      yum install -y jpegoptim
      yum install -y pngquant
      yum install -y gifsicle
      yum install -y libwebp-tools
    fi
    URL=$VERCEL_URL
    if [ "$VERCEL_ENV" = "production" ]; then
      CONTEXT="production"
    fi
    ;;
  "CFPages")
    if [ "$CF_PAGES_BRANCH" = "master" ] || [ "$CF_PAGES_BRANCH" = "main" ]; then
      CONTEXT="production"
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

# PHP
php --version > /dev/null 2>&1
PHP_IS_INSTALLED=$?
if [ $PHP_IS_INSTALLED -ne 0 ]; then
  echo "PHP is not installed. Please install it before running this script."
  exit 1;
else
  php -r 'echo "PHP ".PHP_VERSION." is already installed.".PHP_EOL;'
fi
PHP_OK=$(php -r 'echo (bool) version_compare(phpversion(), getenv("PHP_MIN_VERSION"), ">=");')
if [ "$PHP_OK" != "1" ]; then
  echo "PHP version is not compatible. Please install PHP ${PHP_MIN_VERSION} or higher."
  exit 1;
fi

# Cecil
cecil --version > /dev/null 2>&1
CECIL_IS_INSTALLED=$?
CECIL_CMD="cecil"
if [ $CECIL_IS_INSTALLED -ne 0 ]; then
  if [ -z $CECIL_VERSION ]; then
    echo "Installing Cecil..."
    curl -sSOL https://cecil.app/cecil.phar
  else
    echo "Installing Cecil ${CECIL_VERSION}..."
    curl -sSOL https://cecil.app/download/$CECIL_VERSION/cecil.phar
  fi
  CECIL_CMD="php cecil.phar"
else
  echo "$($CECIL_CMD --version) is already installed."
fi

# Themes
if [ -f "./composer.json" ]; then
  composer --version > /dev/null 2>&1
  COMPOSER_IS_INSTALLED=$?
  COMPOSER_CMD="composer"
  if [ $COMPOSER_IS_INSTALLED -ne 0 ]; then
    echo "Installing Composer"
    curl -sS https://getcomposer.org/installer | php
    COMPOSER_CMD="php composer.phar"
  else
    echo "$($COMPOSER_CMD --version) is already installed."
  fi
  echo "Installing themes..."
  $COMPOSER_CMD install --prefer-dist --no-dev --no-progress --no-interaction --quiet
fi

# Options
if [ -z "${URL}" ]; then
  CECIL_CMD_OPTIONS="${CECIL_CMD_OPTIONS} --baseurl=${URL}"
fi
if [ "$CONTEXT" = "production" ]; then
  CECIL_CMD_OPTIONS=" -v ${CECIL_CMD_OPTIONS} --postprocess"
else
  CECIL_CMD_OPTIONS=" -vv ${CECIL_CMD_OPTIONS} --drafts"
fi

# Run build
$CECIL_CMD build$CECIL_CMD_OPTIONS
BUILD_SUCCESS=$?

# Build success? Can deploy?
if [ $BUILD_SUCCESS -ne 0 ]; then
  echo "Build fail."; exit 1
fi

exit 0
