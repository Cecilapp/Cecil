#!/bin/bash
set -e

TARGET_REPO="PHPoole/phpoole.github.io"
TARGET_BRANCH="master"
TARGET_DIST_DIR="download/$TRAVIS_TAG"
DIST_FILE="phpoole.phar"
DIST_FILE_VERSION="phpoole.phar.version"

if [ "$TRAVIS_PULL_REQUEST" != "false" ]; then
  echo "Skipping deploy (PR? $TRAVIS_PULL_REQUEST)."
  exit 0
fi

if [ ! -n "$TRAVIS_TAG" ]; then
  TARGET_DIST_DIR="download/$TRAVIS_BRANCH"
fi

echo "Starting to deploy ${DIST_FILE} to ${TARGET_REPO}..."
cp dist/$DIST_FILE $HOME/$DIST_FILE

# clone target repo
cd $HOME
git config --global user.name "Travis"
git config --global user.email "contact@travis-ci.org"
git clone --quiet --branch=$TARGET_BRANCH https://${GH_TOKEN}@github.com/${TARGET_REPO}.git ${TARGET_REPO} > /dev/null

cd $TARGET_REPO
mkdir -p $TARGET_DIST_DIR
# copy dist file
cp $HOME/$DIST_FILE $TARGET_DIST_DIR/$DIST_FILE
# create SHA1
sha1sum $TARGET_DIST_DIR/$DIST_FILE > $TARGET_DIST_DIR/$DIST_FILE_VERSION
# create symlinks
ln -sf $TARGET_DIST_DIR/$DIST_FILE $DIST_FILE
ln -sf $TARGET_DIST_DIR/$DIST_FILE_VERSION $DIST_FILE_VERSION
# create VERSION file
[ -e VERSION ] && rm -- VERSION
echo $TRAVIS_TAG > VERSION

# commit and push
git add -Af .
git commit -m "Travis build $TRAVIS_BUILD_NUMBER: copy ${DIST_FILE}"
git push -fq origin $TARGET_BRANCH > /dev/null
exit 0
