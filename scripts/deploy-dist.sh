#!/bin/bash
set -e

# Deploy dist file to website

TARGET_REPO="Cecilapp/website"
TARGET_BRANCH="source"
TARGET_RELEASE_DIR="download/$TRAVIS_TAG"
TARGET_DIST_DIR="static"
DIST_FILE="cecil.phar"
DIST_FILE_SHA1="cecil.phar.sha1"
TARGET_CONTENT_DIR="content"

if [ ! -n "$TRAVIS_TAG" ]; then
  TARGET_RELEASE_DIR="download/$TRAVIS_BRANCH"
fi

echo "Starting deploy of dist files..."
cp dist/$DIST_FILE $HOME/$DIST_FILE

# clone target repo
cd $HOME
git config --global user.name "Travis"
git config --global user.email "contact@travis-ci.org"
git clone --quiet --branch=$TARGET_BRANCH https://${GH_TOKEN}@github.com/${TARGET_REPO}.git ${TARGET_REPO} > /dev/null

# prepare dist files

# cd static dir
cd $TARGET_REPO/$TARGET_DIST_DIR
mkdir -p $TARGET_RELEASE_DIR
# copy dist file
cp $HOME/$DIST_FILE $TARGET_RELEASE_DIR/$DIST_FILE
# create SHA1
cd $TARGET_RELEASE_DIR
sha1sum $DIST_FILE > $DIST_FILE_SHA1
cd ../..
# create symlinks
ln -sf $TARGET_RELEASE_DIR/$DIST_FILE $DIST_FILE
ln -sf $TARGET_RELEASE_DIR/$DIST_FILE_SHA1 $DIST_FILE_SHA1

# create VERSION file
[ -e VERSION ] && rm -- VERSION
echo $TRAVIS_TAG > VERSION

# commit and push
git add -Af .
git commit -m "Travis build $TRAVIS_BUILD_NUMBER: copy ${DIST_FILE}* files"
git push -fq origin $TARGET_BRANCH > /dev/null

# prepare redirections (symlinks alternative)

# cd content dir
cd ../$TARGET_CONTENT_DIR

# create content files
rm -f $DIST_FILE.md
cat <<EOT >> $DIST_FILE.md
---
redirect: $TARGET_RELEASE_DIR/$DIST_FILE
slug: cecil
output: phar
---
EOT
rm -f $DIST_FILE_SHA1.md
cat <<EOT >> $DIST_FILE_SHA1.md
---
redirect: $TARGET_RELEASE_DIR/$DIST_FILE_SHA1
slug: cecil
output: sha1
---
EOT

# commit and push
git add -Af .
git commit -m "Travis build $TRAVIS_BUILD_NUMBER: create redirections"
git push -fq origin $TARGET_BRANCH > /dev/null

exit 0
