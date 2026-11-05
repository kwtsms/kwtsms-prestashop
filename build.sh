#!/usr/bin/env bash
# FILE:      build.sh
# CREATED:   2026-11-06 01:07 +03
# TASK:      ci-dryrun-all
# AUTHOR:    M Alhassoun
# WHAT:      Builds dist/kwtsms.zip, the installable PrestaShop module, from the committed tree with
#            the composer runtime dependencies (vendor/) included and dev files left out.
# WHY:       classes/KwtsmsGateway.php and classes/KwtsmsSender.php require vendor/autoload.php, but
#            kwtsms/vendor/ is gitignored, so a zip of the repo tree fatals on load. This makes every
#            release zip the same, instead of a hand-built one.
# USAGE:     bash build.sh   (from the repo root; writes dist/kwtsms.zip, which is gitignored)
# DEPENDS:   git, composer 2, php, zip, unzip
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
OUT="${ROOT}/dist/kwtsms.zip"
WORK="$(mktemp -d)"
trap 'rm -rf "${WORK}"' EXIT

command -v composer >/dev/null || { echo "composer not found" >&2; exit 1; }

# Committed files only, so local caches, logs and a dev vendor/ never leak into the zip.
git -C "${ROOT}" archive HEAD kwtsms | tar -x -C "${WORK}"

# Runtime dependencies only, with an optimized autoloader.
(cd "${WORK}/kwtsms" && composer install --no-dev --optimize-autoloader --no-interaction --no-progress)

# Same guards the v1.0.0 zip carried: deny web access to vendor/, and an index.php in every vendor
# folder (PrestaShop's module validator expects one in each directory).
cat > "${WORK}/kwtsms/vendor/.htaccess" <<'HTACCESS'
# Apache 2.2
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>

# Apache 2.4
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
HTACCESS
find "${WORK}/kwtsms/vendor" -type d -exec cp -n "${WORK}/kwtsms/index.php" {}/ \;

mkdir -p "${ROOT}/dist"
rm -f "${OUT}"
(cd "${WORK}" && zip -qr "${OUT}" kwtsms \
    --exclude "kwtsms/composer.lock" \
    --exclude "*/.git*" \
    --exclude "*/.github/*" \
    --exclude "*/vendor/bin/*" \
    --exclude "*/vendor/*/tests/*" \
    --exclude "*/vendor/*/examples/*" \
    --exclude "*/phpunit.xml*" \
    --exclude "*/.php-cs-fixer*" \
    --exclude "*/.env.example" \
    --exclude "*/vendor/*/CONTRIBUTING.md" \
    --exclude "*/vendor/*/SECURITY.md" \
    --exclude "*/vendor/*/CHANGELOG.md" \
    --exclude "*/vendor/*/README.md" \
    --exclude "*.DS_Store")

# Read the listing once: grep -q closing the pipe early would fail the pipeline under pipefail.
LIST="$(unzip -Z1 "${OUT}")"
grep -qx "kwtsms/vendor/autoload.php" <<<"${LIST}" || { echo "vendor/autoload.php missing from zip" >&2; exit 1; }
grep -qx "kwtsms/kwtsms.php" <<<"${LIST}" || { echo "kwtsms.php missing from zip" >&2; exit 1; }
echo "Built ${OUT} ($(unzip -Z1 "${OUT}" | wc -l) entries)"
