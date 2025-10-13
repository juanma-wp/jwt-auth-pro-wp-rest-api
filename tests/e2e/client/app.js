/**
 * JWT Test Client Application
 * Handles authentication flow and cookie testing
 */

let currentScenario = null;
let accessToken = null;

// Initialize app
document.addEventListener('DOMContentLoaded', () => {
  initializeScenarios();
  setupEventListeners();
  log('App initialized');
});

/**
 * Initialize scenario selector
 */
function initializeScenarios() {
  const scenarios = getAllScenarios();
  const select = document.getElementById('scenarioSelect');

  scenarios.forEach(scenario => {
    const option = document.createElement('option');
    option.value = scenario.key;
    option.textContent = scenario.name;
    select.appendChild(option);
  });

  // Set current scenario from URL or default
  currentScenario = getCurrentScenario();
  select.value = Object.keys(TEST_SCENARIOS).find(
    key => TEST_SCENARIOS[key] === currentScenario
  );

  updateScenarioInfo();
}

/**
 * Update scenario information display
 */
function updateScenarioInfo() {
  const info = document.getElementById('scenarioInfo');
  info.innerHTML = `
    <div class="scenario-details">
      <p><strong>API URL:</strong> <code>${currentScenario.apiUrl}</code></p>
      <p><strong>Client URL:</strong> <code>${currentScenario.clientUrl}</code></p>
      <p><strong>Expected SameSite:</strong> <code>${currentScenario.expectedSameSite}</code></p>
      <p><strong>Expected Secure:</strong> <code>${currentScenario.expectedSecure}</code></p>
      <p><strong>Description:</strong> ${currentScenario.description}</p>
    </div>
  `;
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
  document.getElementById('scenarioSelect').addEventListener('change', (e) => {
    window.location.search = `?scenario=${e.target.value}`;
  });

  document.getElementById('loginForm').addEventListener('submit', handleLogin);
  document.getElementById('checkCookiesBtn').addEventListener('click', checkCookies);
  document.getElementById('refreshBtn').addEventListener('click', refreshToken);
  document.getElementById('verifyBtn').addEventListener('click', verifyToken);
  document.getElementById('logoutBtn').addEventListener('click', logout);
  document.getElementById('clearLogsBtn').addEventListener('click', clearLogs);
}

/**
 * Handle login
 */
async function handleLogin(e) {
  e.preventDefault();

  const username = document.getElementById('username').value;
  const password = document.getElementById('password').value;

  log(`POST ${currentScenario.apiUrl}/token`);
  showStatus('loginStatus', 'Loading...', 'info');

  try {
    const response = await fetch(`${currentScenario.apiUrl}/token`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include', // Important for cookies
      body: JSON.stringify({ username, password })
    });

    log(`Response: ${response.status} ${response.statusText}`);

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || `HTTP ${response.status}`);
    }

    const data = await response.json();
    accessToken = data.data.access_token;

    showStatus('loginStatus', '✅ Login successful!', 'success');
    displayToken(data.data);
    log('Access token received and HTTPOnly cookie set');

  } catch (error) {
    showStatus('loginStatus', `❌ Error: ${error.message}`, 'error');
    log(`Error: ${error.message}`);
  }
}

/**
 * Check cookies
 */
function checkCookies() {
  log('Checking cookies...');

  const allCookies = document.cookie;
  const cookieArray = allCookies ? allCookies.split('; ') : [];

  let html = '<div class="cookie-info">';
  html += '<h3>JavaScript-Accessible Cookies:</h3>';

  if (cookieArray.length === 0) {
    html += '<p>No cookies accessible via JavaScript</p>';
  } else {
    html += '<ul>';
    cookieArray.forEach(cookie => {
      html += `<li><code>${cookie}</code></li>`;
    });
    html += '</ul>';
  }

  html += '<div class="warning">';
  html += '<p><strong>Note:</strong> HTTPOnly cookies (like <code>wp_jwt_refresh_token</code>) are NOT visible here.</p>';
  html += '<p>They are automatically sent with requests but cannot be accessed via JavaScript.</p>';
  html += '<p>Use browser DevTools → Application → Cookies to see them.</p>';
  html += '</div>';
  html += '</div>';

  document.getElementById('cookieStatus').innerHTML = html;
  log(`Found ${cookieArray.length} JavaScript-accessible cookies`);
}

/**
 * Refresh token
 */
async function refreshToken() {
  log(`POST ${currentScenario.apiUrl}/token/refresh`);
  showStatus('refreshStatus', 'Refreshing...', 'info');

  try {
    const response = await fetch(`${currentScenario.apiUrl}/token/refresh`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include' // HTTPOnly cookie sent automatically
    });

    log(`Response: ${response.status} ${response.statusText}`);

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || `HTTP ${response.status}`);
    }

    const data = await response.json();
    accessToken = data.data.access_token;

    showStatus('refreshStatus', '✅ Token refreshed successfully!', 'success');
    displayToken(data.data);
    log('New access token received');

  } catch (error) {
    showStatus('refreshStatus', `❌ Error: ${error.message}`, 'error');
    log(`Error: ${error.message}`);
  }
}

/**
 * Verify token
 */
async function verifyToken() {
  if (!accessToken) {
    showStatus('verifyStatus', '❌ No access token available', 'error');
    return;
  }

  log(`POST ${currentScenario.apiUrl}/token/verify`);
  showStatus('verifyStatus', 'Verifying...', 'info');

  try {
    const response = await fetch(`${currentScenario.apiUrl}/token/verify`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${accessToken}`
      }
    });

    log(`Response: ${response.status} ${response.statusText}`);

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || `HTTP ${response.status}`);
    }

    const data = await response.json();
    showStatus('verifyStatus', `✅ Token is valid! User: ${data.data.user.username}`, 'success');
    log('Token verified successfully');

  } catch (error) {
    showStatus('verifyStatus', `❌ Error: ${error.message}`, 'error');
    log(`Error: ${error.message}`);
  }
}

/**
 * Logout
 */
async function logout() {
  log(`POST ${currentScenario.apiUrl}/logout`);
  showStatus('logoutStatus', 'Logging out...', 'info');

  try {
    const response = await fetch(`${currentScenario.apiUrl}/logout`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${accessToken}`
      },
      credentials: 'include'
    });

    log(`Response: ${response.status} ${response.statusText}`);

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || `HTTP ${response.status}`);
    }

    accessToken = null;
    showStatus('logoutStatus', '✅ Logged out successfully!', 'success');
    document.getElementById('tokenDisplay').innerHTML = '';
    log('Logout successful, cookies cleared');

  } catch (error) {
    showStatus('logoutStatus', `❌ Error: ${error.message}`, 'error');
    log(`Error: ${error.message}`);
  }
}

/**
 * Display token information
 */
function displayToken(data) {
  const display = document.getElementById('tokenDisplay');
  display.innerHTML = `
    <div class="token-info">
      <p><strong>Token Type:</strong> ${data.token_type}</p>
      <p><strong>Expires In:</strong> ${data.expires_in} seconds</p>
      <p><strong>Access Token (first 50 chars):</strong></p>
      <code>${data.access_token.substring(0, 50)}...</code>
    </div>
  `;
}

/**
 * Show status message
 */
function showStatus(elementId, message, type) {
  const element = document.getElementById(elementId);
  element.className = `status ${type}`;
  element.textContent = message;
}

/**
 * Log network activity
 */
function log(message) {
  const logs = document.getElementById('networkLogs');
  const time = new Date().toLocaleTimeString();
  const entry = document.createElement('div');
  entry.className = 'log-entry';
  entry.textContent = `[${time}] ${message}`;
  logs.appendChild(entry);
  logs.scrollTop = logs.scrollHeight;
}

/**
 * Clear logs
 */
function clearLogs() {
  document.getElementById('networkLogs').innerHTML = '';
}
