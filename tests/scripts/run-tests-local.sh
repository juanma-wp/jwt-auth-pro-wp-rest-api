#!/bin/bash

# Automated test runner for local environment
# This script starts wp-env, test app and runs Playwright tests

set -e  # Exit on error

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}======================================"
echo "  JWT Cookie Scenario Tests (Local)"
echo -e "======================================${NC}"
echo ""

# Configuration
TEST_APP_PORT=5173
WORDPRESS_URL="http://localhost:8889"  # wp-env tests environment
WORDPRESS_API="$WORDPRESS_URL/wp-json/jwt/v1"

# Cleanup function
cleanup() {
    echo ""
    echo -e "${YELLOW}Cleaning up...${NC}"

    # Kill test app server
    if [ ! -z "$TEST_APP_PID" ]; then
        kill $TEST_APP_PID 2>/dev/null || true
        echo -e "${GREEN}✓${NC} Test app stopped"
    fi

    # Kill any http-server processes on port 5173
    lsof -ti:$TEST_APP_PORT | xargs kill -9 2>/dev/null || true
}

# Register cleanup on exit
trap cleanup EXIT INT TERM

# Step 1: Ensure wp-env is started
echo -e "${BLUE}1. Starting wp-env...${NC}"
if ! docker ps | grep -q "wordpress-develop-tests"; then
    echo "Starting wp-env..."
    npm run env:start
    echo "Waiting for WordPress to be ready..."
    sleep 10
fi

# Check WordPress is accessible
echo -e "${BLUE}2. Checking WordPress accessibility...${NC}"
max_attempts=30
attempt=0
until curl -s "$WORDPRESS_API/" > /dev/null 2>&1 || [ $attempt -eq $max_attempts ]; do
    attempt=$((attempt + 1))
    echo -n "."
    sleep 2
done
echo ""

if curl -s "$WORDPRESS_API/" > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} WordPress API accessible at $WORDPRESS_API"
else
    echo -e "${RED}✗${NC} WordPress API not accessible at $WORDPRESS_API"
    echo "Please check wp-env logs: npm run env:logs"
    exit 1
fi

# Step 3: Activate plugin
echo ""
echo -e "${BLUE}3. Activating plugin...${NC}"
npm run test:setup > /dev/null 2>&1
echo -e "${GREEN}✓${NC} Plugin activated"

# Step 4: Configure WordPress for testing via WP-CLI
echo ""
echo -e "${BLUE}4. Configuring WordPress for tests...${NC}"
wp-env run tests-cli wp option update jwt_auth_pro_general_settings --format=json '{"samesite":"auto","secure":"auto","path":"auto","domain":"auto","auto_detect":true,"allowed_origins":"http://localhost:5173"}' 2>/dev/null || true
echo -e "${GREEN}✓${NC} WordPress configured"

# Step 5: Start test app
echo ""
echo -e "${BLUE}5. Starting test app...${NC}"

# Kill any existing server on port 5173
lsof -ti:$TEST_APP_PORT | xargs kill -9 2>/dev/null || true
sleep 1

# Start test app server
cd tests/test-app
npx http-server . -p $TEST_APP_PORT -c-1 --cors > /dev/null 2>&1 &
TEST_APP_PID=$!
cd ../..

# Wait for server to be ready
echo -n "Waiting for test app to start"
max_attempts=10
attempt=0
while ! curl -s http://localhost:$TEST_APP_PORT/ > /dev/null; do
    attempt=$((attempt + 1))
    if [ $attempt -ge $max_attempts ]; then
        echo ""
        echo -e "${RED}✗${NC} Test app failed to start"
        exit 1
    fi
    echo -n "."
    sleep 1
done
echo ""
echo -e "${GREEN}✓${NC} Test app running at http://localhost:$TEST_APP_PORT"

# Step 6: Run Playwright tests
echo ""
echo -e "${BLUE}6. Running Playwright tests...${NC}"
echo ""

# Set environment variables
export PLAYWRIGHT_BASE_URL="http://localhost:$TEST_APP_PORT"
export PLAYWRIGHT_API_URL="$WORDPRESS_URL"

# Run tests
if npm run test:e2e -- --project=auto-detect; then
    echo ""
    echo -e "${GREEN}======================================"
    echo "  ✓ All tests passed!"
    echo -e "======================================${NC}"
    exit 0
else
    echo ""
    echo -e "${RED}======================================"
    echo "  ✗ Tests failed"
    echo -e "======================================${NC}"
    exit 1
fi
