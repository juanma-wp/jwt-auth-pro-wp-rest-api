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
	 * @When I request a JWT token with username :username and password :password
	 */
	public function iRequestAJwtTokenWithUsernameAndPassword($username, $password)
	{
		$url  = $this->getSiteUrl() . '/wp-json/jwt-auth/v1/token';
		$data = array(
			'username' => $username,
			'password' => $password,
		);

		$response = wp_remote_post(
			$url,
			array(
				'body'    => wp_json_encode($data),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			)
		);

		$this->statusCode = wp_remote_retrieve_response_code($response);
		$this->response   = json_decode(wp_remote_retrieve_body($response), true);
	}

	/**
	 * @Then I should receive a valid JWT token
	 */
	public function iShouldReceiveAValidJwtToken()
	{
		if (200 !== $this->statusCode) {
			throw new Exception('Expected status code 200, got ' . $this->statusCode);
		}

		if (! isset($this->response['data']['token']) || empty($this->response['data']['token'])) {
			throw new Exception('No token found in response');
		}

		$this->tokens['access_token'] = $this->response['data']['token'];
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
		$url = $this->getSiteUrl() . $endpoint;

		if (! isset($this->tokens['access_token'])) {
			throw new Exception('No access token available');
		}

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->tokens['access_token'],
				),
			)
		);

		$this->statusCode = wp_remote_retrieve_response_code($response);
		$this->response   = json_decode(wp_remote_retrieve_body($response), true);
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
					wp_json_encode($this->response)
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
		$url = $this->getSiteUrl() . '/wp-json/jwt-auth/v1/token/refresh';

		if (! isset($this->tokens['refresh_token'])) {
			throw new Exception('No refresh token available');
		}

		$response = wp_remote_post(
			$url,
			array(
				'body'    => wp_json_encode(
					array(
						'refresh_token' => $this->tokens['refresh_token'],
					)
				),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			)
		);

		$this->statusCode = wp_remote_retrieve_response_code($response);
		$this->response   = json_decode(wp_remote_retrieve_body($response), true);

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
