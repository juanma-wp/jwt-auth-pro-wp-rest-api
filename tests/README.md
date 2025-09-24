# JWT Plugin Testing

This directory contains the test suite for the WP REST Auth JWT plugin.

## Prerequisites

- Node.js 16+ and npm 8+
- PHP 7.4+ with Composer
- Docker and Docker Compose (for wp-env)

## Setup

1. **Install dependencies:**
   ```bash
   npm install
   composer install
   ```

2. **Start the test environment:**
   ```bash
   npm run env:start
   # or
   composer run env:start
   ```

## Running Tests

### Using wp-env (Recommended)
```bash
# All tests
composer test
# or
npm run test

# Unit tests only
composer test-unit

# Integration tests only
composer test-integration

# Local tests (without wp-env)
composer test-local
```

### Manual phpunit
```bash
# Make sure wp-env is running first
npm run env:start

# Run tests
./vendor/bin/phpunit --configuration phpunit.xml
```

## Test Structure

```
tests/
├── bootstrap.php              # Standard PHPUnit bootstrap
├── bootstrap-wp-env.php       # wp-env specific bootstrap
├── helpers/
│   └── TestCase.php          # Base test case with utilities
├── unit/                     # Unit tests
│   ├── HelpersTest.php      # Helper functions tests
│   ├── JWTAuthTest.php      # JWT authentication tests
│   └── MainPluginTest.php   # Main plugin tests
├── integration/             # Integration tests
│   └── RestAPIIntegrationTest.php
└── README.md               # This file
```

## Test Environment

- **WordPress**: Latest stable version
- **PHP**: 8.1 (configurable in .wp-env.json)
- **Database**: MySQL (via Docker)
- **Ports**:
  - Development: 8888
  - Tests: 8889

## Configuration

The test environment is configured via `.wp-env.json`:

```json
{
  "core": "WordPress/WordPress#6.4",
  "phpVersion": "8.1",
  "plugins": ["."],
  "config": {
    "WP_DEBUG": true,
    "WP_JWT_AUTH_SECRET": "test-secret"
  },
  "port": 8888,
  "testsPort": 8889
}
```

## Environment Management

```bash
# Start environment
npm run env:start

# Stop environment
npm run env:stop

# Restart environment
npm run env:restart

# Clean environment (reset database)
npm run env:clean

# Destroy environment (remove containers)
npm run env:destroy
```

## Writing Tests

### Unit Tests
- Test individual functions and classes
- Mock WordPress functions when needed
- Extend `WPRestAuthJWT\Tests\Helpers\TestCase`

### Integration Tests
- Test full WordPress integration
- Use real WordPress database
- Extend `WP_UnitTestCase`

### Example Test
```php
<?php
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testExample(): void
    {
        $this->assertTrue(true);
    }
}
```

## Debugging

1. **Enable debug mode:**
   ```bash
   wp-env run tests-wordpress wp config set WP_DEBUG true
   ```

2. **View logs:**
   ```bash
   wp-env logs tests
   ```

3. **Access test site:**
   - Development: http://localhost:8888
   - Tests: http://localhost:8889

## Continuous Integration

Tests can be run in CI environments:

```yaml
# GitHub Actions example
- name: Setup wp-env
  run: |
    npm install -g @wordpress/env
    npm install
    npm run env:start

- name: Run tests
  run: composer test
```