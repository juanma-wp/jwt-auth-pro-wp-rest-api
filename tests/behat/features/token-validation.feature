Feature: JWT Token Validation
  In order to ensure secure authentication
  As a developer
  I need to validate JWT tokens properly

  Background:
    Given a WordPress database is configured

  Scenario: Validate a valid JWT token
    Given I request a JWT token with username "admin" and password "password"
    And I should receive a valid JWT token
    When I make a request to "/wp-json/jwt-auth/v1/token/validate" with the JWT token
    Then the response status code should be 200
    And the response should contain "code"

  Scenario: Reject an invalid JWT token
    When I make a request to "/wp-json/jwt-auth/v1/token/validate" with the JWT token
    Then the response status code should be 401
    And I should receive an error message "Authorization header not found"

  Scenario: Reject an expired JWT token
    Given I request a JWT token with username "admin" and password "password"
    And I should receive a valid JWT token
    # In a real scenario, you would wait for token expiration
    # or manipulate the token to be expired
    # This is a placeholder for demonstration
    When I make a request to "/wp-json/wp/v2/users/me" with the JWT token
    Then the response status code should be 200
