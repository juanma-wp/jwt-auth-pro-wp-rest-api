#!/usr/bin/env node

/**
 * Configure WordPress cookie settings for different test scenarios
 * This script uses WordPress REST API to configure cookie settings
 */

const https = require('https');
const http = require('http');

const WP_URL = process.env.WP_URL || 'https://rest-api-tests.wp.local';
const WP_USER = process.env.WP_USER || 'admin';
const WP_PASS = process.env.WP_PASS || 'S$genSlH%24GLe0k1hy(C6r3';

// Cookie configuration scenarios
const SCENARIOS = {
  'same-domain': {
    name: 'Same Domain (Lax)',
    config: {
      samesite: 'Lax',
      secure: 'auto',
      path: '/',
      domain: 'auto',
      auto_detect: false,
    },
    corsOrigins: 'http://localhost:5173',
  },
  'cross-origin': {
    name: 'Cross-Origin (None + Secure)',
    config: {
      samesite: 'None',
      secure: true,
      path: '/',
      domain: 'auto',
      auto_detect: false,
    },
    corsOrigins: 'http://localhost:5173',
  },
  'auto-detect': {
    name: 'Auto-detect (Default)',
    config: {
      samesite: 'auto',
      secure: 'auto',
      path: 'auto',
      domain: 'auto',
      auto_detect: true,
    },
    corsOrigins: 'http://localhost:5173',
  },
  'localhost-dev': {
    name: 'Localhost Development',
    config: {
      samesite: 'None',
      secure: false,
      path: '/',
      domain: 'auto',
      auto_detect: false,
    },
    corsOrigins: 'http://localhost:5173',
  },
  'strict-production': {
    name: 'Strict Production',
    config: {
      samesite: 'Strict',
      secure: true,
      path: '/',
      domain: 'auto',
      auto_detect: false,
    },
    corsOrigins: 'https://example.com',
  },
};

/**
 * Make HTTP request to WordPress API
 */
function makeRequest(url, options, data = null) {
  return new Promise((resolve, reject) => {
    const isHttps = url.startsWith('https');
    const lib = isHttps ? https : http;

    // Allow self-signed certificates
    if (isHttps) {
      options.rejectUnauthorized = false;
    }

    const req = lib.request(url, options, (res) => {
      let body = '';

      res.on('data', (chunk) => {
        body += chunk;
      });

      res.on('end', () => {
        resolve({
          statusCode: res.statusCode,
          headers: res.headers,
          body: body ? JSON.parse(body) : null,
        });
      });
    });

    req.on('error', reject);

    if (data) {
      req.write(JSON.stringify(data));
    }

    req.end();
  });
}

/**
 * Authenticate and get JWT token
 */
async function authenticate() {
  console.log('🔐 Authenticating with WordPress...');

  const url = `${WP_URL}/wp-json/jwt/v1/token`;
  const options = {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  };

  const data = {
    username: WP_USER,
    password: WP_PASS,
  };

  try {
    const response = await makeRequest(url, options, data);

    if (response.statusCode === 200 && response.body.data.access_token) {
      console.log('✓ Authentication successful');
      return response.body.data.access_token;
    } else {
      throw new Error(`Authentication failed: ${response.statusCode}`);
    }
  } catch (error) {
    console.error('✗ Authentication failed:', error.message);
    throw error;
  }
}

/**
 * Update cookie configuration
 */
async function updateCookieConfig(token, config) {
  console.log('📝 Updating cookie configuration...');

  const url = `${WP_URL}/wp-json/wp/v2/settings`;
  const options = {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
  };

  const data = {
    jwt_auth_cookie_config: config,
  };

  try {
    const response = await makeRequest(url, options, data);

    if (response.statusCode === 200) {
      console.log('✓ Cookie configuration updated');
      return true;
    } else {
      throw new Error(`Update failed: ${response.statusCode}`);
    }
  } catch (error) {
    console.error('✗ Cookie configuration update failed:', error.message);
    throw error;
  }
}

/**
 * Update CORS origins
 */
async function updateCorsOrigins(token, origins) {
  console.log('🌐 Updating CORS origins...');

  const url = `${WP_URL}/wp-json/wp/v2/settings`;
  const options = {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
  };

  const data = {
    jwt_auth_pro_general_settings: {
      cors_allowed_origins: origins,
    },
  };

  try {
    const response = await makeRequest(url, options, data);

    if (response.statusCode === 200) {
      console.log('✓ CORS origins updated');
      return true;
    } else {
      throw new Error(`Update failed: ${response.statusCode}`);
    }
  } catch (error) {
    console.error('✗ CORS origins update failed:', error.message);
    throw error;
  }
}

/**
 * Main function
 */
async function main() {
  const scenario = process.argv[2];

  if (!scenario) {
    console.log('Cookie Scenario Configuration Tool\n');
    console.log('Usage: node configure-scenario.js <scenario>\n');
    console.log('Available scenarios:');
    Object.keys(SCENARIOS).forEach((key) => {
      console.log(`  - ${key}: ${SCENARIOS[key].name}`);
    });
    process.exit(1);
  }

  if (!SCENARIOS[scenario]) {
    console.error(`Error: Unknown scenario "${scenario}"`);
    console.log('\nAvailable scenarios:');
    Object.keys(SCENARIOS).forEach((key) => {
      console.log(`  - ${key}: ${SCENARIOS[key].name}`);
    });
    process.exit(1);
  }

  const config = SCENARIOS[scenario];

  console.log('\n======================================');
  console.log(`  Configuring: ${config.name}`);
  console.log('======================================\n');

  try {
    // Authenticate
    const token = await authenticate();

    // Update cookie config
    await updateCookieConfig(token, config.config);

    // Update CORS origins
    await updateCorsOrigins(token, config.corsOrigins);

    console.log('\n======================================');
    console.log('✓ Configuration complete!');
    console.log('======================================\n');

    console.log('Configuration applied:');
    console.log(JSON.stringify(config.config, null, 2));
    console.log(`\nCORS origins: ${config.corsOrigins}`);
    console.log('');
  } catch (error) {
    console.error('\n✗ Configuration failed:', error.message);
    process.exit(1);
  }
}

// Run if called directly
if (require.main === module) {
  main();
}

module.exports = { SCENARIOS, authenticate, updateCookieConfig, updateCorsOrigins };
