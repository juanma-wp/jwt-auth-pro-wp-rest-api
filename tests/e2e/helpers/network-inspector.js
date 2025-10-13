/**
 * Network Inspector Helper
 *
 * Helps inspect network requests, responses, headers, and cookies during tests
 */
export class NetworkInspector {
  constructor(page) {
    this.page = page;
    this.requests = [];
    this.responses = [];

    // Track all requests and responses
    page.on('request', (req) => this.requests.push(req));
    page.on('response', (resp) => this.responses.push(resp));
  }

  /**
   * Get response by URL pattern
   */
  getResponse(urlPattern) {
    return this.responses.find((r) => r.url().includes(urlPattern));
  }

  /**
   * Get request by URL pattern
   */
  getRequest(urlPattern) {
    return this.requests.find((r) => r.url().includes(urlPattern));
  }

  /**
   * Get all responses for a URL pattern
   */
  getResponses(urlPattern) {
    return this.responses.filter((r) => r.url().includes(urlPattern));
  }

  /**
   * Get response headers for a URL
   */
  async getResponseHeaders(urlPattern) {
    const resp = this.getResponse(urlPattern);
    return resp ? resp.headers() : null;
  }

  /**
   * Get CORS headers from response
   */
  async getCORSHeaders(urlPattern) {
    const resp = this.getResponse(urlPattern);
    if (!resp) return null;

    const headers = resp.headers();
    return {
      origin: headers['access-control-allow-origin'],
      credentials: headers['access-control-allow-credentials'],
      methods: headers['access-control-allow-methods'],
      headers: headers['access-control-allow-headers'],
      exposeHeaders: headers['access-control-expose-headers'],
      maxAge: headers['access-control-max-age'],
    };
  }

  /**
   * Get Set-Cookie header from response
   */
  async getSetCookieHeader(urlPattern) {
    const resp = this.getResponse(urlPattern);
    if (!resp) return null;

    const headers = resp.headers();
    return headers['set-cookie'];
  }

  /**
   * Parse Set-Cookie header into object
   */
  parseSetCookie(setCookieHeader) {
    if (!setCookieHeader) return null;

    const parts = setCookieHeader.split(';').map((p) => p.trim());
    const [nameValue, ...attributes] = parts;
    const [name, value] = nameValue.split('=');

    const cookie = {
      name,
      value,
      httpOnly: false,
      secure: false,
      sameSite: 'None',
      path: '/',
      domain: '',
    };

    attributes.forEach((attr) => {
      const [key, val] = attr.split('=').map((s) => s.trim());
      const keyLower = key.toLowerCase();

      if (keyLower === 'httponly') {
        cookie.httpOnly = true;
      } else if (keyLower === 'secure') {
        cookie.secure = true;
      } else if (keyLower === 'samesite') {
        cookie.sameSite = val;
      } else if (keyLower === 'path') {
        cookie.path = val;
      } else if (keyLower === 'domain') {
        cookie.domain = val;
      } else if (keyLower === 'max-age') {
        cookie.maxAge = parseInt(val, 10);
      } else if (keyLower === 'expires') {
        cookie.expires = val;
      }
    });

    return cookie;
  }

  /**
   * Get cookie details from browser context
   */
  async getCookieDetails(cookieName) {
    const cookies = await this.page.context().cookies();
    return cookies.find((c) => c.name === cookieName);
  }

  /**
   * Check if a cookie was set with correct attributes
   */
  async verifyCookieSet(cookieName, expectedAttributes = {}) {
    const cookie = await this.getCookieDetails(cookieName);

    if (!cookie) {
      return {
        success: false,
        error: `Cookie ${cookieName} not found`,
      };
    }

    const errors = [];

    if (expectedAttributes.httpOnly !== undefined && cookie.httpOnly !== expectedAttributes.httpOnly) {
      errors.push(`HttpOnly: expected ${expectedAttributes.httpOnly}, got ${cookie.httpOnly}`);
    }

    if (expectedAttributes.secure !== undefined && cookie.secure !== expectedAttributes.secure) {
      errors.push(`Secure: expected ${expectedAttributes.secure}, got ${cookie.secure}`);
    }

    if (expectedAttributes.sameSite !== undefined && cookie.sameSite !== expectedAttributes.sameSite) {
      errors.push(`SameSite: expected ${expectedAttributes.sameSite}, got ${cookie.sameSite}`);
    }

    if (expectedAttributes.path !== undefined && cookie.path !== expectedAttributes.path) {
      errors.push(`Path: expected ${expectedAttributes.path}, got ${cookie.path}`);
    }

    if (expectedAttributes.domain !== undefined && cookie.domain !== expectedAttributes.domain) {
      errors.push(`Domain: expected ${expectedAttributes.domain}, got ${cookie.domain}`);
    }

    return {
      success: errors.length === 0,
      errors,
      cookie,
    };
  }

  /**
   * Check if cookie was sent with request
   */
  async verifyCookieSent(urlPattern, cookieName) {
    const request = this.getRequest(urlPattern);
    if (!request) {
      return {
        success: false,
        error: `Request to ${urlPattern} not found`,
      };
    }

    const headers = request.headers();
    const cookieHeader = headers['cookie'];

    if (!cookieHeader) {
      return {
        success: false,
        error: 'No Cookie header in request',
      };
    }

    const hasCookie = cookieHeader.includes(`${cookieName}=`);

    return {
      success: hasCookie,
      error: hasCookie ? null : `Cookie ${cookieName} not in Cookie header`,
      cookieHeader,
    };
  }

  /**
   * Verify CORS headers are correct
   */
  async verifyCORS(urlPattern, expectedOrigin) {
    const corsHeaders = await this.getCORSHeaders(urlPattern);

    if (!corsHeaders) {
      return {
        success: false,
        error: `No response found for ${urlPattern}`,
      };
    }

    const errors = [];

    if (corsHeaders.origin !== expectedOrigin) {
      errors.push(`Origin: expected ${expectedOrigin}, got ${corsHeaders.origin}`);
    }

    if (corsHeaders.credentials !== 'true') {
      errors.push(`Credentials: expected true, got ${corsHeaders.credentials}`);
    }

    return {
      success: errors.length === 0,
      errors,
      corsHeaders,
    };
  }

  /**
   * Clear tracked requests and responses
   */
  clear() {
    this.requests = [];
    this.responses = [];
  }

  /**
   * Get all requests to a domain
   */
  getRequestsToDomain(domain) {
    return this.requests.filter((r) => r.url().includes(domain));
  }

  /**
   * Get all responses from a domain
   */
  getResponsesFromDomain(domain) {
    return this.responses.filter((r) => r.url().includes(domain));
  }

  /**
   * Log all requests and responses (for debugging)
   */
  logAll() {
    console.log('=== Requests ===');
    this.requests.forEach((req) => {
      console.log(`${req.method()} ${req.url()}`);
    });

    console.log('\n=== Responses ===');
    this.responses.forEach((resp) => {
      console.log(`${resp.status()} ${resp.url()}`);
    });
  }
}
