# Local Composer Package Setup for Shared JWT Logic

This directory structure demonstrates how to set up a local Composer package for shared JWT authentication logic that can be used across multiple WordPress plugins.

## Directory Structure

```
.
├── jwt-auth-core/              # Shared JWT library package
│   ├── composer.json           # Package definition
│   ├── src/
│   │   └── TokenManager.php    # Shared JWT token logic
│   └── README.md               # Package documentation
├── wp-jwt-login/               # WordPress JWT login plugin
│   ├── composer.json           # Plugin dependencies
│   ├── wp-jwt-login.php        # Plugin main file
│   └── vendor/                 # Composer dependencies (auto-generated)
├── wp-oauth-login/             # WordPress OAuth login plugin
│   ├── composer.json           # Plugin dependencies
│   ├── wp-oauth-login.php      # Plugin main file
│   └── vendor/                 # Composer dependencies (auto-generated)
└── COMPOSER-SETUP.md           # This file
```

## 1. Local Composer Setup

### Step 1: Install Dependencies for wp-jwt-login

```bash
cd wp-jwt-login
composer install
```

This will:
- Create a symlink to `../jwt-auth-core` in the `vendor/myorg/jwt-auth-core` directory
- Set up the autoloader to load classes from the shared package

### Step 2: Install Dependencies for wp-oauth-login

```bash
cd ../wp-oauth-login
composer install
```

This will also create a symlink to the shared `jwt-auth-core` package.

### How It Works

Both plugins have a `repositories` section in their `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../jwt-auth-core"
    }
  ],
  "require": {
    "myorg/jwt-auth-core": "@dev"
  }
}
```

The `path` repository type tells Composer to:
1. Look for the package in the specified local directory
2. Create a symlink instead of copying files
3. Allow you to edit the shared package and see changes immediately

## 2. Iterative Development

### Making Changes to jwt-auth-core

When you modify files in `jwt-auth-core/src/`:

1. **Changes are immediately reflected** in both plugins because Composer uses symlinks
2. **No need to run `composer update`** for simple code changes
3. Just refresh your WordPress environment to see the changes

### Example Workflow

```bash
# 1. Edit the shared TokenManager class
vim jwt-auth-core/src/TokenManager.php

# 2. Test in wp-jwt-login plugin
cd wp-jwt-login
# Changes are already available due to symlinks

# 3. Test in wp-oauth-login plugin
cd ../wp-oauth-login
# Changes are already available here too
```

### Adding New Classes to jwt-auth-core

If you add new classes to the shared package:

1. Add the class file to `jwt-auth-core/src/`
2. The PSR-4 autoloader will automatically detect it (no `composer update` needed)
3. Import and use it in your plugins

Example:

```php
// jwt-auth-core/src/TokenValidator.php
namespace MyOrg\JWTAuthCore;

class TokenValidator {
    // New functionality
}

// Use in plugins
use MyOrg\JWTAuthCore\TokenValidator;
```

### Updating Package Metadata

If you update `jwt-auth-core/composer.json` (dependencies, version, etc.):

```bash
# Run in each plugin directory
cd wp-jwt-login
composer update myorg/jwt-auth-core

cd ../wp-oauth-login
composer update myorg/jwt-auth-core
```

## 3. Future Extraction to Separate Repository

When ready to move `jwt-auth-core` to its own repository:

### Step 1: Create a New Repository

```bash
# Create a new repository on GitHub/GitLab/etc.
# Example: https://github.com/myorg/jwt-auth-core
```

### Step 2: Move the Package

```bash
# Clone the new repository
git clone https://github.com/myorg/jwt-auth-core.git /tmp/jwt-auth-core

# Copy files from local package
cp -r jwt-auth-core/* /tmp/jwt-auth-core/

# Commit and push
cd /tmp/jwt-auth-core
git add .
git commit -m "Initial commit of JWT Auth Core library"
git push origin main
```

### Step 3: Tag a Release

```bash
cd /tmp/jwt-auth-core
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

### Step 4: Update Plugin composer.json Files

Update both `wp-jwt-login/composer.json` and `wp-oauth-login/composer.json`:

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

### Step 5: Update Dependencies

```bash
cd wp-jwt-login
composer update myorg/jwt-auth-core

cd ../wp-oauth-login
composer update myorg/jwt-auth-core
```

Now Composer will:
- Download the package from the VCS repository
- Use proper semantic versioning
- Cache the package like any other dependency

## 4. Bundling for WordPress.org

When preparing plugins for WordPress.org submission:

### Step 1: Install Production Dependencies

```bash
cd wp-jwt-login
composer install --no-dev --optimize-autoloader
```

This:
- Installs only production dependencies (no dev packages)
- Optimizes the autoloader for production
- Creates the `vendor/` directory with all dependencies

### Step 2: Include vendor/ in Your Build

**Important**: The `vendor/` directory **MUST** be included in your WordPress.org ZIP file.

Update your build script to include `vendor/`:

```bash
# Example build script
#!/bin/bash
cd wp-jwt-login

# Install production dependencies
composer install --no-dev --optimize-autoloader

# Create ZIP file including vendor/
zip -r wp-jwt-login.zip \
  wp-jwt-login.php \
  vendor/ \
  readme.txt \
  -x "*.git*" \
  -x "*node_modules*" \
  -x "*tests*"
```

### Step 3: .gitignore Considerations

For development:
- Add `vendor/` to `.gitignore` in your plugin repository
- This keeps your Git repository clean

For WordPress.org:
- Build scripts should install dependencies fresh
- Include the generated `vendor/` in the distribution ZIP

### Example .gitignore for Plugins

```gitignore
# wp-jwt-login/.gitignore
vendor/
composer.lock
node_modules/
*.log
.DS_Store
```

### Step 4: Testing the Bundle

Before submitting to WordPress.org:

```bash
# Extract and test the ZIP
unzip wp-jwt-login.zip -d /tmp/test-plugin
cd /tmp/test-plugin/wp-jwt-login

# Verify vendor/ exists and contains the shared package
ls -la vendor/myorg/jwt-auth-core/
```

## 5. Advanced Configurations

### Using Different Versions in Different Plugins

You can require different versions of the shared package:

```json
// wp-jwt-login/composer.json
{
  "require": {
    "myorg/jwt-auth-core": "^1.0"
  }
}

// wp-oauth-login/composer.json
{
  "require": {
    "myorg/jwt-auth-core": "^2.0"
  }
}
```

### Private Package Repositories

For private packages, use authentication:

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
  },
  "config": {
    "github-oauth": {
      "github.com": "your-github-token"
    }
  }
}
```

### Using Packagist

For public packages, submit to Packagist.org:

1. Register on https://packagist.org
2. Submit your package URL
3. Remove the `repositories` section from plugin composer.json
4. Composer will automatically find it on Packagist

## 6. Testing the Setup

### Quick Test

```bash
# Install both plugins
cd wp-jwt-login
composer install

cd ../wp-oauth-login
composer install

# Verify the shared package is linked
ls -la wp-jwt-login/vendor/myorg/jwt-auth-core
ls -la wp-oauth-login/vendor/myorg/jwt-auth-core

# Both should be symlinks pointing to ../../jwt-auth-core
```

### Verify Autoloading

Create a test file:

```php
<?php
// test-autoload.php in wp-jwt-login/
require_once __DIR__ . '/vendor/autoload.php';

use MyOrg\JWTAuthCore\TokenManager;

$manager = new TokenManager('test-secret');
$token = $manager->generate_token(['user_id' => 123]);
echo "Token: " . $token . "\n";

$payload = $manager->validate_token($token);
print_r($payload);
```

Run:
```bash
cd wp-jwt-login
php test-autoload.php
```

## Benefits of This Approach

1. **DRY (Don't Repeat Yourself)**: Share code across plugins
2. **Immediate Updates**: Changes in shared code reflect instantly
3. **Version Control**: Can use semantic versioning when extracted
4. **Easy Testing**: Test changes across all dependent plugins
5. **Professional Structure**: Follows Composer and PHP best practices
6. **WordPress.org Ready**: Easy to bundle for distribution

## Troubleshooting

### Symlinks Not Working

If symlinks don't work (e.g., on Windows):

```json
{
  "config": {
    "preferred-install": {
      "*": "source"
    }
  }
}
```

This tells Composer to copy files instead of symlinking.

### Autoloader Issues

If classes aren't loading:

```bash
# Regenerate the autoloader
composer dump-autoload
```

### Version Conflicts

If you see version conflicts:

```bash
# Clear Composer cache
composer clear-cache

# Remove vendor and reinstall
rm -rf vendor composer.lock
composer install
```

## References

- [Composer Path Repository Documentation](https://getcomposer.org/doc/05-repositories.md#path)
- [PSR-4 Autoloading Standard](https://www.php-fig.org/psr/psr-4/)
- [WordPress Plugin Best Practices](https://developer.wordpress.org/plugins/plugin-basics/best-practices/)
- [Semantic Versioning](https://semver.org/)
