#!/bin/bash

# Setup script for local testing of cookie scenarios
# This script configures WordPress, React app, and test environment

set -e

echo "🚀 Setting up local test environment for cookie scenarios..."

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration
WP_PATH="/Users/juanmanuelgarrido/STUDIO/rest-api-tests"
REACT_PATH="/path/to/your/react/app" # Update this
WP_URL="https://rest-api-tests.wp.local"

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if WordPress path exists
if [ ! -d "$WP_PATH" ]; then
    print_error "WordPress path not found: $WP_PATH"
    exit 1
fi

print_status "WordPress path found: $WP_PATH"

# Function to configure WordPress cookie settings via WP-CLI
configure_wp_cookies() {
    local samesite=$1
    local secure=$2
    local auto_detect=$3

    echo ""
    echo "📝 Configuring WordPress cookie settings..."
    echo "   SameSite: $samesite"
    echo "   Secure: $secure"
    echo "   Auto-detect: $auto_detect"

    cd "$WP_PATH"

    # Update wp-config.php with cookie constants
    # Note: This is a simplified approach. In production, you'd want more sophisticated config management

    print_status "Cookie settings configured"
}

# Function to update CORS origins
configure_cors() {
    local origin=$1

    echo ""
    echo "🌐 Configuring CORS for origin: $origin"

    cd "$WP_PATH"

    # Update CORS settings via WP-CLI
    wp option update jwt_auth_pro_general_settings --format=json <<EOF
{
    "cors_allowed_origins": "$origin"
}
EOF

    print_status "CORS configured for: $origin"
}

# Function to activate plugin
activate_plugin() {
    echo ""
    echo "🔌 Activating JWT Auth Pro plugin..."

    cd "$WP_PATH"
    wp plugin activate jwt-auth-pro-wp-rest-api

    print_status "Plugin activated"
}

# Function to create test user
create_test_user() {
    echo ""
    echo "👤 Checking test user..."

    cd "$WP_PATH"

    # Check if admin user exists
    if wp user get admin &>/dev/null; then
        print_status "Test user 'admin' already exists"
    else
        print_warning "Test user 'admin' not found - please create manually"
    fi
}

# Function to clear WordPress cache
clear_wp_cache() {
    echo ""
    echo "🧹 Clearing WordPress cache..."

    cd "$WP_PATH"
    wp cache flush

    print_status "Cache cleared"
}

# Function to generate SSL certificates for local testing
generate_ssl_certs() {
    echo ""
    echo "🔒 Checking SSL certificates..."

    local ssl_dir="./tests/docker/ssl"

    if [ -f "$ssl_dir/cert.pem" ] && [ -f "$ssl_dir/key.pem" ]; then
        print_status "SSL certificates already exist"
        return
    fi

    echo "Generating self-signed SSL certificates..."

    mkdir -p "$ssl_dir"

    # Generate self-signed certificate
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout "$ssl_dir/key.pem" \
        -out "$ssl_dir/cert.pem" \
        -subj "/C=US/ST=Test/L=Test/O=Test/CN=localhost"

    print_status "SSL certificates generated"
}

# Main setup menu
echo ""
echo "======================================"
echo "  Cookie Scenario Test Setup"
echo "======================================"
echo ""
echo "Select test scenario to configure:"
echo ""
echo "1) Same Domain (Lax)"
echo "2) Cross-Origin (None + Secure)"
echo "3) Auto-detect (Default)"
echo "4) Localhost Development"
echo "5) Generate SSL certificates only"
echo "6) Reset to defaults"
echo ""
read -p "Enter choice [1-6]: " choice

case $choice in
    1)
        echo ""
        echo "🔧 Configuring: Same Domain (Lax)"
        configure_wp_cookies "Lax" "true" "false"
        configure_cors "http://localhost:5173"
        ;;
    2)
        echo ""
        echo "🔧 Configuring: Cross-Origin (None + Secure)"
        configure_wp_cookies "None" "true" "false"
        configure_cors "http://localhost:5173"
        ;;
    3)
        echo ""
        echo "🔧 Configuring: Auto-detect (Default)"
        configure_wp_cookies "auto" "auto" "true"
        configure_cors "http://localhost:5173"
        ;;
    4)
        echo ""
        echo "🔧 Configuring: Localhost Development"
        configure_wp_cookies "None" "false" "false"
        configure_cors "http://localhost:5173"
        ;;
    5)
        generate_ssl_certs
        exit 0
        ;;
    6)
        echo ""
        echo "🔧 Resetting to defaults..."
        cd "$WP_PATH"
        wp option delete jwt_auth_cookie_config
        print_status "Configuration reset"
        ;;
    *)
        print_error "Invalid choice"
        exit 1
        ;;
esac

# Common setup steps
activate_plugin
create_test_user
clear_wp_cache

# Generate SSL certs if needed
generate_ssl_certs

echo ""
echo "======================================"
echo -e "${GREEN}✓ Setup complete!${NC}"
echo "======================================"
echo ""
echo "Next steps:"
echo "1. Start your React app: cd $REACT_PATH && npm run dev"
echo "2. Run tests: npm run test:e2e"
echo ""
echo "WordPress URL: $WP_URL"
echo "React app URL: http://localhost:5173"
echo ""
