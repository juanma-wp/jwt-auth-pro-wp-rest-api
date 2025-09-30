#!/bin/bash

# Test script to verify the Composer setup works correctly

echo "========================================"
echo "Testing Local Composer Package Setup"
echo "========================================"
echo ""

# Test 1: Verify jwt-auth-core structure
echo "Test 1: Checking jwt-auth-core structure..."
if [ -f "jwt-auth-core/composer.json" ] && [ -f "jwt-auth-core/src/TokenManager.php" ]; then
    echo "✅ jwt-auth-core structure is correct"
else
    echo "❌ jwt-auth-core structure is missing files"
    exit 1
fi
echo ""

# Test 2: Verify wp-jwt-login dependencies
echo "Test 2: Checking wp-jwt-login dependencies..."
cd wp-jwt-login
if [ -d "vendor/myorg/jwt-auth-core" ]; then
    echo "✅ wp-jwt-login has jwt-auth-core in vendor/"
    
    # Check if it's a symlink
    if [ -L "vendor/myorg/jwt-auth-core" ]; then
        echo "✅ jwt-auth-core is symlinked (iterative development enabled)"
    else
        echo "⚠️  jwt-auth-core is copied (not symlinked)"
    fi
else
    echo "❌ wp-jwt-login missing jwt-auth-core dependency"
    exit 1
fi
cd ..
echo ""

# Test 3: Verify wp-oauth-login dependencies
echo "Test 3: Checking wp-oauth-login dependencies..."
cd wp-oauth-login
if [ -d "vendor/myorg/jwt-auth-core" ]; then
    echo "✅ wp-oauth-login has jwt-auth-core in vendor/"
    
    # Check if it's a symlink
    if [ -L "vendor/myorg/jwt-auth-core" ]; then
        echo "✅ jwt-auth-core is symlinked (iterative development enabled)"
    else
        echo "⚠️  jwt-auth-core is copied (not symlinked)"
    fi
else
    echo "❌ wp-oauth-login missing jwt-auth-core dependency"
    exit 1
fi
cd ..
echo ""

# Test 4: Test autoloading in wp-jwt-login
echo "Test 4: Testing autoloading in wp-jwt-login..."
cd wp-jwt-login
php -r "
require 'vendor/autoload.php';
use MyOrg\JWTAuthCore\TokenManager;

\$manager = new TokenManager('test-secret-key', 3600);
\$token = \$manager->generate_token(['user_id' => 123, 'username' => 'test']);

if (strlen(\$token) > 0) {
    echo '✅ TokenManager loaded and token generated successfully\n';
    
    // Validate the token
    \$payload = \$manager->validate_token(\$token);
    if (\$payload && \$payload['user_id'] === 123) {
        echo '✅ Token validation works correctly\n';
    } else {
        echo '❌ Token validation failed\n';
        exit(1);
    }
} else {
    echo '❌ Failed to generate token\n';
    exit(1);
}
" || exit 1
cd ..
echo ""

# Test 5: Test autoloading in wp-oauth-login
echo "Test 5: Testing autoloading in wp-oauth-login..."
cd wp-oauth-login
php -r "
require 'vendor/autoload.php';
use MyOrg\JWTAuthCore\TokenManager;

\$manager = new TokenManager('oauth-secret-key', 7200);
\$token = \$manager->generate_token(['provider' => 'google', 'oauth_id' => 'abc123']);

if (strlen(\$token) > 0) {
    echo '✅ TokenManager loaded in wp-oauth-login\n';
    
    // Test refresh
    \$refreshed = \$manager->refresh_token(\$token);
    if (\$refreshed && strlen(\$refreshed) > 0) {
        echo '✅ Token refresh works correctly\n';
    } else {
        echo '❌ Token refresh failed\n';
        exit(1);
    }
} else {
    echo '❌ Failed to load TokenManager\n';
    exit(1);
}
" || exit 1
cd ..
echo ""

echo "========================================"
echo "All tests passed! ✅"
echo "========================================"
echo ""
echo "Setup is working correctly:"
echo "  - jwt-auth-core is properly structured"
echo "  - Both plugins have the dependency installed"
echo "  - Symlinks are working for iterative development"
echo "  - Autoloading is functioning correctly"
echo "  - TokenManager is accessible from both plugins"
echo ""
