#!/bin/bash

# Colors and formatting
RESET='\033[0m'
BOLD='\033[1m'
CYAN='\033[1;36m'
GREEN='\033[1;32m'
YELLOW='\033[1;33m'
BLUE='\033[1;34m'
RED='\033[1;31m'
GRAY='\033[90m'
SEPARATOR="${GRAY}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"

# Local path for development
LOCAL_PATH="/Users/juanmanuelgarrido/PROJECTS/2025/wp-rest-auth-toolkit"
GITHUB_URL="https://github.com/juanma-wp/wp-rest-auth-toolkit.git"

function check_mode() {
    echo ""
    echo -e "${CYAN}🔍 WP REST Auth Toolkit Status${RESET}"
    echo -e "$SEPARATOR"

    # Check if composer.lock exists
    if [ ! -f "composer.lock" ]; then
        echo -e "${RED}❌ Package not installed${RESET}"
        echo "Run 'composer install' to install dependencies"
        echo -e "$SEPARATOR"
        echo ""
        exit 1
    fi

    # First check if the vendor directory is actually a symlink
    IS_SYMLINK=false
    if [ -L "vendor/wp-rest-auth/auth-toolkit" ]; then
        IS_SYMLINK=true
        ACTUAL_PATH=$(readlink "vendor/wp-rest-auth/auth-toolkit")
    fi

    # Parse composer.lock to find the package
    PKG_TYPE=$(php -r "
        \$lock = json_decode(file_get_contents('composer.lock'), true);
        foreach(\$lock['packages'] ?? [] as \$p) {
            if(\$p['name'] === 'wp-rest-auth/auth-toolkit') {
                echo \$p['dist']['type'];
                exit(0);
            }
        }
        echo 'not-found';
    ")

    if [ "$PKG_TYPE" = "not-found" ]; then
        echo -e "${RED}❌ Package not installed${RESET}"
        echo "Run 'composer install' to install dependencies"
    elif [ "$PKG_TYPE" = "path" ] || [ "$IS_SYMLINK" = true ]; then
        echo -e "${YELLOW}⚡ DEVELOPMENT MODE${RESET}"
        if [ "$IS_SYMLINK" = true ]; then
            echo -e "${YELLOW}📁 Local Path:${RESET} $ACTUAL_PATH"
            echo -e "${YELLOW}🔗 Type:${RESET} Symlink (changes reflect immediately)"
            if [ "$PKG_TYPE" != "path" ]; then
                echo -e "${GRAY}⚠️  Note: Symlink exists but composer.lock shows production mode${RESET}"
                echo -e "${GRAY}   Run 'composer dev-mode' to sync composer configuration${RESET}"
            fi
        else
            echo -e "${YELLOW}📁 Local Path:${RESET} $LOCAL_PATH"
            echo -e "${YELLOW}🔗 Type:${RESET} Symlink (changes reflect immediately)"
        fi
    else
        VERSION=$(php -r "
            \$lock = json_decode(file_get_contents('composer.lock'), true);
            foreach(\$lock['packages'] ?? [] as \$p) {
                if(\$p['name'] === 'wp-rest-auth/auth-toolkit') {
                    echo \$p['version'] ?? 'Unknown';
                    exit(0);
                }
            }
        ")
        echo -e "${BLUE}📦 PRODUCTION MODE${RESET}"
        echo -e "${BLUE}🌐 Source:${RESET} GitHub Repository"
        echo -e "${BLUE}🏷️ Version:${RESET} $VERSION"
        echo -e "${BLUE}📍 URL:${RESET} $GITHUB_URL"
    fi

    echo -e "$SEPARATOR"
    echo ""
}

function switch_to_dev() {
    echo ""
    echo -e "${CYAN}🔧 Switching to DEVELOPMENT mode...${RESET}"
    echo -e "$SEPARATOR"

    # Configure local repository
    composer config repositories.local-toolkit "{\"type\": \"path\", \"url\": \"$LOCAL_PATH\", \"options\": {\"symlink\": true}}"

    # Update dependency
    composer require wp-rest-auth/auth-toolkit:@dev --update-with-dependencies

    if [ $? -eq 0 ]; then
        echo ""
        echo -e "${GREEN}✅ SUCCESS: Development Mode Activated${RESET}"
        echo -e "$SEPARATOR"
        echo -e "${YELLOW}📁 Local Path:${RESET} $LOCAL_PATH"
        echo -e "${YELLOW}🔗 Type:${RESET} Symlink (changes reflect immediately)"
        echo -e "$SEPARATOR"
        echo ""
    else
        echo ""
        echo -e "${RED}❌ Failed to switch to development mode${RESET}"
        echo -e "$SEPARATOR"
        echo ""
        exit 1
    fi
}

function switch_to_prod() {
    echo ""
    echo -e "${CYAN}📦 Switching to PRODUCTION mode...${RESET}"
    echo -e "$SEPARATOR"

    # Remove local repository
    composer config --unset repositories.local-toolkit

    # Update dependency
    composer require wp-rest-auth/auth-toolkit:dev-main --update-with-dependencies

    if [ $? -eq 0 ]; then
        echo ""
        echo -e "${GREEN}✅ SUCCESS: Production Mode Activated${RESET}"
        echo -e "$SEPARATOR"
        echo -e "${BLUE}🌐 Source:${RESET} GitHub Repository"
        echo -e "${BLUE}🏷️ Version:${RESET} dev-main"
        echo -e "${BLUE}📍 URL:${RESET} $GITHUB_URL"
        echo -e "$SEPARATOR"
        echo ""
    else
        echo ""
        echo -e "${RED}❌ Failed to switch to production mode${RESET}"
        echo -e "$SEPARATOR"
        echo ""
        exit 1
    fi
}

# Main script logic
case "$1" in
    check)
        check_mode
        ;;
    dev)
        switch_to_dev
        ;;
    prod)
        switch_to_prod
        ;;
    *)
        echo "Usage: $0 {check|dev|prod}"
        exit 1
        ;;
esac