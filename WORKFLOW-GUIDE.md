# Local Composer Package - Visual Workflow Guide

## 📁 Project Structure

```
jwt-auth-pro-wp-rest-api/
│
├── 📦 jwt-auth-core/                    # Shared JWT Library Package
│   ├── composer.json                    # Package: myorg/jwt-auth-core
│   ├── src/
│   │   └── TokenManager.php             # Shared JWT logic
│   └── README.md
│
├── 🔌 wp-jwt-login/                     # WordPress Plugin #1
│   ├── composer.json                    # Requires: myorg/jwt-auth-core
│   ├── wp-jwt-login.php                 # Plugin file
│   ├── vendor/                          # (gitignored)
│   │   └── myorg/jwt-auth-core → ../../jwt-auth-core/  # Symlink!
│   └── README.md
│
├── 🔌 wp-oauth-login/                   # WordPress Plugin #2
│   ├── composer.json                    # Requires: myorg/jwt-auth-core
│   ├── wp-oauth-login.php               # Plugin file
│   ├── vendor/                          # (gitignored)
│   │   └── myorg/jwt-auth-core → ../../jwt-auth-core/  # Symlink!
│   └── README.md
│
├── 📚 Documentation
│   ├── QUICK-START.md                   # ⭐ Start here!
│   ├── COMPOSER-SETUP.md                # Complete guide
│   ├── IMPLEMENTATION-SUMMARY.md        # Technical details
│   └── STRUCTURE.txt                    # Visual structure
│
└── 🧪 test-composer-setup.sh            # Automated tests
```

## 🔄 Development Workflow

### Initial Setup

```bash
# 1. Install wp-jwt-login dependencies
cd wp-jwt-login
composer install
# ✅ Creates symlink: vendor/myorg/jwt-auth-core → ../../jwt-auth-core/

# 2. Install wp-oauth-login dependencies  
cd ../wp-oauth-login
composer install
# ✅ Creates symlink: vendor/myorg/jwt-auth-core → ../../jwt-auth-core/

# 3. Verify everything works
cd ..
./test-composer-setup.sh
# ✅ All tests should pass
```

### Daily Development

```
┌─────────────────────────────────────────────────────────┐
│  Edit jwt-auth-core/src/TokenManager.php                │
│  (Make your changes to shared code)                     │
└─────────────────────────────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────────┐
        │  Changes are IMMEDIATELY available    │
        │  in both plugins via symlinks!        │
        │  No rebuild or update needed          │
        └───────────────────────────────────────┘
                            │
            ┌───────────────┴───────────────┐
            ▼                               ▼
    ┌──────────────────┐          ┌──────────────────┐
    │  wp-jwt-login    │          │  wp-oauth-login  │
    │  uses updated    │          │  uses updated    │
    │  TokenManager    │          │  TokenManager    │
    └──────────────────┘          └──────────────────┘
```

### Adding New Classes

```
┌─────────────────────────────────────────────────────────┐
│  Create: jwt-auth-core/src/NewClass.php                 │
│                                                          │
│  namespace MyOrg\JWTAuthCore;                          │
│  class NewClass { ... }                                 │
└─────────────────────────────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────────┐
        │  PSR-4 autoloader finds it            │
        │  automatically - no composer update!  │
        └───────────────────────────────────────┘
                            │
                            ▼
    ┌───────────────────────────────────────────────────┐
    │  Use in plugins:                                  │
    │  use MyOrg\JWTAuthCore\NewClass;                 │
    │  $obj = new NewClass();                          │
    └───────────────────────────────────────────────────┘
```

## 🚀 Production Build

### WordPress.org Distribution

```
┌─────────────────────────────────────────────────────────┐
│  cd wp-jwt-login                                         │
│  composer install --no-dev --optimize-autoloader        │
└─────────────────────────────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────────┐
        │  vendor/ directory is populated       │
        │  with production dependencies         │
        └───────────────────────────────────────┘
                            │
                            ▼
    ┌───────────────────────────────────────────────────┐
    │  zip -r wp-jwt-login.zip . \                      │
    │    -x "*.git*" "tests/*" "*.md"                   │
    │                                                   │
    │  ⚠️  vendor/ MUST be included in ZIP!            │
    └───────────────────────────────────────────────────┘
                            │
                            ▼
            ┌───────────────────────────────┐
            │  Upload to WordPress.org      │
            │  ✅ Ready for distribution    │
            └───────────────────────────────┘
```

## 🔀 Future: Extract to Separate Repository

```
Current State:                      Future State:
┌──────────────────┐               ┌──────────────────────────────┐
│  Local           │               │  GitHub Repository           │
│  jwt-auth-core/  │    ───────>   │  myorg/jwt-auth-core        │
│  (directory)     │               │  (tagged v1.0.0)            │
└──────────────────┘               └──────────────────────────────┘
         │                                      │
         │                                      │
         ▼                                      ▼
┌────────────────────┐             ┌─────────────────────────────┐
│  composer.json     │             │  composer.json              │
│  "repositories": [ │             │  "repositories": [          │
│    {               │             │    {                        │
│      "type": "path"│   ───────>  │      "type": "vcs",        │
│      "url": "../.."│             │      "url": "https://..."   │
│    }               │             │    }                        │
│  ]                 │             │  ],                         │
│  "require": {      │             │  "require": {               │
│    "..": "@dev"    │             │    "..": "^1.0"            │
│  }                 │             │  }                          │
└────────────────────┘             └─────────────────────────────┘
```

## 🧪 Testing Flow

```
┌─────────────────────────────────────────────────────────┐
│  ./test-composer-setup.sh                                │
└─────────────────────────────────────────────────────────┘
                            │
            ┌───────────────┴───────────────┐
            ▼                               ▼
    Test Structure              Test Dependencies
    ┌──────────────┐           ┌──────────────────┐
    │ ✅ Folders   │           │ ✅ Symlinks      │
    │ ✅ Files     │           │ ✅ Autoloader    │
    └──────────────┘           └──────────────────┘
                            │
                            ▼
                Test Functionality
            ┌───────────────────────────────┐
            │ ✅ Token Generation           │
            │ ✅ Token Validation           │
            │ ✅ Token Refresh              │
            │ ✅ Both plugins work          │
            └───────────────────────────────┘
```

## 📊 Package Dependencies

```
wp-jwt-login                        wp-oauth-login
     │                                    │
     └────────┬──────────────────────────┘
              │
              ▼
       jwt-auth-core
       (TokenManager)
              │
              ├── generate_token()
              ├── validate_token()
              └── refresh_token()
```

## 🔑 Key Concepts

### Symlinks Enable Iterative Development

```
Traditional Approach:           This Approach:
┌──────────────────┐           ┌──────────────────┐
│ Edit shared code │           │ Edit shared code │
│        │         │           │        │         │
│        ▼         │           │        ▼         │
│ composer update  │           │ Changes visible  │
│        │         │           │  immediately!    │
│        ▼         │           │        │         │
│ Test in plugins  │           │        ▼         │
│        │         │           │ Test in plugins  │
│        ▼         │           └──────────────────┘
│ Repeat...        │           
└──────────────────┘           No update needed!
```

### PSR-4 Autoloading

```
File Path:                    Namespace + Class:
jwt-auth-core/                MyOrg\JWTAuthCore\
└── src/                      └── (from PSR-4 mapping)
    └── TokenManager.php          └── TokenManager

Autoloader automatically resolves:
use MyOrg\JWTAuthCore\TokenManager;
     └─────┬─────┘ └────┬─────┘
      Namespace      Class
           │            │
           └────────────┴──> src/TokenManager.php
```

## 📝 Quick Commands Reference

```bash
# Setup
cd wp-jwt-login && composer install
cd ../wp-oauth-login && composer install

# Test
./test-composer-setup.sh

# Update shared package
cd jwt-auth-core/src && vim TokenManager.php
# Changes available immediately in both plugins

# Production build
cd wp-jwt-login
composer install --no-dev --optimize-autoloader
zip -r ../wp-jwt-login.zip . -x "*.git*" "tests/*" "*.md"
```

## 📚 Documentation Quick Links

1. **New Users** → Start with `QUICK-START.md`
2. **Detailed Guide** → Read `COMPOSER-SETUP.md`
3. **Technical Info** → See `IMPLEMENTATION-SUMMARY.md`
4. **Visual Structure** → View `STRUCTURE.txt`

## ✅ Success Criteria

- [x] Symlinks working (iterative development)
- [x] PSR-4 autoloading functional
- [x] Both plugins use shared code
- [x] All tests passing
- [x] Production build process documented
- [x] Future migration path clear
- [x] WordPress.org ready

---

**🎯 Result:** Professional, production-ready local Composer package setup!
