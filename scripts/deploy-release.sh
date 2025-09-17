#!/bin/bash
set -e

# Deploy release files to website

# params
VERSION=$(echo $GITHUB_REF | cut -d'/' -f 3)
TARGET_REPO="Cecilapp/website"
TARGET_BRANCH="master"
TARGET_RELEASE_DIR="download/$VERSION"
TARGET_DIST_DIR="static"
DIST_FILE="cecil.phar"
DIST_FILE_SHA1="cecil.phar.sha1"
SCOOP_CMD="cecil"
SCOOP_FILE_JSON="scoop/cecil.json"
SCOOP_FILE_JSON_PREVIEW="scoop/cecil-preview.json"
TARGET_PAGES_DIR="pages"
USER_NAME=$GITHUB_ACTOR
USER_EMAIL="${GITHUB_ACTOR}@cecil.app"
HOME="${GITHUB_WORKSPACE}/HOME"
BUILD_NUMBER=$GITHUB_RUN_NUMBER
if [ -z "${PRE_RELEASE}" ]; then
  export PRE_RELEASE="false"
fi

echo "Starting deploy release files..."
mkdir $HOME
cp dist/$DIST_FILE $HOME/$DIST_FILE

# clone target repo
cd $HOME
git config --global user.name "${USER_NAME}"
git config --global user.email "${USER_EMAIL}"
git clone --quiet --branch=$TARGET_BRANCH https://${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git ${TARGET_REPO} > /dev/null

# copy (or create if necessary) release files
cd $TARGET_REPO/$TARGET_DIST_DIR
mkdir -p $TARGET_RELEASE_DIR
# `.phar` file
cp $HOME/$DIST_FILE $TARGET_RELEASE_DIR/$DIST_FILE
# `.sha1` file
cd $TARGET_RELEASE_DIR
sha1sum $DIST_FILE > $DIST_FILE_SHA1
sha1hash=$(sha1sum $DIST_FILE)
sha1hash=${sha1hash%% *}
cd ../..

# if not pre-release, create VERSION file and redirections
if [ "${PRE_RELEASE}" != 'true' ]; then
  # create VERSION file
  [ -e VERSION ] && rm -- VERSION
  echo $VERSION > VERSION

  # create website redirections files
  now=$(date +"%Y-%m-%d")
  cd ../$TARGET_PAGES_DIR
  rm -f $DIST_FILE.md
  cat <<EOT >> $DIST_FILE.md
---
redirect: $TARGET_RELEASE_DIR/$DIST_FILE
slug: cecil
output: phar
date: $now
---
EOT
  rm -f $DIST_FILE_SHA1.md
  cat <<EOT >> $DIST_FILE_SHA1.md
---
redirect: $TARGET_RELEASE_DIR/$DIST_FILE_SHA1
slug: cecil
output: sha1
date: $now
---
EOT
  cd ..
fi

# pre-release / preview
if [ "${PRE_RELEASE}" == 'true' ]; then
  SCOOP_FILE_JSON="$SCOOP_FILE_JSON_PREVIEW"
fi

# create Scoop manifest
rm -f $SCOOP_FILE_JSON
mkdir -p $(dirname "$SCOOP_FILE_JSON") && touch $SCOOP_FILE_JSON
cat <<EOT > $SCOOP_FILE_JSON
{
  "description": "A simple and powerful content-driven static site generator.",
  "homepage": "https://cecil.app",
  "license": "MIT",
  "bin": "$DIST_FILE",
  "notes": [
    "Run 'cecil' to get started",
    "Run 'scoop update cecil' instead of 'cecil self-update' to update"
  ],
  "suggest": {
    "PHP": ["php"]
  },
  "url": "https://cecil.app/download/$VERSION/cecil.phar",
  "version": "$VERSION",
  "hash": "sha1:$sha1hash",
  "checkver": {
    "url": "https://cecil.app/VERSION",
    "regex": "([\\\d.]+)"
  },
  "autoupdate": {
    "url": "https://cecil.app/download/\$version/cecil.phar",
    "hash": {
      "url": "\$url.sha1"
    }
  }
}
EOT

# commit and push
git add -Af .
git commit -m "Build $BUILD_NUMBER: deploy release ${VERSION}"
git push -fq origin $TARGET_BRANCH > /dev/null

exit 0
