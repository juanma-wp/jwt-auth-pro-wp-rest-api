# Changelog

All notable changes to JWT Auth Pro WP REST API will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-10-03

### Fixed
- **Code Quality Improvements**: Complete PHPCS linting compliance
  - Fixed all inline comment formatting across codebase (added proper punctuation)
  - Standardized comment style for better maintainability
  - Removed obsolete test files (`test-cookie-settings.php`, `test-cookie-save.php`)
- **Admin Settings Enhancements**:
  - Fixed inline script/style enqueuing to follow WordPress standards
  - Converted inline scripts to use `wp_enqueue_script()`, `wp_enqueue_style()`, and `wp_add_inline_script()`
  - Fixed nonce verification handling for tab navigation
  - Replaced short ternary operators with explicit ternary expressions for better compatibility
- **Cookie Configuration**:
  - Enhanced environment detection to work with PHP 7.4 (replaced `str_ends_with()` and `str_contains()` with compatible alternatives)
  - Improved cookie path detection for proper cleanup across environments
  - Fixed `strpos()` usage for better PHP compatibility

### Changed
- **Documentation**: Updated inline code documentation for clarity
- **Code Standards**: All code now passes PHPCS WordPress-Extra ruleset
- **Compatibility**: Improved PHP 7.4 compatibility throughout the codebase

## [1.0.0] - 2025-09-29

### Added
- **OpenAPI Specification**: Complete API documentation with Swagger UI integration
  - Interactive API documentation tab in admin settings
  - OpenAPI 3.0 spec endpoint at `/wp-json/jwt/v1/openapi`
  - Full documentation of all authentication endpoints
- **Environment-Aware Cookie Configuration**: Automatic security settings based on environment
  - `JWT_Cookie_Config` class for intelligent cookie management
  - Auto-detection of development, staging, and production environments
  - Configurable SameSite, Secure, Path, and Domain attributes
  - Admin UI for cookie configuration with real-time preview
  - Support for cross-domain and same-domain scenarios
- **Comprehensive Documentation**:
  - Cookie configuration guide with troubleshooting scenarios
  - RFC 7009 (Token Revocation) compliance documentation
  - RFC 9700 (OAuth 2.0 Security Best Practices) compliance documentation
  - React + WordPress JWT authentication flow diagram (Excalidraw)
- **Enhanced Security Features**:
  - HTTPOnly cookies for refresh tokens (XSS protection)
  - Automatic token rotation on refresh
  - IP address and user agent tracking
  - Configurable token expiration times
- **CI/CD Improvements**:
  - PHPStan static analysis workflow with memory optimization
  - Unit and integration test workflows
  - PHPCS linting workflow
  - Comprehensive test coverage badges

### Changed
- **Major Refactoring**: Renamed from "WP REST Auth JWT" to "JWT Auth Pro"
  - Updated all configuration constants (e.g., `JWT_AUTH_PRO_SECRET`, `JWT_AUTH_PRO_ACCESS_TTL`)
  - Improved naming consistency across codebase
  - Enhanced plugin description to highlight refresh token security
- **Dependency Integration**:
  - Integrated `wp-rest-auth/auth-toolkit` package for enhanced JWT handling
  - Refactored JWT encoding/decoding to use toolkit's `Encoder` class
  - Improved CORS handling with toolkit's `Cors` class
- **Code Quality**:
  - Fixed all PHPCS linting errors across codebase
  - Standardized inline comment formatting
  - Removed obsolete test files
  - Enhanced inline documentation for better maintainability
- **Admin Interface**:
  - Reorganized settings tabs (JWT Settings, General Settings, Cookie Settings, API Docs, Help)
  - Improved UI with real-time configuration preview
  - Added environment detection display
  - Enhanced help documentation with usage examples

### Fixed
- CORS origin validation in cross-domain requests
- Cookie path detection for proper cleanup
- Memory optimization in PHPStan analysis
- Inline script/style enqueuing to follow WordPress standards

### Security
- Implemented secure HTTPOnly cookie storage for refresh tokens
- Short-lived access tokens (default: 1 hour)
- Long-lived refresh tokens (default: 30 days) with rotation
- Protection against XSS attacks via HTTPOnly cookies
- Protection against MITM attacks via Secure flag
- CSRF protection via SameSite cookie attribute
- Token revocation capabilities

### Documentation
- Added comprehensive README.md with features and installation guide
- Created detailed cookie configuration guide with troubleshooting
- Added RFC compliance documentation
- Included visual authentication flow diagram
- Enhanced inline code documentation

### Developer Experience
- Added build script for WordPress.org deployment (`build-plugin.sh`)
- Improved PHPUnit test coverage
- Enhanced CI workflows for better code quality
- Added .gitignore entries for build artifacts

## [Unreleased]

### Planned Features
- OAuth2 authorization flow support
- Scoped permissions system
- Third-party app authorization
- API proxy for enhanced security
- Multi-site support
- REST API rate limiting

---

## Version History

### Pre-1.0.0 Development
- Initial JWT authentication implementation
- Database schema for refresh tokens
- Basic REST API endpoints
- WordPress plugin architecture setup
- Unit and integration testing framework

---

## Upgrade Notes

### Upgrading to 1.0.0

**Breaking Changes:**
- Configuration constants renamed:
  - `JWT_SECRET` → `JWT_AUTH_PRO_SECRET`
  - `JWT_ACCESS_TTL` → `JWT_AUTH_PRO_ACCESS_TTL`
  - `JWT_REFRESH_TTL` → `JWT_AUTH_PRO_REFRESH_TTL`

**Migration Steps:**
1. Update `wp-config.php` with new constant names
2. Clear cookie configuration cache (handled automatically)
3. Review cookie settings in admin panel
4. Test authentication flow in your environment

**New Features:**
- Configure cookie settings via admin panel
- View API documentation via Swagger UI
- Environment-aware security settings

---

## Links
- [GitHub Repository](https://github.com/juanma-wp/jwt-auth-pro-wp-rest-api)
- [Author Website](https://juanma.codes)
- [WordPress Plugin Directory](https://wordpress.org/plugins/jwt-auth-pro-wp-rest-api/) _(pending)_

---

## Support

For issues, questions, or contributions:
- Open an issue on [GitHub](https://github.com/juanma-wp/jwt-auth-pro-wp-rest-api/issues)
- Review documentation in the `/DOCS` directory
- Check the Help tab in plugin settings
