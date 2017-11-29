<?php

namespace MailChimp;

class Api
{
	/**
	 * Instance of \HttpExchange\Interfaces\ClientInterface
	 * @var object
	 */
	protected $http;

	/**
	 * MailChimp datacenter
	 * @var string
	 */
	protected $baseUrl;

	/**
	 * [__construct description]
	 * @param object $http Class of \HttpExchange\Interfaces\ClientInterface - should already have credentials in place
	 * @param string $dc   MailChimp datacenter
	 */
	public function __construct(\HttpExchange\Interfaces\ClientInterface $http, $dc)
	{
		$this->http = $http;
		$this->baseUrl = "https://{$dc}.api.mailchimp.com/3.0/";
	}

	/**
	 * Make a request to the MailChimp API
	 * @param  string $endpoint MailChimp API endpoint
	 * @param  string $method   HTTP method
	 * @param  array  $body     Request body (pre-json_encode)
	 * @return object Response from API.
	 */
	public function request($endpoint, $method = "get", $body = array())
	{
		$response = $this->http->$method($this->baseUrl . $endpoint, array("body" => json_encode($body)))->getBody();

		if ($error = $this->checkForError($response)) {
			// create error property that calling class with look for
			$response->error = $error;
		}

		return $response;
	}

	/**
	 * Checks a MailChimp response for errors.
	 * @param  object $response Response from API
	 * @return mixed Error string if error found; FALSE if no error found.
	 */
	protected function checkForError($response)
	{
		if (property_exists($response, "errors") && !empty($response->errors)) {
			// return the first error
			return $response->errors[0]->message;
		}

		return false;
	}
}
