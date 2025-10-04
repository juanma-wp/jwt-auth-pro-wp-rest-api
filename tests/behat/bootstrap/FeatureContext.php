<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends BaseWordpressContext implements Context
{
	/**
	 * The response from the last API call.
	 *
	 * @var array
	 */
	private $response;

	/**
	 * The HTTP status code from the last API call.
	 *
	 * @var int
	 */
	private $statusCode;

	/**
	 * JWT tokens storage.
	 *
	 * @var array
	 */
	private $tokens = array();

	/**
	 * Initializes context.
	 */
	public function __construct()
	{
		// No parent constructor to call
	}

	/**
	 * @Given a WordPress database is configured
	 */
	public function aWordpressDatabaseIsConfigured()
	{
		// This is a background step that ensures WordPress is ready
		// In wp-env, the database is automatically configured
		// We just need to verify the site is accessible
		$url = $this->getSiteUrl();
		$ch  = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_exec($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error       = curl_error($ch);
		curl_close($ch);

		if (! empty($error)) {
			throw new Exception('WordPress site is not accessible: ' . $error);
		}

		if ($status_code < 200 || $status_code >= 400) {
			throw new Exception('WordPress site returned error status: ' . $status_code);
		}
	}

	/**
	 * @When I request a JWT token with username :username and password :password
	 */
	public function iRequestAJwtTokenWithUsernameAndPassword($username, $password)
	{
		// Use query parameter format since pretty permalinks may not be configured
		$url  = $this->getSiteUrl() . '/?rest_route=/jwt/v1/token';
		$data = array(
			'username' => $username,
			'password' => $password,
		);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'Content-Type: application/json',
			)
		);

		$response_body    = curl_exec($ch);
		$this->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->response   = json_decode($response_body, true);
		curl_close($ch);
	}

	/**
	 * @Then I should receive a valid JWT token
	 */
	public function iShouldReceiveAValidJwtToken()
	{
		if (200 !== $this->statusCode) {
			throw new Exception('Expected status code 200, got ' . $this->statusCode);
		}

		if (! isset($this->response['data']['access_token']) || empty($this->response['data']['access_token'])) {
			throw new Exception('No access token found in response');
		}

		$this->tokens['access_token'] = $this->response['data']['access_token'];
	}

	/**
	 * @Then I should receive a refresh token
	 */
	public function iShouldReceiveARefreshToken()
	{
		if (! isset($this->response['data']['refresh_token']) || empty($this->response['data']['refresh_token'])) {
			throw new Exception('No refresh token found in response');
		}

		$this->tokens['refresh_token'] = $this->response['data']['refresh_token'];
	}

	/**
	 * @When I make a request to :endpoint with the JWT token
	 */
	public function iMakeARequestToWithTheJwtToken($endpoint)
	{
		// Convert /wp-json/path to query parameter format
		$rest_route = str_replace('/wp-json', '', $endpoint);
		$url        = $this->getSiteUrl() . '/?rest_route=' . $rest_route;

		$headers = array();

		// Only add Authorization header if token exists
		// This allows testing unauthorized access
		if (isset($this->tokens['access_token'])) {
			$headers[] = 'Authorization: Bearer ' . $this->tokens['access_token'];
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if (! empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		$response_body    = curl_exec($ch);
		$this->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->response   = json_decode($response_body, true);
		curl_close($ch);
	}

	/**
	 * @Then the response status code should be :code
	 */
	public function theResponseStatusCodeShouldBe($code)
	{
		if ((int) $code !== $this->statusCode) {
			throw new Exception(
				sprintf(
					'Expected status code %d, got %d. Response: %s',
					$code,
					$this->statusCode,
					json_encode($this->response)
				)
			);
		}
	}

	/**
	 * @Then the response should contain :field
	 */
	public function theResponseShouldContain($field)
	{
		if (! isset($this->response[$field])) {
			throw new Exception(
				sprintf(
					'Field "%s" not found in response. Available fields: %s',
					$field,
					implode(', ', array_keys($this->response))
				)
			);
		}
	}

	/**
	 * @When I refresh the JWT token using the refresh token
	 */
	public function iRefreshTheJwtTokenUsingTheRefreshToken()
	{
		// Use query parameter format since pretty permalinks may not be configured
		$url = $this->getSiteUrl() . '/?rest_route=/jwt/v1/refresh';

		if (! isset($this->tokens['refresh_token'])) {
			throw new Exception('No refresh token available');
		}

		$data = array(
			'refresh_token' => $this->tokens['refresh_token'],
		);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'Content-Type: application/json',
			)
		);

		$response_body    = curl_exec($ch);
		$this->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->response   = json_decode($response_body, true);
		curl_close($ch);

		if (isset($this->response['data']['token'])) {
			$this->tokens['access_token'] = $this->response['data']['token'];
		}
	}

	/**
	 * @Then I should receive an error message :message
	 */
	public function iShouldReceiveAnErrorMessage($message)
	{
		if (! isset($this->response['message'])) {
			throw new Exception('No error message found in response');
		}

		if (false === strpos($this->response['message'], $message)) {
			throw new Exception(
				sprintf(
					'Expected error message to contain "%s", got "%s"',
					$message,
					$this->response['message']
				)
			);
		}
	}

	/**
	 * Get the WordPress site URL.
	 *
	 * @return string
	 */
	protected function getSiteUrl()
	{
		return parent::getSiteUrl();
	}
}
