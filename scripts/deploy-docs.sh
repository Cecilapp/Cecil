#!/bin/bash
set -e

# Deploy documentation files to website

SOURCE_DOCS_DIR="docs"
TARGET_REPO="Cecilapp/website"
if [ -z "${TARGET_BRANCH}" ]; then
  export TARGET_BRANCH="master"
fi
TARGET_DOCS_DIR="pages/documentation"
USER_NAME=$GITHUB_ACTOR
USER_EMAIL="${GITHUB_ACTOR}@cecil.app"
HOME="${GITHUB_WORKSPACE}/HOME"
BUILD_NUMBER=$GITHUB_RUN_NUMBER

echo "Starting to update documentation to ${TARGET_REPO}..."
mkdir $HOME
cp -R $SOURCE_DOCS_DIR $HOME/$SOURCE_DOCS_DIR

# clone or create target repo
cd $HOME
git config --global user.name "${USER_NAME}"
git config --global user.email "${USER_EMAIL}"
if [ -z "$(git ls-remote --heads https://${GITHUB_ACTOR}:${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git ${TARGET_BRANCH})" ]; then
  echo "Create branch '${TARGET_BRANCH}'"
  git clone --quiet https://${GITHUB_ACTOR}:${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git $TARGET_BRANCH > /dev/null
  cd $TARGET_BRANCH
  git checkout --orphan $TARGET_BRANCH
  echo "Deploy from https://github.com/$GITHUB_REPOSITORY/tree/$TARGET_BRANCH/$SOURCE_DOCS_DIR." > README.md
  git add README.md
  git commit -a -m "Create '$TARGET_BRANCH' branch"
  git push origin $TARGET_BRANCH
  cd ..
else
  echo "Clone branch '${TARGET_BRANCH}'"
  git clone --quiet --branch=$TARGET_BRANCH https://${GITHUB_ACTOR}:${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git $TARGET_BRANCH > /dev/null
fi

cd $TARGET_BRANCH
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
