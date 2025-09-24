<?php

/**
 * PHPUnit bootstrap file for wp-env testing environment
 */

// Define testing environment constants
if (!defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
    define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills');
}

// Load Composer autoloader
$composer_autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($composer_autoloader)) {
    require_once $composer_autoloader;
} else {
    echo "Warning: Composer autoloader not found. Please run 'composer install'.\n";
}

// WordPress test environment paths for wp-env
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/wordpress-phpunit/wp-tests';
}

// WordPress core directory for wp-env
$wp_core_dir = getenv('WP_CORE_DIR');
if (!$wp_core_dir) {
    $wp_core_dir = '/var/www/html';
}

// Give access to tests_add_filter() function
if (file_exists($_tests_dir . '/includes/functions.php')) {
    require_once $_tests_dir . '/includes/functions.php';
}

// Mock additional WordPress functions before loading plugin
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file)
    {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file)
    {
        return 'https://example.com/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('add_action')) {
    function add_action($hook_name, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        // Mock for testing
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook_name, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        // Mock for testing
        return true;
    }
}

if (!function_exists('is_admin')) {
    function is_admin()
    {
        return false;
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array())
    {
        exit($message);
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $function)
    {
        // Mock for testing
        return true;
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $function)
    {
        // Mock for testing
        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook_name, $value)
    {
        return $value;
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter($hook_name, $function_to_remove, $priority = 10)
    {
        return true;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null)
    {
        return true;
    }
}

// Mock WordPress classes for testing
if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private $code;
        private $message;
        private $data;

        public function __construct($code = '', $message = '', $data = '')
        {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_code()
        {
            return $this->code;
        }

        public function get_error_message($code = '')
        {
            return $this->message;
        }

        public function get_error_data($code = '')
        {
            return $this->data;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        private $data;
        private $status;
        private $headers;

        public function __construct($data = null, $status = 200, $headers = array())
        {
            $this->data = $data;
            $this->status = $status;
            $this->headers = $headers;
        }

        public function get_data()
        {
            return $this->data;
        }

        public function get_status()
        {
            return $this->status;
        }

        public function get_headers()
        {
            return $this->headers;
        }

        public function set_status($code)
        {
            $this->status = $code;
        }

        public function header($key, $value, $replace = true)
        {
            $this->headers[$key] = $value;
        }
    }
}

// Define global $wpdb mock
global $wpdb;
if (!isset($wpdb)) {
    $wpdb = new class {
        public $prefix = 'wp_';

        public function query($sql) {
            return 1; // Mock successful query
        }

        public function get_results($sql, $output = OBJECT) {
            return []; // Mock empty results
        }

        public function prepare($query, ...$args) {
            return $query; // Mock prepared statement
        }
    };
}

/**
 * Manually load the plugin being tested
 */
function _manually_load_jwt_plugin()
{
    // Define test constants for JWT
    if (!defined('WP_JWT_AUTH_SECRET')) {
        define('WP_JWT_AUTH_SECRET', 'test-secret-key-for-testing-purposes-only-never-use-in-production-environment-this-should-be-long-and-random');
    }

    if (!defined('WP_JWT_ACCESS_TTL')) {
        define('WP_JWT_ACCESS_TTL', 3600);
    }

    if (!defined('WP_JWT_REFRESH_TTL')) {
        define('WP_JWT_REFRESH_TTL', 86400);
    }

    // Load the plugin - avoid class redeclaration errors
    if (!defined('WP_REST_AUTH_JWT_LOADED')) {
        require dirname(__DIR__) . '/wp-rest-auth-jwt.php';
        define('WP_REST_AUTH_JWT_LOADED', true);
    }
}

if (function_exists('tests_add_filter')) {
    tests_add_filter('muplugins_loaded', '_manually_load_jwt_plugin');
}

/**
 * Set up WordPress test environment
 */
if (file_exists($_tests_dir . '/includes/bootstrap.php')) {
    require $_tests_dir . '/includes/bootstrap.php';
} else {
    // Fallback bootstrap for cases where wp-env is not fully set up
    echo "Warning: WordPress test environment not found. Some tests may not work correctly.\n";

    // Define minimal WordPress constants
    if (!defined('ABSPATH')) {
        define('ABSPATH', $wp_core_dir . '/');
    }

    if (!defined('WP_DEBUG')) {
        define('WP_DEBUG', true);
    }

    if (!defined('WP_DEBUG_LOG')) {
        define('WP_DEBUG_LOG', true);
    }

    if (!defined('ARRAY_A')) {
        define('ARRAY_A', 'ARRAY_A');
    }

    if (!defined('OBJECT')) {
        define('OBJECT', 'OBJECT');
    }

    // Load our plugin manually
    _manually_load_jwt_plugin();
}

// Load test helpers
require_once __DIR__ . '/helpers/TestCase.php';

// Mock additional WordPress functions if needed for unit tests
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action)
    {
        return 'test-nonce-' . md5($action . wp_salt());
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action)
    {
        return $nonce === wp_create_nonce($action);
    }
}

if (!function_exists('wp_salt')) {
    function wp_salt($scheme = 'auth')
    {
        return 'test-salt-' . $scheme;
    }
}

// Set up REST API testing environment
if (function_exists('rest_get_server')) {
    global $wp_rest_server;
    $wp_rest_server = rest_get_server();
}

// Ensure predictable general settings for tests (CORS)
if (function_exists('update_option')) {
    update_option('wp_rest_auth_jwt_general_settings', [
        'enable_debug_logging' => true,
        'cors_allowed_origins' => "https://example.com\nhttps://app.example.com"
    ]);
}

// Force a predictable avatar URL for unit tests
if (function_exists('add_filter')) {
    add_filter('get_avatar_url', function ($url) {
        return 'https://example.com/avatar.jpg';
    }, 10, 1);
}

echo "WP REST Auth JWT test environment loaded successfully!\n";
echo "WordPress version: " . (defined('WP_VERSION') ? WP_VERSION : 'Unknown') . "\n";
echo "PHP version: " . PHP_VERSION . "\n";
echo "Test directory: " . $_tests_dir . "\n";
echo "WordPress directory: " . $wp_core_dir . "\n\n";
