#!/bin/bash

# JWT Auth Pro - WordPress.org Build Script
# This script creates a clean ZIP file for WordPress.org plugin submission
# Following WordPress.org best practices for plugins with Composer dependencies

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get plugin version from main plugin file
VERSION=$(grep "Version:" jwt-auth-pro-wp-rest-api.php | sed 's/.*Version: *//' | sed 's/ *$//')

if [ -z "$VERSION" ]; then
    echo -e "${RED}Error: Could not extract version from plugin file${NC}"
    exit 1
fi

# Set output filename with version
OUTPUT_FILE="jwt-auth-pro-wp-rest-api-v${VERSION}.zip"

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   JWT Auth Pro - WordPress.org Build Script      ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}Building version: ${VERSION}${NC}"
echo -e "Output file: ${OUTPUT_FILE}"
echo ""

# Remove existing build if it exists
if [ -f "$OUTPUT_FILE" ]; then
    rm "$OUTPUT_FILE"
    echo -e "${YELLOW}⚠ Removed existing ${OUTPUT_FILE}${NC}"
    echo ""
fi

# Clean and reinstall production dependencies
# Best practices from WordPress.org:
# - Use --no-dev to exclude development dependencies
# - Use --optimize-autoloader for better performance
# - Use --no-interaction for automated builds
echo -e "${GREEN}📦 Preparing production dependencies...${NC}"

# Remove existing vendor directory to ensure clean install
if [ -d "vendor" ]; then
    echo -e "${YELLOW}🧹 Removing existing vendor directory...${NC}"
    # Fix permissions first
    chmod -R u+w vendor 2>/dev/null || true
    # Remove the directory
    if ! rm -rf vendor 2>/dev/null; then
        echo -e "${RED}❌ Error: Cannot remove vendor directory. Please run:${NC}"
        echo -e "${YELLOW}   sudo rm -rf vendor${NC}"
        echo -e "${YELLOW}   Then run this script again.${NC}"
        exit 1
    fi
fi

if [ -f "composer.lock" ]; then
    echo -e "${YELLOW}🧹 Removing composer.lock for fresh install...${NC}"
    rm -f composer.lock
fi

echo ""
echo -e "${GREEN}📦 Installing production dependencies...${NC}"
echo -e "${BLUE}   Running: composer install --no-dev --optimize-autoloader --no-interaction${NC}"
echo ""
composer install --no-dev --optimize-autoloader --no-interaction

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Error installing composer dependencies${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}✅ Dependencies installed successfully${NC}"
echo ""

# Verify vendor directory exists and contains required packages
if [ ! -d "vendor/wp-rest-auth/auth-toolkit" ]; then
    echo -e "${RED}❌ Error: wp-rest-auth-toolkit not found in vendor directory${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Verified wp-rest-auth-toolkit is installed${NC}"
echo ""

# Create ZIP file excluding development files but including vendor/
echo -e "${GREEN}📦 Creating distribution ZIP file...${NC}"
echo ""
zip -r "$OUTPUT_FILE" . \
    -x "*.git*" \
    -x "tests/*" \
    -x "DOCS/*" \
    -x "node_modules/*" \
    -x "tmp/*" \
    -x "*.md" \
    -x "*.log" \
    -x "*.zip" \
    -x "*.neon" \
    -x "package*.json" \
    -x "composer.json" \
    -x "composer.lock" \
    -x "phpcs.xml" \
    -x "phpunit*.xml" \
    -x ".wp-env.json" \
    -x ".phpunit.result.cache" \
    -x ".github/*" \
    -x ".claude/*" \
    -x "bin/*" \
    -x "build-plugin.sh" \
    > /dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Successfully created ${OUTPUT_FILE}${NC}"
    echo -e "${GREEN}📦 File size: $(du -h "$OUTPUT_FILE" | cut -f1)${NC}"
    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
    echo -e "${BLUE}Files included in the build:${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
    unzip -l "$OUTPUT_FILE" | grep -E '\.(php|txt|yml)$' | head -20
    echo ""
    echo -e "${BLUE}Vendor packages included:${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════${NC}"
    unzip -l "$OUTPUT_FILE" | grep "vendor/wp-rest-auth" | head -10
    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║  Ready for WordPress.org submission! 🚀           ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${YELLOW}Next steps:${NC}"
    echo -e "  1. Test the plugin in a clean WordPress installation"
    echo -e "  2. Upload to WordPress.org SVN repository"
    echo -e "  3. Tag the release in SVN"
    echo ""
else
    echo -e "${RED}❌ Error creating ZIP file${NC}"
    exit 1
fi
