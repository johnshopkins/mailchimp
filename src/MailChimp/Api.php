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
	public function __construct(\HttpExchange\Interfaces\ClientAdapterInterface $http, $dc)
	{
		$this->http = $http;
		$this->baseUrl = "https://{$dc}.api.mailchimp.com/3.0/";
	}

	/**
	 * Make a request to the MailChimp API
	 * @param  string $endpoint MailChimp API endpoint
	 * @param  string $method   HTTP method
	 * @param  array  $body     Request body (pre-json_encode)
   * @param  array  $params   Array of k=>v query string parameters
	 * @return object Response from API.
	 */
	public function request($endpoint, $method = "get", $body = [], $params = [])
	{
    $uri = $this->baseUrl . $endpoint;

    $opts = [];

    if (!empty($body)) {
      $opts['body'] = json_encode($body);
    }

    if (!empty($params)) {
      $opts['query'] = $params;
    }

    return $this->http->$method($uri, $opts)->getBody();
	}

  public function getResponseDetails()
  {
    $response = $this->http->response;

    return [
      'reasonPhrase' => $response->getReasonPhrase(),
      'statusCode' => $this->getStatusCode(),
      'headers' => $response->getHeaders(),
      'body' => $response->getBody(),
    ];
  }

  public function getStatusCode()
  {
    return $this->http->getStatusCode();
  }
}
