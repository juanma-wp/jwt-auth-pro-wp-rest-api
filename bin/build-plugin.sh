#!/bin/bash

# JWT Auth Pro - WordPress.org Build Script
# This script creates a clean ZIP file for WordPress.org plugin submission

# Get plugin version from main plugin file
VERSION=$(grep "Version:" jwt-auth-pro-wp-rest-api.php | sed 's/.*Version: *//' | sed 's/ *$//')

if [ -z "$VERSION" ]; then
    echo "Error: Could not extract version from plugin file"
    exit 1
fi

# Set output filename with version
OUTPUT_FILE="jwt-auth-pro-wp-rest-api-v${VERSION}.zip"

echo "Building JWT Auth Pro v${VERSION}..."
echo "Output file: ${OUTPUT_FILE}"

# Remove existing build if it exists
if [ -f "$OUTPUT_FILE" ]; then
    rm "$OUTPUT_FILE"
    echo "Removed existing ${OUTPUT_FILE}"
fi

# Create ZIP file excluding development files
zip -r "$OUTPUT_FILE" . \
    -x "*.git*" \
    -x "tests/*" \
    -x "DOCS/*" \
    -x "node_modules/*" \
    -x "vendor/*" \
    -x "*.md" \
    -x "package*.json" \
    -x "composer.*" \
    -x "phpcs.xml" \
    -x "phpstan.neon" \
    -x "phpunit*.xml" \
    -x ".wp-env.json" \
    -x ".phpunit.result.cache" \
    -x ".github/*" \
    -x ".claude/*" \
    -x "bin/*" \
    -x "build-plugin.sh"

if [ $? -eq 0 ]; then
    echo "✅ Successfully created ${OUTPUT_FILE}"
    echo "📦 File size: $(du -h "$OUTPUT_FILE" | cut -f1)"
    echo ""
    echo "Files included in the build:"
    unzip -l "$OUTPUT_FILE" | grep -E '\.(php|txt|js|css)$' | head -20
    echo ""
    echo "Ready for WordPress.org submission! 🚀"
else
    echo "❌ Error creating ZIP file"
    exit 1
fi