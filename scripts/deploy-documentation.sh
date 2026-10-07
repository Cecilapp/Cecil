#!/bin/bash
set -e

# Deploy documentation files to website

# source
SOURCE_DOCS_DIR="docs"
SOURCE_API_DIR="docs/api"

# target
TARGET_REPO="Cecilapp/website"
if [ -z "${TARGET_BRANCH}" ]; then
  export TARGET_BRANCH="main"
fi
TARGET_DOCS_DIR="pages/documentation"
TARGET_API_DIR="static/documentation/library/api"
TARGET_CLONE_DIR="website" # not the branch name, which may contain a "/" (e.g. "docs/...")

# GitHub
USER_NAME=$GITHUB_ACTOR
USER_NAME="cecil-bot" # override for better commit history
USER_EMAIL="${GITHUB_ACTOR_ID}+${USER_NAME}@users.noreply.github.com"
HOME="${GITHUB_WORKSPACE}/HOME"

# prepare files
mkdir -p $HOME
cp -R $SOURCE_DOCS_DIR $HOME/$SOURCE_DOCS_DIR # includes the API dir

# clone or create target repo
echo "Starting to update documentation to ${TARGET_REPO}..."
cd $HOME
git config --global user.name "${USER_NAME}"
git config --global user.email "${USER_EMAIL}"
if [ -z "$(git ls-remote --heads https://${GITHUB_ACTOR}:${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git ${TARGET_BRANCH})" ]; then
  echo "Create branch '${TARGET_BRANCH}'"
  git clone --depth=1 --quiet https://${GITHUB_ACTOR}:${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git $TARGET_CLONE_DIR > /dev/null
  cd $TARGET_CLONE_DIR
  git checkout --orphan $TARGET_BRANCH
  echo "Deploy from https://github.com/$GITHUB_REPOSITORY/." > README.md
  git add README.md
  git commit -a -m "Create '$TARGET_BRANCH' branch"
  git push origin $TARGET_BRANCH
  cd $HOME
else
  echo "Clone branch '${TARGET_BRANCH}'"
  git clone --depth=1 --quiet --branch=$TARGET_BRANCH https://${GITHUB_ACTOR}:${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git $TARGET_CLONE_DIR > /dev/null
fi

# copy files to cloned repo
cd $TARGET_CLONE_DIR
# docs dir: mirrors the source, so that moved or deleted files are removed
# (the documentation index pages are owned by the website and kept)
mkdir -p $TARGET_DOCS_DIR
find $TARGET_DOCS_DIR -mindepth 1 -maxdepth 1 ! -name 'index.md' ! -name 'index.fr.md' -exec rm -rf {} +
cp -R $HOME/$SOURCE_DOCS_DIR/. $TARGET_DOCS_DIR
# api dir: mirrors the source
rm -rf $TARGET_API_DIR
mkdir -p $TARGET_API_DIR
cp -R $HOME/$SOURCE_API_DIR/. $TARGET_API_DIR

# commit and push
if [[ -n $(git status -s) ]]; then
  git add -Af .
  git commit -m "Build $GITHUB_RUN_NUMBER: update documentation."
  git push -fq origin $TARGET_BRANCH > /dev/null
else
  echo "Nothing to update"
fi
exit 0
