# Running E2E Cookie Tests

## Prerequisites

Since this plugin focuses on backend JWT authentication, the E2E tests are designed to test against **any React app** that consumes the WordPress REST API with JWT authentication.

## Options for Running Tests

### Option 1: Use an Existing React App (Recommended for now)

If you already have a React app that uses this plugin:

1. **Start your React app** (in a separate terminal):
   ```bash
   cd /path/to/your/react/app
   npm run dev
   ```

2. **Configure the test to use your app**:
   ```bash
   export PLAYWRIGHT_BASE_URL=http://localhost:5173  # Your React app URL
   export PLAYWRIGHT_API_URL=https://rest-api-tests.wp.local  # Your WordPress URL
   ```

3. **Run tests**:
   ```bash
   npm run test:e2e -- --grep "Auto Development"
   ```

### Option 2: Create a Minimal React Test App

Create a simple React app for testing:

```bash
# In a separate directory
npx create-react-app jwt-test-app
cd jwt-test-app

# Install axios for API calls
npm install axios

# Start the app
npm start
```

Then create a simple login component that uses the JWT endpoints.

### Option 3: Use Docker (Full Automation)

The Docker setup includes everything:

```bash
# Build and run all containers (WordPress + React + Tests)
npm run docker:test

# This will:
# 1. Start WordPress instances
# 2. Start React test apps
# 3. Run Playwright tests
# 4. Generate reports
```

### Option 4: Mock React App (Coming Soon)

We can add a minimal Express server that serves a test HTML page with login/refresh functionality.

## Current Test Status

The tests are **ready to run** but require:
- ✅ WordPress instance (you have: `https://rest-api-tests.wp.local`)
- ✅ Plugin configured (you just configured: `auto-detect`)
- ❌ React app with login UI (you need to provide this)

## Quick Fix: Skip Web Server Check

The Playwright config has been updated to not require a dev server. Tests will run against whatever URL you specify in `PLAYWRIGHT_BASE_URL`.

## Recommended Next Step

Since you're testing the **plugin behavior** (cookies, CORS, HTTPOnly), you could:

1. **Use curl/Postman tests** instead (faster for backend validation)
2. **Create a minimal HTML test page** (we can add this)
3. **Use your existing React app** if you have one
4. **Use Docker** for full automation

Would you like me to create a minimal test HTML page that can test the JWT flows without needing a full React app?
