#!/bin/bash
set -e

# Deploy release files to website

# version
VERSION=$(echo $GITHUB_REF | cut -d'/' -f 3)

# target
TARGET_REPO="Cecilapp/website"
TARGET_BRANCH="master"
TARGET_STATIC_DIR="static"
TARGET_RELEASE_DIR="download/$VERSION"
TARGET_PAGES_DIR="pages"

# Phar
PHAR_FILE="cecil.phar"
PHAR_FILE_SHA1="cecil.phar.sha1"

# GitHub
USER_NAME=$GITHUB_ACTOR
USER_EMAIL="${GITHUB_ACTOR}@cecil.app"
HOME="${GITHUB_WORKSPACE}/HOME"

# pre-release
if [ -z "${PRE_RELEASE}" ]; then
  export PRE_RELEASE="false"
fi

echo "Starting deploy release files..."
mkdir $HOME

# copy dist file
cp dist/$PHAR_FILE $HOME/$PHAR_FILE

# clone target repo
cd $HOME
git config --global user.name "${USER_NAME}"
git config --global user.email "${USER_EMAIL}"
git clone --quiet --branch=$TARGET_BRANCH https://${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git ${TARGET_REPO} > /dev/null
cd $TARGET_REPO

# copy/create release files in static/
cd $TARGET_STATIC_DIR
mkdir -p $TARGET_RELEASE_DIR
# copy `.phar` file
cp $HOME/$PHAR_FILE $TARGET_RELEASE_DIR/$PHAR_FILE
# create `.sha1` file
cd $TARGET_RELEASE_DIR
sha1sum $PHAR_FILE > $PHAR_FILE_SHA1
sha1hash=$(sha1sum $PHAR_FILE)
sha1hash=${sha1hash%% *}
cd ../../..

# create VERSION file and redirections (if not pre-release)
if [ "${PRE_RELEASE}" != 'true' ]; then
  # VERSION file in static
  cd $TARGET_STATIC_DIR
  [ -e VERSION ] && rm -- VERSION
  echo $VERSION > VERSION
  cd ..
  # redirections files in pages/
  cd $TARGET_PAGES_DIR
  now=$(date +"%Y-%m-%d")
  rm -f $PHAR_FILE.md
  cat <<EOT >> $PHAR_FILE.md
---
redirect: $TARGET_RELEASE_DIR/$PHAR_FILE
slug: cecil
output: phar
date: $now
---
EOT
  rm -f $PHAR_FILE_SHA1.md
  cat <<EOT >> $PHAR_FILE_SHA1.md
---
redirect: $TARGET_RELEASE_DIR/$PHAR_FILE_SHA1
slug: cecil
output: sha1
date: $now
---
EOT
  cd ..
fi

# commit and push
if [[ -n $(git status -s) ]]; then
  git add -Af .
  git commit -m "Build $GITHUB_RUN_NUMBER: deploy release ${VERSION}"
  git push -fq origin $TARGET_BRANCH > /dev/null
else
  echo "Nothing to update"
fi
exit 0
