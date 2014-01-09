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
	 * MailChimp API key
	 * @var string
	 */
	protected $key;

	/**
	 * MailChimp datacenter
	 * @var string
	 */
	protected $baseUrl;

	/**
	 * [__construct description]
	 * @param object $http Instance of \HttpExchange\Interfaces\ClientInterface
	 * @param string $key MailChimp API key
	 * @param string $dc MailChimp datacenter
	 */
	public function __construct(\HttpExchange\Interfaces\ClientInterface $http, $key, $dc)
	{
		$this->http = $http;
		$this->key = $key;
		$this->baseUrl = "https://{$dc}.api.mailchimp.com/2.0/";
	}

	/**
	 * Make a POST request to the MailChimp API
	 * @param  string $endpoint MailChimp API endpoint
	 * @param  array $data Data to send to the request. API key is added automatically.
	 * @return object Response from API.
	 */
	public function post($endpoint, $data)
	{
		$response = $this->http->post($this->baseUrl . $endpoint, $this->addKey($data))->getBody();

		if ($error = $this->checkForError($response)) {
			// create error property that calling class with look for
			$response->error = $error;
		}

		return $response;
	}

	/**
	 * Adds the API key to the data array
	 * @param array $data
	 * @return array
	 */
	protected function addKey($data = array())
	{
		$data["apikey"] = $this->key;
		return $data;
	}

	/**
	 * Checks a MailChimp response for errors.
	 * @param  object $response Response from API
	 * @return mixed Error string if error found; FALSE if no error found.
	 */
	protected function checkForError($response)
	{
		// returned not 200
		if (property_exists($response, "status") && $response->status == "error") {
			return $response->error;
		}

		// returned 200, but there was some kind of problem
		if (property_exists($response, "errors") && !empty($response->errors)) {
			// return the first error
			return $response->errors[0]->error;
		}

		return false;
	}
}