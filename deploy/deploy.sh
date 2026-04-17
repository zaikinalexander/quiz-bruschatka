#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
WEBSITE_DIR="$ROOT_DIR/website"
DASHBOARD_DIR="$ROOT_DIR/dashboard"
ADMIN_TARGET_DIR="$WEBSITE_DIR/public/admin"
WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-$WEB_USER}"

echo "==> website: composer install"
cd "$WEBSITE_DIR"
composer install --no-dev --optimize-autoloader

echo "==> website: clear cache"
php bin/console cache:clear --env=prod

echo "==> dashboard: npm ci"
cd "$DASHBOARD_DIR"
npm ci

echo "==> dashboard: build"
npm run build

echo "==> sync dashboard dist -> website/public/admin"
rm -rf "$ADMIN_TARGET_DIR"
mkdir -p "$ADMIN_TARGET_DIR"
cp -R "$DASHBOARD_DIR/dist/." "$ADMIN_TARGET_DIR/"

echo "==> ensure writable paths"
mkdir -p "$WEBSITE_DIR/var" "$WEBSITE_DIR/public/uploads/quiz" "$WEBSITE_DIR/data"
[ -f "$WEBSITE_DIR/data/quiz-leads.json" ] || printf '{\n  "lastNumber": 0,\n  "items": []\n}\n' > "$WEBSITE_DIR/data/quiz-leads.json"
chown -R "$WEB_USER:$WEB_GROUP" "$WEBSITE_DIR/var" "$WEBSITE_DIR/public/uploads/quiz" "$WEBSITE_DIR/data" 2>/dev/null || true
find "$WEBSITE_DIR/var" "$WEBSITE_DIR/public/uploads/quiz" "$WEBSITE_DIR/data" -type d -exec chmod 775 {} + || true
find "$WEBSITE_DIR/var" "$WEBSITE_DIR/public/uploads/quiz" "$WEBSITE_DIR/data" -type f -exec chmod 664 {} + || true
chmod 775 "$WEBSITE_DIR/data" || true
chmod 666 "$WEBSITE_DIR/data/quiz-config.json" "$WEBSITE_DIR/data/quiz-leads.json" 2>/dev/null || true

echo "Deploy build complete."
