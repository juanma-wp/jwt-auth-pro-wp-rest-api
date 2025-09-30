# JWT Auth Core

A shared JWT authentication library for WordPress plugins.

## Overview

This package provides common JWT token management functionality that can be shared across multiple WordPress plugins. It includes token generation, validation, and refresh capabilities.

## Installation

This is a local Composer package. To use it in your WordPress plugin:

### Local Development

Add this to your plugin's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../jwt-auth-core"
    }
  ],
  "require": {
    "myorg/jwt-auth-core": "@dev"
  }
}
```

Then run:

```bash
composer install
```

## Usage

```php
<?php

use MyOrg\JWTAuthCore\TokenManager;

// Initialize the token manager
$token_manager = new TokenManager( 'your-secret-key', 3600 );

// Generate a token
$payload = array(
    'user_id' => 123,
    'username' => 'john_doe',
);
$token = $token_manager->generate_token( $payload );

// Validate a token
$decoded_payload = $token_manager->validate_token( $token );
if ( $decoded_payload ) {
    echo 'Token is valid!';
    print_r( $decoded_payload );
}

// Refresh a token
$new_token = $token_manager->refresh_token( $token );
```

## Features

- **Token Generation**: Create JWT tokens with custom payloads
- **Token Validation**: Verify token signatures and expiration
- **Token Refresh**: Generate new tokens from existing ones
- **Configurable Expiration**: Set custom token expiration times
- **WordPress Compatible**: Uses WordPress functions when available

## API Reference

### TokenManager

#### Constructor

```php
public function __construct( string $secret, int $expiration = 3600 )
```

- `$secret`: JWT secret key for signing tokens
- `$expiration`: Token expiration time in seconds (default: 3600)

#### Methods

##### generate_token( array $payload ): string

Generate a new JWT token.

##### validate_token( string $token ): array|false

Validate a JWT token and return its payload.

##### refresh_token( string $token ): string|false

Refresh a token with a new expiration time.

##### get_expiration(): int

Get the current token expiration time.

##### set_expiration( int $expiration ): void

Set the token expiration time.

## Development

### Moving to a Separate Repository

When ready to extract this package to its own repository:

1. Create a new repository (e.g., `myorg/jwt-auth-core`)
2. Move the contents of this directory to the new repository
3. Update `composer.json` in your plugins to use VCS repository:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/myorg/jwt-auth-core"
    }
  ],
  "require": {
    "myorg/jwt-auth-core": "^1.0"
  }
}
```

4. Tag a release in the new repository
5. Run `composer update` in your plugins

### Bundling for WordPress.org

When releasing plugins to WordPress.org, include the `vendor/` directory:

1. Run `composer install --no-dev` before building
2. Include the `vendor/` directory in your plugin ZIP
3. Don't add `vendor/` to `.gitignore` in the final build

## License

GPL-2.0-or-later

## Author

Your Organization
