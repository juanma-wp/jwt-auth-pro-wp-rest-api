<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for JWT Authentication class
 */
class JWTAuthTest extends TestCase
{
    private $auth_jwt;

    protected function setUp(): void
    {
        parent::setUp();

        // Load the JWT auth class
        if (!class_exists('Auth_JWT')) {
            require_once dirname(__DIR__, 2) . '/includes/class-auth-jwt.php';
        }

        // Load helpers
        if (!function_exists('wp_auth_jwt_generate_token')) {
            require_once dirname(__DIR__, 2) . '/includes/helpers.php';
        }

        // Define constants for testing
        if (!defined('WP_JWT_AUTH_SECRET')) {
            define('WP_JWT_AUTH_SECRET', 'test-secret-key-for-testing-only-jwt');
        }
        if (!defined('WP_JWT_ACCESS_TTL')) {
            define('WP_JWT_ACCESS_TTL', 3600);
        }
        if (!defined('WP_JWT_REFRESH_TTL')) {
            define('WP_JWT_REFRESH_TTL', 86400);
        }

        $this->auth_jwt = new Auth_JWT();
    }

    public function testJWTAuthClassExists(): void
    {
        $this->assertTrue(class_exists('Auth_JWT'));
        $this->assertInstanceOf('Auth_JWT', $this->auth_jwt);
    }

    public function testRestRoutesRegistration(): void
    {
        // Test that JWT routes registration method exists
        $this->assertTrue(method_exists($this->auth_jwt, 'register_routes'));
    }

    public function testTokenIssuanceEndpoint(): void
    {
        $this->assertTrue(method_exists($this->auth_jwt, 'issue_token'));

        // Create a mock WP_REST_Request
        $request = $this->createMockRequest([
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        // Test that the method exists and can be called
        $this->assertTrue(method_exists($this->auth_jwt, 'issue_token'));
    }

    public function testTokenRefreshEndpoint(): void
    {
        $this->assertTrue(method_exists($this->auth_jwt, 'refresh_access_token'));
    }

    public function testLogoutEndpoint(): void
    {
        $this->assertTrue(method_exists($this->auth_jwt, 'logout'));
    }

    public function testWhoamiEndpoint(): void
    {
        $this->assertTrue(method_exists($this->auth_jwt, 'whoami'));
    }

    public function testBearerTokenAuthentication(): void
    {
        $this->assertTrue(method_exists($this->auth_jwt, 'authenticate_bearer'));

        // Test with invalid token
        $result = $this->auth_jwt->authenticate_bearer('invalid-token');
        $this->assertInstanceOf('WP_Error', $result);
    }

    public function testRefreshTokenStorage(): void
    {
        // Test refresh token storage methods exist
        $this->assertTrue(method_exists($this->auth_jwt, 'get_user_refresh_tokens'));
        $this->assertTrue(method_exists($this->auth_jwt, 'revoke_user_token'));
        $this->assertTrue(method_exists($this->auth_jwt, 'clean_expired_tokens'));
    }

    public function testTokenCleanupFunctionality(): void
    {
        // Test expired token cleanup
        $this->auth_jwt->clean_expired_tokens();
        $this->assertTrue(true); // Should not throw errors
    }

    public function testUserTokenManagement(): void
    {
        $user_id = 123;

        // Test getting user tokens
        $tokens = $this->auth_jwt->get_user_refresh_tokens($user_id);
        $this->assertIsArray($tokens);

        // Test revoking a token (should handle non-existent token gracefully)
        $result = $this->auth_jwt->revoke_user_token($user_id, 999);
        $this->assertIsBool($result);
    }

    public function testCORSSupport(): void
    {
        $this->assertTrue(method_exists($this->auth_jwt, 'add_cors_support'));

        // Test CORS method exists and can be called
        $this->auth_jwt->add_cors_support();
        $this->assertTrue(true); // Should not throw errors
    }

    public function testJWTConstants(): void
    {
        // Test JWT constants are available
        $this->assertTrue(defined('WP_JWT_AUTH_SECRET'));
        $this->assertTrue(defined('WP_JWT_ACCESS_TTL'));
        $this->assertTrue(defined('WP_JWT_REFRESH_TTL'));

        // Test values are reasonable
        $this->assertGreaterThan(0, WP_JWT_ACCESS_TTL);
        $this->assertGreaterThan(0, WP_JWT_REFRESH_TTL);
        $this->assertNotEmpty(WP_JWT_AUTH_SECRET);
    }

    public function testClassConstants(): void
    {
        // Test class constants
        $this->assertTrue(defined('Auth_JWT::REFRESH_COOKIE_NAME'));
        $this->assertEquals('wp_jwt_refresh_token', Auth_JWT::REFRESH_COOKIE_NAME);

        $this->assertTrue(defined('Auth_JWT::ISSUER'));
        $this->assertEquals('wp-rest-auth-jwt', Auth_JWT::ISSUER);
    }

    public function testJWTHelperFunctionsAvailable(): void
    {
        // Test that JWT helper functions are available
        $this->assertTrue(function_exists('wp_auth_jwt_encode'));
        $this->assertTrue(function_exists('wp_auth_jwt_decode'));
        $this->assertTrue(function_exists('wp_auth_jwt_generate_token'));
        $this->assertTrue(function_exists('wp_auth_jwt_hash_token'));
    }

    public function testJWTWorkflowIntegration(): void
    {
        // Test a basic JWT workflow using helper functions
        $secret = WP_JWT_AUTH_SECRET;
        $claims = [
            'iss' => Auth_JWT::ISSUER,
            'aud' => 'test-audience',
            'iat' => time(),
            'exp' => time() + 3600,
            'sub' => 123,
            'jti' => wp_auth_jwt_generate_token(32)
        ];

        $token = wp_auth_jwt_encode($claims, $secret);
        $this->assertNotEmpty($token);

        $decoded = wp_auth_jwt_decode($token, $secret);
        $this->assertIsArray($decoded);
        $this->assertEquals(123, $decoded['sub']);
        $this->assertEquals(Auth_JWT::ISSUER, $decoded['iss']);
    }

    public function testTokenValidation(): void
    {
        // Test valid JWT token structure
        $token = $this->createValidJWTToken();
        $parts = explode('.', $token);

        $this->assertCount(3, $parts, 'JWT should have exactly 3 parts');

        // Verify header
        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        $this->assertEquals('JWT', $header['typ']);
        $this->assertEquals('HS256', $header['alg']);

        // Verify payload structure
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        $this->assertArrayHasKey('iss', $payload);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('iat', $payload);
    }

    public function testAccessTokenGeneration(): void
    {
        // Mock WordPress user functions
        $this->mockWordPressFunctions();

        // Test access token generation
        $user_id = 123;
        $access_token = $this->auth_jwt->generate_access_token($user_id);

        $this->assertIsString($access_token);
        $this->assertNotEmpty($access_token);

        // Verify token structure
        $parts = explode('.', $access_token);
        $this->assertCount(3, $parts);
    }

    public function testRefreshTokenGeneration(): void
    {
        // Test refresh token generation
        $refresh_token = wp_auth_jwt_generate_token(64);

        $this->assertIsString($refresh_token);
        $this->assertEquals(64, strlen($refresh_token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $refresh_token);
    }

    public function testTokenExpiration(): void
    {
        // Create expired token
        $expired_payload = [
            'iss' => Auth_JWT::ISSUER,
            'exp' => time() - 3600, // Expired 1 hour ago
            'iat' => time() - 3600,
            'sub' => 123
        ];

        $expired_token = wp_auth_jwt_encode($expired_payload, WP_JWT_AUTH_SECRET);
        $result = wp_auth_jwt_decode($expired_token, WP_JWT_AUTH_SECRET);

        $this->assertFalse($result, 'Expired token should not be valid');
    }

    // Helper methods

    private function createValidJWTToken($user_id = 123): string
    {
        $payload = [
            'iss' => Auth_JWT::ISSUER,
            'iat' => time(),
            'exp' => time() + 3600,
            'sub' => $user_id,
            'data' => [
                'user' => [
                    'id' => $user_id
                ]
            ]
        ];

        return wp_auth_jwt_encode($payload, WP_JWT_AUTH_SECRET);
    }

    private function createMockRequest(array $params = []): stdClass
    {
        $request = new stdClass();
        foreach ($params as $key => $value) {
            $request->$key = $value;
        }
        return $request;
    }

    private function base64UrlDecode($data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    private function mockWordPressFunctions(): void
    {
        // Mock WordPress user functions for testing
        if (!function_exists('get_userdata')) {
            function get_userdata($user_id) {
                $user = new stdClass();
                $user->ID = $user_id;
                $user->user_login = 'testuser';
                $user->user_email = 'test@example.com';
                $user->display_name = 'Test User';
                return $user;
            }
        }

        if (!function_exists('is_wp_error')) {
            function is_wp_error($thing) {
                return $thing instanceof WP_Error;
            }
        }
    }
}