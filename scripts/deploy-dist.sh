#!/bin/bash
set -e

# Deploy dist file to website

TARGET_REPO="Cecilapp/website"
TARGET_BRANCH="source"
TARGET_RELEASE_DIR="download/$TRAVIS_TAG"
TARGET_DIST_DIR="static"
DIST_FILE="cecil.phar"
DIST_FILE_VERSION="cecil.phar.version"

if [ ! -n "$TRAVIS_TAG" ]; then
  TARGET_RELEASE_DIR="download/$TRAVIS_BRANCH"
fi

echo "Starting to deploy ${DIST_FILE} to ${TARGET_REPO}..."
cp dist/$DIST_FILE $HOME/$DIST_FILE

# clone target repo
cd $HOME
git config --global user.name "Travis"
git config --global user.email "contact@travis-ci.org"
git clone --quiet --branch=$TARGET_BRANCH https://${GH_TOKEN}@github.com/${TARGET_REPO}.git ${TARGET_REPO} > /dev/null

cd $TARGET_REPO/$TARGET_DIST_DIR
mkdir -p $TARGET_RELEASE_DIR
# copy dist file
cp $HOME/$DIST_FILE $TARGET_RELEASE_DIR/$DIST_FILE
# create SHA1
sha1sum $TARGET_RELEASE_DIR/$DIST_FILE > $TARGET_RELEASE_DIR/$DIST_FILE_VERSION
# create symlinks
ln -sf $TARGET_RELEASE_DIR/$DIST_FILE $DIST_FILE
ln -sf $TARGET_RELEASE_DIR/$DIST_FILE_VERSION $DIST_FILE_VERSION
# create redirections (symlinks alternative)
touch content/$DIST_FILE.md
cat <<EOT >> content/$DIST_FILE.md
---
redirect: $TARGET_RELEASE_DIR/$DIST_FILE
permalink: $DIST_FILE
---
EOT
touch content/$DIST_FILE_VERSION.md
cat <<EOT >> content/$DIST_FILE_VERSION.md
---
redirect: $TARGET_RELEASE_DIR/$DIST_FILE_VERSION
permalink: $DIST_FILE_VERSION
---
EOT
# create VERSION file
[ -e VERSION ] && rm -- VERSION
echo $TRAVIS_TAG > VERSION

# commit and push
git add -Af .
git commit -m "Travis build $TRAVIS_BUILD_NUMBER: copy ${DIST_FILE}"
git push -fq origin $TARGET_BRANCH > /dev/null
exit 0
