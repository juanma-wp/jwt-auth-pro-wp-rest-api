<?php
/**
 * Minimal PHPStan Bootstrap - Memory Optimized
 *
 * This ultra-minimal bootstrap file is specifically designed for PHPStan static analysis
 * to reduce memory consumption. It only defines the absolute minimum constants and
 * functions needed for analysis without loading unnecessary WordPress stubs or
 * external dependencies.
 *
 * @package   JWTAuthProWPRestAPI
 * @author    WordPress Developer
 * @copyright 2025 WordPress Developer
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

// Only define essential constants that PHPStan actually needs
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

// JWT constants that are referenced in code
if ( ! defined( 'JWT_AUTH_PRO_SECRET' ) ) {
	define( 'JWT_AUTH_PRO_SECRET', 'phpstan-analysis-secret' );
}

if ( ! defined( 'JWT_AUTH_PRO_ACCESS_TTL' ) ) {
	define( 'JWT_AUTH_PRO_ACCESS_TTL', 3600 );
}

if ( ! defined( 'JWT_AUTH_PRO_REFRESH_TTL' ) ) {
	define( 'JWT_AUTH_PRO_REFRESH_TTL', 2592000 );
}

// WordPress version constant
if ( ! defined( 'JWT_AUTH_PRO_VERSION' ) ) {
	define( 'JWT_AUTH_PRO_VERSION', '1.0.0' );
}

echo "Minimal PHPStan bootstrap loaded (memory optimized)\n";
echo 'PHP version: ' . PHP_VERSION . "\n\n";
