/**
 * JWT Test Client Configuration
 *
 * This file defines test scenarios for different domain/protocol combinations.
 * The configuration is dynamically loaded based on URL parameters or defaults.
 */

const TEST_SCENARIOS = {
  // Same domain - Client and API on same domain
  'same-domain-http': {
    name: 'Same Domain (HTTP)',
    apiUrl: 'http://wordpress.localtest.me:8000/wp-json/jwt/v1',
    clientUrl: 'http://wordpress.localtest.me:8000',
    expectedSameSite: 'Lax',
    expectedSecure: false,
    description: 'Client and API on same domain with HTTP'
  },

  'same-domain-https': {
    name: 'Same Domain (HTTPS)',
    apiUrl: 'https://wordpress.localtest.me:8443/wp-json/jwt/v1',
    clientUrl: 'https://wordpress.localtest.me:8443',
    expectedSameSite: 'Lax',
    expectedSecure: true,
    description: 'Client and API on same domain with HTTPS'
  },

  // Cross-origin - Different domains
  'cross-origin-http': {
    name: 'Cross-Origin (HTTP)',
    apiUrl: 'http://wordpress.localtest.me:8000/wp-json/jwt/v1',
    clientUrl: 'http://client.localtest.me:8000',
    expectedSameSite: 'None',
    expectedSecure: false,
    description: 'Client and API on different domains with HTTP'
  },

  'cross-origin-https': {
    name: 'Cross-Origin (HTTPS)',
    apiUrl: 'https://wordpress.localtest.me:8443/wp-json/jwt/v1',
    clientUrl: 'https://client.localtest.me:8443',
    expectedSameSite: 'None',
    expectedSecure: true,
    description: 'Client and API on different domains with HTTPS'
  },

  // Mixed protocols
  'mixed-https-client-http-api': {
    name: 'Mixed (HTTPS Client → HTTP API)',
    apiUrl: 'http://wordpress.localtest.me:8000/wp-json/jwt/v1',
    clientUrl: 'https://client.localtest.me:8443',
    expectedSameSite: 'None',
    expectedSecure: false,
    description: 'HTTPS client connecting to HTTP API (will fail due to mixed content)'
  },

  'mixed-http-client-https-api': {
    name: 'Mixed (HTTP Client → HTTPS API)',
    apiUrl: 'https://wordpress.localtest.me:8443/wp-json/jwt/v1',
    clientUrl: 'http://client.localtest.me:8000',
    expectedSameSite: 'None',
    expectedSecure: true,
    description: 'HTTP client connecting to HTTPS API'
  },

  // Subdomain scenarios
  'subdomain-http': {
    name: 'Subdomain (HTTP)',
    apiUrl: 'http://api.localtest.me:8000/wp-json/jwt/v1',
    clientUrl: 'http://app.localtest.me:8000',
    expectedSameSite: 'None',
    expectedSecure: false,
    description: 'Client on subdomain, API on different subdomain (HTTP)'
  },

  'subdomain-https': {
    name: 'Subdomain (HTTPS)',
    apiUrl: 'https://api.localtest.me:8443/wp-json/jwt/v1',
    clientUrl: 'https://app.localtest.me:8443',
    expectedSameSite: 'None',
    expectedSecure: true,
    description: 'Client on subdomain, API on different subdomain (HTTPS)'
  }
};

/**
 * Get current test scenario from URL parameter or default
 */
function getCurrentScenario() {
  const params = new URLSearchParams(window.location.search);
  const scenario = params.get('scenario') || 'cross-origin-https';
  return TEST_SCENARIOS[scenario] || TEST_SCENARIOS['cross-origin-https'];
}

/**
 * Get all available scenarios for UI dropdown
 */
function getAllScenarios() {
  return Object.keys(TEST_SCENARIOS).map(key => ({
    key,
    ...TEST_SCENARIOS[key]
  }));
}

// Default credentials for testing
const DEFAULT_CREDENTIALS = {
  username: 'admin',
  password: 'password'
};
