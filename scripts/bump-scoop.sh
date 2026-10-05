#!/bin/bash
set -e

# Generate Scoop manifest (to be deployed with release files)

# version
if [ -z "${VERSION}" ]; then
  export VERSION=$(echo $GITHUB_REF | cut -d'/' -f 3)
fi

# pre-release
if [ -z "${PRERELEASE}" ]; then
  export PRERELEASE="false"
fi

# SHA1
if [ -z "${SHA1}" ]; then
  if [ ! -f dist/cecil.phar ]; then
    echo "SHA1 is required"
    exit 1
  fi
  export SHA1=$(sha1sum dist/cecil.phar | cut -d' ' -f 1)
fi

# output (copied to website's static dir by deploy-release.sh)
OUTPUT_DIR="${OUTPUT_DIR:-dist/scoop}"

# Scoop
SCOOP_FILE_JSON="cecil.json"
SCOOP_FILE_JSON_PREVIEW="cecil-preview.json"

echo "Starting generate Scoop file..."

# Scoop manifest
if [ "${PRERELEASE}" == 'true' ]; then
  SCOOP_FILE_JSON="$SCOOP_FILE_JSON_PREVIEW"
fi
# create manifest in output dir
mkdir -p "$OUTPUT_DIR"
cat <<EOT > "$OUTPUT_DIR/$SCOOP_FILE_JSON"
{
  "description": "A simple and powerful content-driven static site generator.",
  "homepage": "https://cecil.app",
  "license": "EUPL-1.2",
  "bin": "cecil.phar",
  "notes": [
    "Run 'cecil' to get started",
    "Run 'scoop update cecil' instead of 'cecil self-update' to update"
  ],
  "suggest": {
    "PHP": ["php"]
  },
  "url": "https://cecil.app/download/$VERSION/cecil.phar",
  "version": "$VERSION",
  "hash": "sha1:$SHA1",
  "checkver": {
    "url": "https://cecil.app/VERSION",
    "regex": "([\\\d.]+)"
  },
  "autoupdate": {
    "url": "https://cecil.app/download/\$version/cecil.phar",
    "hash": {
      "url": "\$url.sha1"
    }
  }
}
EOT
echo "Scoop file generated: $OUTPUT_DIR/$SCOOP_FILE_JSON"
exit 0
