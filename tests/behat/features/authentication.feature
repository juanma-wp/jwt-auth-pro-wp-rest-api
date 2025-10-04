Feature: JWT Authentication
  In order to secure the WordPress REST API
  As a developer
  I need to be able to authenticate users with JWT tokens

  Background:
    Given a WordPress database is configured

  Scenario: Successfully obtain JWT token with valid credentials
    When I request a JWT token with username "admin" and password "password"
    Then the response status code should be 200
    And I should receive a valid JWT token

  Scenario: Fail to obtain JWT token with invalid credentials
    When I request a JWT token with username "admin" and password "wrongpassword"
    Then the response status code should be 403
    And I should receive an error message "Invalid username or password"

  Scenario: Access protected endpoint with valid JWT token
    Given I request a JWT token with username "admin" and password "password"
    And I should receive a valid JWT token
    When I make a request to "/wp-json/wp/v2/users/me" with the JWT token
    Then the response status code should be 200
    And the response should contain "id"
    And the response should contain "name"

  Scenario: Fail to access protected endpoint without token
    When I make a request to "/wp-json/wp/v2/users/me" with the JWT token
    Then the response status code should be 401
