#!/bin/bash
set -e

# Bump Scoop file

# version
if [ -z "${VERSION}" ]; then
  export VERSION=$(echo $GITHUB_REF | cut -d'/' -f 3)
fi

# pre-release
if [ -z "${PRERELEASE}" ]; then
  export PRERELEASE="false"
fi

# target
TARGET_REPO="Cecilapp/website"
TARGET_BRANCH="master"
TARGET_STATIC_DIR="static"

# Scoop
SCOOP_FILE_JSON="scoop/cecil.json"
SCOOP_FILE_JSON_PREVIEW="scoop/cecil-preview.json"

# GitHub
USER_NAME=$GITHUB_ACTOR
USER_EMAIL="${GITHUB_ACTOR}@cecil.app"
HOME="${GITHUB_WORKSPACE}/HOME"

echo "Starting deploy Scoop file..."
mkdir $HOME

# clone target repo
cd $HOME
git config --global user.name "${USER_NAME}"
git config --global user.email "${USER_EMAIL}"
git clone --depth=1 --quiet --branch=$TARGET_BRANCH https://${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git ${TARGET_REPO} > /dev/null
cd $TARGET_REPO

# Scoop manifest
if [ "${PRERELEASE}" == 'true' ]; then
  SCOOP_FILE_JSON="$SCOOP_FILE_JSON_PREVIEW"
fi
# remove and recreate manifest in static
cd $TARGET_STATIC_DIR
rm -f $SCOOP_FILE_JSON
mkdir -p $(dirname "$SCOOP_FILE_JSON") && touch $SCOOP_FILE_JSON
cat <<EOT > $SCOOP_FILE_JSON
{
  "description": "A simple and powerful content-driven static site generator.",
  "homepage": "https://cecil.app",
  "license": "MIT",
  "bin": "cecil.phar",
  "notes": [
    "Run 'cecil' to get started",
    "Run 'scoop update cecil' instead of 'cecil self-update' to update"
  ],
  "suggest": {
    "PHP": ["php"]
  },
  "url": "https://cecil.app/download/$VERSION/cecil.phar",
  "version": "$VERSION",
  "hash": "sha1:$SHA1",
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
cd ..

# commit and push
if [[ -n $(git status -s) ]]; then
  git add -Af .
  git commit -m "Build $GITHUB_RUN_NUMBER: bump Scoop with version ${VERSION}"
  git push -fq origin $TARGET_BRANCH > /dev/null
else
  echo "Nothing to update"
fi
exit 0
