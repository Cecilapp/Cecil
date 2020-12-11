#!/bin/bash
set -e

# Deploy documentation files to website

SOURCE_DOCS_DIR="docs"
TARGET_REPO="Cecilapp/cecil.app"
TARGET_BRANCH="master"
TARGET_DOCS_DIR="content/documentation"
USER_NAME=$GITHUB_ACTOR
USER_EMAIL="${GITHUB_ACTOR}@cecil.app"
HOME="${GITHUB_WORKSPACE}/HOME"
#BUILD_NUMBER=$TRAVIS_BUILD_NUMBER
BUILD_NUMBER=$GITHUB_RUN_NUMBER

echo "Starting to update documentation to ${TARGET_REPO}..."
cp -R $SOURCE_DOCS_DIR $HOME/$SOURCE_DOCS_DIR

# clone target repo
mkdir $HOME
cd $HOME
git config --global user.name "${USER_NAME}"
git config --global user.email "${USER_EMAIL}"
git clone --quiet --branch=$TARGET_BRANCH https://${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git ${TARGET_REPO} > /dev/null

cd $TARGET_REPO
mkdir -p $TARGET_DOCS_DIR
# copy documentation dir
cp -Rf $HOME/$SOURCE_DOCS_DIR/* $TARGET_DOCS_DIR

# commit and push
if [[ -n $(git status -s) ]]; then
  git add -Af .
  git commit -m "Build $BUILD_NUMBER: update ${TARGET_DOCS_DIR}"
  git push -fq origin $TARGET_BRANCH > /dev/null
else
  echo "Nothing to update"
fi
exit 0
