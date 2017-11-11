#!/bin/bash
set -e

SOURCE_BRANCH="master"
TARGET_REPO="PHPoole/phpoole.github.io"
TARGET_BRANCH="source"
SOURCE_DIST_DIR="dist"
TARGET_DIST_DIR="static"
DIST_FILE="phpoole.phar"
DIST_FILE_VERSION="phpoole.phar.version"

if [ "$TRAVIS_PULL_REQUEST" != "false" ]; then
    echo "Skipping deploy (PR? $TRAVIS_PULL_REQUEST)."
    exit 0
fi

echo "Starting to deploy ${DIST_FILE} to ${TARGET_REPO}..."

cp $SOURCE_DIST_DIR/$DIST_FILE $HOME/$DIST_FILE
cd $HOME
git config --global user.name "Travis"
git config --global user.email "contact@travis-ci.org"
git clone --quiet --branch=$TARGET_BRANCH https://${GH_TOKEN}@github.com/${TARGET_REPO}.git gh-pages > /dev/null
cd gh-pages/$TARGET_DIST_DIR/
mkdir -p download/$TRAVIS_TAG
cp $HOME/$DIST_FILE download/$TRAVIS_TAG/$DIST_FILE
sha1sum download/$TRAVIS_TAG/$DIST_FILE > download/$TRAVIS_TAG/$DIST_FILE_VERSION
ln -s $DIST_FILE download/$TRAVIS_TAG/$DIST_FILE
ln -s $DIST_FILE_VERSION download/$TRAVIS_TAG/$DIST_FILE_VERSION
git add -Af .
git commit -m "Travis build $TRAVIS_BUILD_NUMBER: copy ${DIST_FILE}"
git push -fq origin $TARGET_BRANCH > /dev/null
exit 0
