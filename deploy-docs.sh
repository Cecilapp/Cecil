#!/bin/bash
set -e

SOURCE_BRANCH="master"
TARGET_REPO="PHPoole/phpoole.github.io"
TARGET_BRANCH="source"
SOURCE_DOCS_DIR="docs"
TARGET_DOCS_DIR="content/documentation"

if [ $TRAVIS_PHP_VERSION != "5.6" -o "$TRAVIS_PULL_REQUEST" != "false" -o "$TRAVIS_BRANCH" != "$SOURCE_BRANCH" ]; then
    echo "Skipping deploy."
    exit 0
fi

echo "Starting to update documentation..."

cp -R $SOURCE_DOCS_DIR $HOME/$SOURCE_DOCS_DIR
cd $HOME
git config --global user.name "Travis"
git config --global user.email "contact@travis-ci.org"
git clone --quiet --branch=$TARGET_BRANCH https://${GH_TOKEN}@github.com/${TARGET_REPO}.git gh-pages > /dev/null
cd gh-pages
mkdir -p $TARGET_DOCS_DIR
cp -Rf $HOME/$SOURCE_DOCS_DIR/* $TARGET_DOCS_DIR
git add -f .
git commit -m "Travis build $TRAVIS_BUILD_NUMBER pushed to $TARGET_DOCS_DIR"
git push -fq origin $TARGET_BRANCH > /dev/null
exit 0
