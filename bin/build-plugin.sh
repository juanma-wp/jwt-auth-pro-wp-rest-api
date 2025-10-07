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
cd /tmp && wp package install wp-cli/dist-archive-command:@stable 2>/dev/null || true

echo "==> Creating distribution archive..."
cd /var/www/html/wp-content/plugins/jwt-auth-pro-wp-rest-api

# Extract version from main plugin file
VERSION=$(grep -E "^\s*\*\s*Version:" jwt-auth-pro-wp-rest-api.php | sed -E 's/.*Version:\s*([0-9.]+).*/\1/')
if [ -z "$VERSION" ]; then
    VERSION="${1:-1.0.0}"
fi

# Convert dots to hyphens for filename
VERSION_HYPHENATED=$(echo "$VERSION" | tr '.' '-')
OUTPUT_FILE="build/jwt-auth-pro-wp-rest-api-${VERSION_HYPHENATED}.zip"

# Create build directory and remove old archive
mkdir -p build
rm -f "${OUTPUT_FILE}"

wp dist-archive . "${OUTPUT_FILE}" --skip-plugins

echo "==> Build complete: ${OUTPUT_FILE}"
ls -lh "${OUTPUT_FILE}"
