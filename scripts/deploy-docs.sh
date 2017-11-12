#!/bin/bash
set -e

# Deploy documentation files to website

SOURCE_DOCS_DIR="docs"
TARGET_REPO="PHPoole/phpoole.github.io"
TARGET_BRANCH="source"
TARGET_DOCS_DIR="content/documentation"

echo "Starting to update documentation to ${TARGET_REPO}..."
cp -R $SOURCE_DOCS_DIR $HOME/$SOURCE_DOCS_DIR

# clone target repo
cd $HOME
git config --global user.name "Travis"
git config --global user.email "contact@travis-ci.org"
git clone --quiet --branch=$TARGET_BRANCH https://${GH_TOKEN}@github.com/${TARGET_REPO}.git ${TARGET_REPO} > /dev/null

cd $TARGET_REPO
mkdir -p $TARGET_DOCS_DIR
# copy documentation dir
cp -Rf $HOME/$SOURCE_DOCS_DIR/* $TARGET_DOCS_DIR

# commit and push
if [[ -n $(git status -s) ]]; then
  git add -Af .
  git commit -m "Travis build $TRAVIS_BUILD_NUMBER: update ${TARGET_DOCS_DIR}"
  git push -fq origin $TARGET_BRANCH > /dev/null
else
  echo "Nothing to update"
fi
exit 0
