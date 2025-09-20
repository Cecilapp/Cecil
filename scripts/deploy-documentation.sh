#!/bin/bash
set -e

# Deploy documentation files to website

# source
SOURCE_DOCS_DIR="docs"
SOURCE_API_DIR="docs/api"

# target
TARGET_REPO="Cecilapp/website"
if [ -z "${TARGET_BRANCH}" ]; then
  export TARGET_BRANCH="master"
fi
TARGET_DOCS_DIR="pages/documentation"
TARGET_API_DIR="static/documentation/library/api"

# GitHub
USER_NAME=$GITHUB_ACTOR
USER_EMAIL="${GITHUB_ACTOR}@cecil.app"
HOME="${GITHUB_WORKSPACE}/HOME"

# prepare files
mkdir $HOME
cp -R $SOURCE_DOCS_DIR $HOME/$SOURCE_DOCS_DIR
cp -R $SOURCE_API_DIR $HOME/$SOURCE_API_DIR

# clone or create target repo
echo "Starting to update documentation to ${TARGET_REPO}..."
cd $HOME
git config --global user.name "${USER_NAME}"
git config --global user.email "${USER_EMAIL}"
if [ -z "$(git ls-remote --heads https://${GITHUB_ACTOR}:${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git ${TARGET_BRANCH})" ]; then
  echo "Create branch '${TARGET_BRANCH}'"
  git clone --quiet https://${GITHUB_ACTOR}:${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git $TARGET_BRANCH > /dev/null
  cd $TARGET_BRANCH
  git checkout --orphan $TARGET_BRANCH
  echo "Deploy from https://github.com/$GITHUB_REPOSITORY/." > README.md
  git add README.md
  git commit -a -m "Create '$TARGET_BRANCH' branch"
  git push origin $TARGET_BRANCH
  cd ..
else
  echo "Clone branch '${TARGET_BRANCH}'"
  git clone --quiet --branch=$TARGET_BRANCH https://${GITHUB_ACTOR}:${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git $TARGET_BRANCH > /dev/null
fi

# copy files to cloned repo
cd $TARGET_BRANCH
# docs dir
mkdir -p $TARGET_DOCS_DIR
#find $HOME/$SOURCE_DOCS_DIR/ -type f -name '*.md' | xargs cp -t $TARGET_DOCS_DIR
#cp -Rf $HOME/$SOURCE_DOCS_DIR/* $TARGET_DOCS_DIR
cp -f $HOME/$SOURCE_DOCS_DIR/*.md $TARGET_DOCS_DIR
# api dir
mkdir -p $TARGET_API_DIR
cp -Rf $HOME/$SOURCE_API_DIR/* $TARGET_API_DIR

# commit and push
if [[ -n $(git status -s) ]]; then
  git add -Af .
  git commit -m "Build $GITHUB_RUN_NUMBER: update documentation"
  git push -fq origin $TARGET_BRANCH > /dev/null
else
  echo "Nothing to update"
fi
exit 0
