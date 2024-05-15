#!/bin/bash
set -e

# Deploy releases files to website

# params
REF=$(echo $GITHUB_REF | cut -d'/' -f 3)
TARGET_REPO="Cecilapp/website"
TARGET_BRANCH="master"
TARGET_RELEASE_DIR="download/$REF"
TARGET_DIST_DIR="static"
DIST_FILE="cecil.phar"
DIST_FILE_SHA1="cecil.phar.sha1"
TARGET_PAGES_DIR="pages"
USER_NAME=$GITHUB_ACTOR
USER_EMAIL="${GITHUB_ACTOR}@cecil.app"
HOME="${GITHUB_WORKSPACE}/HOME"
BUILD_NUMBER=$GITHUB_RUN_NUMBER

echo "Starting deploy of distribution file..."
mkdir $HOME
cp dist/$DIST_FILE $HOME/$DIST_FILE

# clone target repo
cd $HOME
git config --global user.name "${USER_NAME}"
git config --global user.email "${USER_EMAIL}"
git clone --quiet --branch=$TARGET_BRANCH https://${GITHUB_TOKEN}@github.com/${TARGET_REPO}.git ${TARGET_REPO} > /dev/null

# copy/create releases files
cd $TARGET_REPO/$TARGET_DIST_DIR
mkdir -p $TARGET_RELEASE_DIR
# .phar
cp $HOME/$DIST_FILE $TARGET_RELEASE_DIR/$DIST_FILE
# .sha1
cd $TARGET_RELEASE_DIR
sha1sum $DIST_FILE > $DIST_FILE_SHA1
cd ../..

# create VERSION file
[ -e VERSION ] && rm -- VERSION
echo $REF > VERSION

# create website redirections files
now=$(date +"%Y-%m-%d")
cd ../$TARGET_PAGES_DIR
rm -f $DIST_FILE.md
cat <<EOT >> $DIST_FILE.md
---
redirect: $TARGET_RELEASE_DIR/$DIST_FILE
slug: cecil
output: phar
date: $now
---
EOT
rm -f $DIST_FILE_SHA1.md
cat <<EOT >> $DIST_FILE_SHA1.md
---
redirect: $TARGET_RELEASE_DIR/$DIST_FILE_SHA1
slug: cecil
output: sha1
date: $now
---
EOT
cd ..

# commit
git add -Af .
git commit -m "Build $BUILD_NUMBER: deploy ${DIST_FILE} and create redirections"
# push
git push -fq origin $TARGET_BRANCH > /dev/null

exit 0
