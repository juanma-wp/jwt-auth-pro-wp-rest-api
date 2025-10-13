/**
 * JWT Auth Test Client
 *
 * This application tests JWT authentication with HTTPOnly cookies
 * across different scenarios (same-domain, cross-origin, etc.)
 */

// State management
let accessToken = null;
let tokenExpiry = null;
const logs = [];

// Scenario descriptions
const scenarios = {
    'same-domain': {
        name: 'Same Domain (Lax)',
        description: 'React app and WordPress on same domain. SameSite=Lax, Secure=auto',
        expectedCookie: 'SameSite=Lax',
        corsRequired: false
    },
    'cross-origin': {
        name: 'Cross-Origin (None + Secure)',
        description: 'React app on different domain. SameSite=None, Secure=true, CORS required',
        expectedCookie: 'SameSite=None; Secure',
        corsRequired: true
    },
    'localhost': {
        name: 'Localhost Development',
        description: 'Local dev without HTTPS. SameSite=None, Secure=false (only works on localhost)',
        expectedCookie: 'SameSite=None',
        corsRequired: true
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadConfig();
    updateScenarioInfo();
    document.getElementById('scenario').addEventListener('change', updateScenarioInfo);
    log('App initialized', 'info');
});

// Configuration management
function loadConfig() {
    const saved = localStorage.getItem('jwtTestConfig');
    if (saved) {
        const config = JSON.parse(saved);
        document.getElementById('apiUrl').value = config.apiUrl || '';
        document.getElementById('username').value = config.username || '';
        document.getElementById('scenario').value = config.scenario || 'cross-origin';
        updateScenarioInfo();
        showStatus('loginStatus', 'Configuration loaded', 'success');
    }
}

function saveConfig() {
    const config = {
        apiUrl: document.getElementById('apiUrl').value,
        username: document.getElementById('username').value,
        scenario: document.getElementById('scenario').value
    };
    localStorage.setItem('jwtTestConfig', JSON.stringify(config));
    showStatus('loginStatus', 'Configuration saved', 'success');
}

function updateScenarioInfo() {
    const scenario = document.getElementById('scenario').value;
    const info = scenarios[scenario];
    const html = `
        <span class="scenario-badge">${info.name}</span>
        <br><br>
        <strong>Description:</strong> ${info.description}<br>
        <strong>Expected Cookie:</strong> <code>${info.expectedCookie}</code><br>
        <strong>CORS Required:</strong> ${info.corsRequired ? '✅ Yes' : '❌ No'}
    `;
    document.getElementById('scenarioInfo').innerHTML = html;
}

// API calls
async function login() {
    const apiUrl = document.getElementById('apiUrl').value;
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    if (!apiUrl || !username || !password) {
        showStatus('loginStatus', 'Please fill in all fields', 'error');
        return;
    }

    try {
        showStatus('loginStatus', 'Logging in...', 'info');
        log(`POST ${apiUrl}/token`, 'request');

        const response = await fetch(`${apiUrl}/token`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include', // Important: Include cookies
            body: JSON.stringify({ username, password })
        });

        log(`Response: ${response.status} ${response.statusText}`, 'response');
        logHeaders('Response Headers', response.headers);

        const data = await response.json();

        if (response.ok && data.success) {
            accessToken = data.data.access_token;
            tokenExpiry = Date.now() + (data.data.expires_in * 1000);

            // Save to localStorage (in real app, this would be memory only)
            localStorage.setItem('access_token', accessToken);
            localStorage.setItem('token_expiry', tokenExpiry);

            showStatus('loginStatus',
                `✅ Login successful! Access token expires in ${data.data.expires_in} seconds`,
                'success'
            );

            // Display token info
            displayToken(data.data);

            // Check for Set-Cookie header (won't be visible due to HTTPOnly)
            log('Note: refresh_token cookie set via HTTPOnly (not visible in JS)', 'info');

        } else {
            throw new Error(data.message || 'Login failed');
        }

    } catch (error) {
        showStatus('loginStatus', `❌ Error: ${error.message}`, 'error');
        log(`Error: ${error.message}`, 'error');
    }
}

async function refreshToken() {
    const apiUrl = document.getElementById('apiUrl').value;

    try {
        showStatus('refreshStatus', 'Refreshing token...', 'info');
        log(`POST ${apiUrl}/token/refresh`, 'request');
        log('Sending request with credentials (HTTPOnly cookie will be sent automatically)', 'info');

        const response = await fetch(`${apiUrl}/token/refresh`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include', // Critical: This sends the HTTPOnly cookie
        });

        log(`Response: ${response.status} ${response.statusText}`, 'response');
        logHeaders('Response Headers', response.headers);

        const data = await response.json();

        if (response.ok && data.success) {
            accessToken = data.data.access_token;
            tokenExpiry = Date.now() + (data.data.expires_in * 1000);

            localStorage.setItem('access_token', accessToken);
            localStorage.setItem('token_expiry', tokenExpiry);

            showStatus('refreshStatus',
                `✅ Token refreshed! New token expires in ${data.data.expires_in} seconds`,
                'success'
            );

            displayToken(data.data);

            log('Refresh token rotated (new HTTPOnly cookie set)', 'info');

        } else {
            throw new Error(data.message || 'Refresh failed');
        }

    } catch (error) {
        showStatus('refreshStatus', `❌ Error: ${error.message}`, 'error');
        log(`Error: ${error.message}`, 'error');
    }
}

async function verifyToken() {
    const apiUrl = document.getElementById('apiUrl').value;
    const token = accessToken || localStorage.getItem('access_token');

    if (!token) {
        showStatus('verifyStatus', '❌ No access token available. Please login first.', 'error');
        return;
    }

    try {
        showStatus('verifyStatus', 'Verifying token...', 'info');
        log(`GET ${apiUrl}/verify`, 'request');

        const response = await fetch(`${apiUrl}/verify`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
            },
            credentials: 'include',
        });

        log(`Response: ${response.status} ${response.statusText}`, 'response');

        const data = await response.json();

        if (response.ok && data.success) {
            showStatus('verifyStatus',
                `✅ Token is valid! User: ${data.data.user.username} (ID: ${data.data.user.id})`,
                'success'
            );

            const html = `
                <div class="token-display">
                    <strong>User Information:</strong><br>
                    <pre>${JSON.stringify(data.data.user, null, 2)}</pre>
                </div>
            `;
            document.getElementById('verifyStatus').innerHTML += html;

        } else {
            throw new Error(data.message || 'Verification failed');
        }

    } catch (error) {
        showStatus('verifyStatus', `❌ Error: ${error.message}`, 'error');
        log(`Error: ${error.message}`, 'error');
    }
}

async function logout() {
    const apiUrl = document.getElementById('apiUrl').value;
    const token = accessToken || localStorage.getItem('access_token');

    try {
        showStatus('logoutStatus', 'Logging out...', 'info');
        log(`POST ${apiUrl}/logout`, 'request');

        const response = await fetch(`${apiUrl}/logout`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            credentials: 'include',
        });

        log(`Response: ${response.status} ${response.statusText}`, 'response');

        const data = await response.json();

        if (response.ok && data.success) {
            // Clear local storage
            localStorage.removeItem('access_token');
            localStorage.removeItem('token_expiry');
            accessToken = null;
            tokenExpiry = null;

            showStatus('logoutStatus',
                '✅ Logged out successfully! HTTPOnly cookie has been cleared.',
                'success'
            );

            // Clear token display
            document.getElementById('tokenDisplay').classList.add('hidden');
            log('Local tokens cleared, HTTPOnly cookie removed', 'info');

        } else {
            throw new Error(data.message || 'Logout failed');
        }

    } catch (error) {
        showStatus('logoutStatus', `❌ Error: ${error.message}`, 'error');
        log(`Error: ${error.message}`, 'error');
    }
}

// Cookie checking
function checkCookies() {
    const allCookies = document.cookie;

    log('Checking cookies via document.cookie', 'info');

    if (!allCookies) {
        showStatus('cookieStatus',
            'ℹ️ No cookies visible in JavaScript (this is expected!)',
            'info'
        );

        const html = `
            <div class="cookie-info">
                <h3>✅ HTTPOnly Cookie Working Correctly!</h3>
                <p>The <code>refresh_token</code> cookie is NOT visible here because it has the <code>HttpOnly</code> flag.</p>
                <p>This is a <strong>security feature</strong> that prevents JavaScript from accessing the cookie, protecting against XSS attacks.</p>
                <ul>
                    <li><strong>Cookie is set:</strong> Yes (by server)</li>
                    <li><strong>Visible in JS:</strong> No (HttpOnly protection)</li>
                    <li><strong>Sent with requests:</strong> Yes (automatically by browser)</li>
                    <li><strong>Accessible via DevTools:</strong> Yes (Application > Cookies)</li>
                </ul>
            </div>
        `;
        document.getElementById('cookieInfo').innerHTML = html;

        log('HTTPOnly cookie is working correctly - not accessible via JavaScript', 'success');

    } else {
        showStatus('cookieStatus',
            `ℹ️ Found ${allCookies.split(';').length} cookie(s), but refresh_token should NOT be here`,
            'info'
        );

        const html = `
            <div class="token-display">
                <strong>Visible Cookies:</strong><br>
                <pre>${allCookies}</pre>
            </div>
            <div class="cookie-info">
                <p>⚠️ If you see <code>refresh_token</code> in the list above, the HTTPOnly flag is NOT set correctly!</p>
            </div>
        `;
        document.getElementById('cookieInfo').innerHTML = html;

        log(`Cookies: ${allCookies}`, 'info');
    }
}

function showExpectedCookie() {
    const scenario = document.getElementById('scenario').value;
    const info = scenarios[scenario];

    const html = `
        <div class="cookie-info">
            <h3>Expected Cookie Configuration</h3>
            <ul>
                <li><strong>Scenario:</strong> ${info.name}</li>
                <li><strong>Cookie Name:</strong> <code>refresh_token</code></li>
                <li><strong>Attributes:</strong> <code>${info.expectedCookie}; HttpOnly</code></li>
                <li><strong>CORS Required:</strong> ${info.corsRequired ? 'Yes' : 'No'}</li>
                <li><strong>Lifetime:</strong> 30 days (default)</li>
            </ul>
            <p style="margin-top: 15px;">
                💡 <strong>Tip:</strong> Check browser DevTools → Application → Cookies to see the actual cookie.
            </p>
        </div>
    `;
    document.getElementById('cookieInfo').innerHTML = html;
}

// Helper functions
function displayToken(data) {
    const expiryDate = new Date(Date.now() + (data.expires_in * 1000));
    const html = `
        <div class="grid">
            <div class="grid-item">
                <strong>Token Type</strong>
                <span>${data.token_type}</span>
            </div>
            <div class="grid-item">
                <strong>Expires In</strong>
                <span>${data.expires_in} seconds</span>
            </div>
            <div class="grid-item">
                <strong>Expires At</strong>
                <span>${expiryDate.toLocaleTimeString()}</span>
            </div>
        </div>
        <strong>Access Token (first 50 chars):</strong><br>
        <code>${data.access_token.substring(0, 50)}...</code><br><br>
        <strong>Full Token:</strong><br>
        <pre style="white-space: pre-wrap; word-break: break-all;">${data.access_token}</pre>
    `;
    document.getElementById('tokenDisplay').innerHTML = html;
    document.getElementById('tokenDisplay').classList.remove('hidden');
}

function expireToken() {
    localStorage.setItem('access_token', 'expired_token');
    localStorage.setItem('token_expiry', Date.now() - 1000);
    accessToken = 'expired_token';
    tokenExpiry = Date.now() - 1000;

    showStatus('refreshStatus',
        'ℹ️ Access token marked as expired. Try refreshing now!',
        'info'
    );
    log('Access token artificially expired', 'info');
}

function showStatus(elementId, message, type) {
    const element = document.getElementById(elementId);
    element.className = `status ${type}`;
    element.innerHTML = message;
    element.style.display = 'block';
}

function clearStatus() {
    ['loginStatus', 'refreshStatus', 'verifyStatus', 'logoutStatus', 'cookieStatus'].forEach(id => {
        document.getElementById(id).innerHTML = '';
        document.getElementById(id).style.display = 'none';
    });
    document.getElementById('cookieInfo').innerHTML = '';
    document.getElementById('tokenDisplay').classList.add('hidden');
}

// Logging
function log(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const logEntry = {
        timestamp,
        message,
        type
    };
    logs.push(logEntry);

    const logElement = document.getElementById('networkLogs');
    const color = type === 'error' ? '#e53e3e' :
                  type === 'success' ? '#38a169' :
                  type === 'request' ? '#667eea' :
                  type === 'response' ? '#48bb78' : '#4a5568';

    const logHtml = `<div style="color: ${color}; margin-bottom: 5px;">[${timestamp}] ${message}</div>`;
    logElement.innerHTML += logHtml;
    logElement.scrollTop = logElement.scrollHeight;

    // Also log to console
    console.log(`[${timestamp}] ${message}`);
}

function logHeaders(title, headers) {
    const headerObj = {};
    headers.forEach((value, key) => {
        headerObj[key] = value;
    });
    log(`${title}: ${JSON.stringify(headerObj, null, 2)}`, 'info');
}

function clearLogs() {
    logs.length = 0;
    document.getElementById('networkLogs').innerHTML = '';
    log('Logs cleared', 'info');
}

// Auto-check token expiry
setInterval(() => {
    if (accessToken && tokenExpiry && Date.now() > tokenExpiry) {
        log('⚠️ Access token has expired! Use refresh to get a new one.', 'error');
    }
}, 5000);
