#!/usr/bin/env bash
# =============================================================================
# build.sh — Eva Slideover Cart plugin zip builder
#
# Usage:
#   chmod +x build.sh
#   ./build.sh
#
# Output: eva-slideover-cart-{VERSION}.zip  (one level above this script)
# =============================================================================

set -euo pipefail

PLUGIN_SLUG="eva-slideover-cart"
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PLUGIN_FILE="${SCRIPT_DIR}/${PLUGIN_SLUG}.php"

# ---------------------------------------------------------------------------
# 1. Read version from the plugin header
# ---------------------------------------------------------------------------
if [[ ! -f "$PLUGIN_FILE" ]]; then
  echo "ERROR: Plugin file not found: ${PLUGIN_FILE}" >&2
  exit 1
fi

VERSION=$( grep -m1 "^ \* Version:" "$PLUGIN_FILE" | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]' )

if [[ -z "$VERSION" ]]; then
  echo "ERROR: Could not read Version from plugin header." >&2
  exit 1
fi

echo "Building ${PLUGIN_SLUG} v${VERSION} ..."

# ---------------------------------------------------------------------------
# 2. Prepare staging directory
# ---------------------------------------------------------------------------
PARENT_DIR="$( dirname "$SCRIPT_DIR" )"
STAGE_DIR="${PARENT_DIR}/_build_stage/${PLUGIN_SLUG}"
ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"
ZIP_PATH="${PARENT_DIR}/${ZIP_NAME}"

# Clean up any previous build artefacts.
rm -rf "${PARENT_DIR}/_build_stage"
mkdir -p "${STAGE_DIR}"

# ---------------------------------------------------------------------------
# 3. Copy distributable files — rsync excludes dev-only items
# ---------------------------------------------------------------------------
rsync -a \
  --exclude="build.sh" \
  --exclude=".git/" \
  --exclude=".gitignore" \
  --exclude=".gitattributes" \
  --exclude=".editorconfig" \
  --exclude=".phpcs.xml" \
  --exclude="phpcs.xml" \
  --exclude="phpunit.xml" \
  --exclude="*.log" \
  --exclude=".DS_Store" \
  --exclude="Thumbs.db" \
  --exclude="node_modules/" \
  --exclude="vendor/" \
  --exclude="*.map" \
  --exclude="*.zip" \
  --exclude="_build_stage/" \
  "${SCRIPT_DIR}/" "${STAGE_DIR}/"

# ---------------------------------------------------------------------------
# 4. Create the zip
# ---------------------------------------------------------------------------
rm -f "$ZIP_PATH"

cd "${PARENT_DIR}/_build_stage"
zip -r "$ZIP_PATH" "${PLUGIN_SLUG}/" --quiet

# ---------------------------------------------------------------------------
# 5. Clean up and report
# ---------------------------------------------------------------------------
rm -rf "${PARENT_DIR}/_build_stage"

SIZE=$( du -sh "$ZIP_PATH" | cut -f1 )
echo "Done!"
echo "  Output : ${ZIP_PATH}"
echo "  Size   : ${SIZE}"
