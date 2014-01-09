<?php

namespace app\mailchimp;

class Api
{
	protected $http;
	protected $key;
	protected $baseUrl;

	public function __construct($http, $key, $dc)
	{
		$this->http = $http;
		$this->baseUrl = "https://{$dc}.api.mailchimp.com/2.0/";

		$this->key = $key;
	}

	public function post($endpoint, $data)
	{
		$response = $this->http->post($this->baseUrl . $endpoint, $this->addKey($data))->getBody();

		if ($error = $this->checkForError($response)) {
			// create error property that calling class with look for
			$response->error = $error;
		}

		return $response;
	}

	protected function addKey($data = array())
	{
		$data["apikey"] = $this->key;
		return $data;
	}

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