#!/bin/bash
#set -e

REPO="PHPoole/phpoole.github.io"
SOURCE_BRANCH="master"
TARGET_BRANCH="master"
DIST_FILE="phpoole.phar"

#if [ "$TRAVIS_PULL_REQUEST" != "false" -o "$TRAVIS_BRANCH" != "$SOURCE_BRANCH" ]; then
#    echo "Skipping deploy."
#    exit 0
#fi
if [ "$TRAVIS_PULL_REQUEST" != "false" ]; then
    echo "Skipping deploy (PR? $TRAVIS_PULL_REQUEST)."
    exit 0
fi

echo "Starting to deploy ${DIST_FILE} to gh-pages..."

cp "dist/"$DIST_FILE $HOME/$DIST_FILE
cd $HOME
git config --global user.name "Travis"
git config --global user.email "contact@travis-ci.org"
git clone --quiet --branch=$TARGET_BRANCH https://${GH_TOKEN}@github.com/${REPO}.git gh-pages > /dev/null
cd gh-pages
cp $HOME/$DIST_FILE $DIST_FILE
sha1sum $DIST_FILE > $DIST_FILE".version"
git add -f .
git commit -m "Travis build $TRAVIS_BUILD_NUMBER: copy ${DIST_FILE}"
git push -fq origin $TARGET_BRANCH > /dev/null
exit 0
