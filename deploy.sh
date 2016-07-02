#!/bin/bash
#set -e

SOURCE_BRANCH="master"
TARGET_REPO="github.com/PHPoole/phpoole.github.io.git"
TARGET_BRANCH="master"
DIST_FILE="phpoole.phar"

if [ "$TRAVIS_PULL_REQUEST" != "false" -o "$TRAVIS_BRANCH" != "$SOURCE_BRANCH" ]; then
    echo "Skipping deploy."
    exit 0
fi

echo "Starting to deploy Phar to gh-pages"

cp "dist/"$DIST_FILE $HOME/$DIST_FILE
cd $HOME
git config --global user.name "Travis"
git config --global user.email "travis@travis-ci.org"
git clone --quiet --branch=$TARGET_BRANCH https://${GH_TOKEN}@${TARGET_REPO} gh-pages > /dev/null
cd gh-pages
cp $HOME/$DIST_FILE $DIST_FILE
git add -f .
git commit -m "Travis build $TRAVIS_BUILD_NUMBER pushed to gh-pages"
git push -fq origin $TARGET_BRANCH > /dev/null
exit 0
