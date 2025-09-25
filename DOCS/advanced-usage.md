# Advanced Usage

## JavaScript Client Example

```javascript
class JWTAuth {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
        this.accessToken = null;
    }

    async login(username, password) {
        const response = await fetch(`${this.baseUrl}/wp-json/jwt/v1/token`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include', // Important for HTTPOnly cookies
            body: JSON.stringify({ username, password })
        });

        if (response.ok) {
            const data = await response.json();
            this.accessToken = data.data.access_token;
            return data;
        }
        throw new Error('Login failed');
    }

    async apiCall(endpoint, options = {}) {
        const response = await fetch(`${this.baseUrl}${endpoint}`, {
            ...options,
            headers: {
                ...options.headers,
                'Authorization': `Bearer ${this.accessToken}`
            }
        });

        if (response.status === 401) {
            // Try to refresh token
            await this.refreshToken();
            // Retry original request
            return this.apiCall(endpoint, options);
        }

        return response;
    }

    async refreshToken() {
        const response = await fetch(`${this.baseUrl}/wp-json/jwt/v1/refresh`, {
            method: 'POST',
            credentials: 'include' // HTTPOnly cookie sent automatically
        });

        if (response.ok) {
            const data = await response.json();
            this.accessToken = data.data.access_token;
            return data;
        }

        // Refresh failed, need to login again
        this.accessToken = null;
        throw new Error('Please login again');
    }

    async logout() {
        await fetch(`${this.baseUrl}/wp-json/jwt/v1/logout`, {
            method: 'POST',
            credentials: 'include'
        });
        this.accessToken = null;
    }
}

// Usage
const auth = new JWTAuth('https://your-wordpress-site.com');
await auth.login('username', 'password');

// Make authenticated API calls
const posts = await auth.apiCall('/wp-json/wp/v2/posts');
```


