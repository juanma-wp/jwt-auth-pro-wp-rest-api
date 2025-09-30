# Quick Start Guide: Local Composer Package Setup

This is a quick reference for working with the local Composer package structure. For detailed documentation, see [COMPOSER-SETUP.md](COMPOSER-SETUP.md).

## Initial Setup

### 1. Install Dependencies

```bash
# Install dependencies for wp-jwt-login
cd wp-jwt-login
composer install

# Install dependencies for wp-oauth-login
cd ../wp-oauth-login
composer install
```

This creates symlinks to `jwt-auth-core` in both plugins' `vendor/` directories.

### 2. Verify Setup

```bash
cd ..
./test-composer-setup.sh
```

You should see all tests passing ✅

## Daily Development Workflow

### Editing Shared Code (jwt-auth-core)

```bash
# Edit the shared TokenManager
vim jwt-auth-core/src/TokenManager.php

# Changes are immediately available in both plugins!
# No need to run composer update
```

### Testing Changes in Plugins

```bash
# Test in wp-jwt-login
cd wp-jwt-login
# Your changes from jwt-auth-core are already available via symlink

# Test in wp-oauth-login  
cd ../wp-oauth-login
# Changes are available here too
```

### Adding New Classes to jwt-auth-core

```php
// jwt-auth-core/src/NewClass.php
namespace MyOrg\JWTAuthCore;

class NewClass {
    // Your code here
}

// Use immediately in plugins (no composer update needed)
use MyOrg\JWTAuthCore\NewClass;
```

## Project Structure

```
├── jwt-auth-core/              # Shared JWT library
│   ├── composer.json
│   ├── src/TokenManager.php
│   └── README.md
│
├── wp-jwt-login/               # JWT login plugin
│   ├── composer.json
│   ├── wp-jwt-login.php
│   └── vendor/ -> jwt-auth-core (symlinked)
│
└── wp-oauth-login/             # OAuth login plugin
    ├── composer.json
    ├── wp-oauth-login.php
    └── vendor/ -> jwt-auth-core (symlinked)
```

## API Examples

### wp-jwt-login Plugin

**Authenticate:**
```bash
curl -X POST http://localhost:8888/wp-json/wp-jwt-login/v1/authenticate \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'
```

**Validate Token:**
```bash
curl -X POST http://localhost:8888/wp-json/wp-jwt-login/v1/validate \
  -H "Content-Type: application/json" \
  -d '{"token":"YOUR_TOKEN"}'
```

### wp-oauth-login Plugin

**Start OAuth Flow:**
```bash
curl http://localhost:8888/wp-json/wp-oauth-login/v1/authorize/google
```

**Refresh Token:**
```bash
curl -X POST http://localhost:8888/wp-json/wp-oauth-login/v1/refresh \
  -H "Content-Type: application/json" \
  -d '{"token":"YOUR_TOKEN"}'
```

## Building for Production

### For WordPress.org Release

```bash
cd wp-jwt-login

# Install production dependencies only
composer install --no-dev --optimize-autoloader

# Create distribution ZIP
zip -r wp-jwt-login.zip . \
  -x "*.git*" \
  -x "tests/*" \
  -x "*.md" \
  -x "composer.json" \
  -x "composer.lock"
```

**Important:** The `vendor/` directory MUST be included in the ZIP for WordPress.org!

## Future: Moving to Separate Repository

When ready to extract `jwt-auth-core`:

1. **Create new repository** for jwt-auth-core
2. **Tag a release** (e.g., v1.0.0)
3. **Update plugin composer.json**:
   ```json
   {
     "repositories": [
       {
         "type": "vcs",
         "url": "https://github.com/myorg/jwt-auth-core"
       }
     ],
     "require": {
       "myorg/jwt-auth-core": "^1.0"
     }
   }
   ```
4. **Run composer update** in both plugins

## Troubleshooting

### Autoloader not finding classes?
```bash
composer dump-autoload
```

### Symlinks not working?
```bash
# Remove and reinstall
rm -rf vendor composer.lock
composer install
```

### Changes not reflecting?
```bash
# Verify symlink exists
ls -la vendor/myorg/jwt-auth-core
# Should show: jwt-auth-core -> ../../../jwt-auth-core/
```

## Key Composer Commands

```bash
# Install dependencies
composer install

# Update specific package
composer update myorg/jwt-auth-core

# Regenerate autoloader
composer dump-autoload

# Install without dev dependencies
composer install --no-dev --optimize-autoloader
```

## Resources

- **Full Documentation:** [COMPOSER-SETUP.md](COMPOSER-SETUP.md)
- **Package README:** [jwt-auth-core/README.md](jwt-auth-core/README.md)
- **Plugin READMEs:** 
  - [wp-jwt-login/README.md](wp-jwt-login/README.md)
  - [wp-oauth-login/README.md](wp-oauth-login/README.md)
- **Structure Overview:** [STRUCTURE.txt](STRUCTURE.txt)
