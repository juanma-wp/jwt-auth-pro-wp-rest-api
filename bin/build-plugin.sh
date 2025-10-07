#!/bin/sh
# Build plugin distribution archive for WordPress.org using WP-CLI dist-archive

set -e

echo "==> Installing production dependencies..."
composer install --no-dev --prefer-dist --no-interaction

echo "==> Checking for zip utility..."
if ! command -v zip > /dev/null 2>&1; then
    echo "Error: zip utility is not installed."
    echo "Please install it first by running:"
    echo "  docker exec -u root \$(docker ps -qf 'name=tests-cli') apk add --no-cache zip"
    exit 1
fi

echo "==> Installing WP-CLI dist-archive command..."
wp package install wp-cli/dist-archive-command:@stable 2>/dev/null || true

echo "==> Creating distribution archive..."
VERSION="${1:-1.0.0}"
OUTPUT_FILE="build/jwt-auth-pro-${VERSION}.zip"
wp dist-archive . "${OUTPUT_FILE}"

echo "==> Build complete: ${OUTPUT_FILE}"
