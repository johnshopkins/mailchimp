<?php

namespace MailChimp;

class MailingList
{
	/**
	 * MailChimp\Api object
	 * @var object
	 */
	protected $api;

	/**
	 * List ID
	 * @var string
	 */
	public $id;

	/**
	 * __construct
	 * @param object  $api MailChimp\Api object
	 * @param string  $id List ID
	 */
	public function __construct(Api $api, $id)
	{
		$this->api = $api;
		$this->id = $id;
	}

	/**
	 * Get information on the list.
	 * @param  string $id List ID
	 * @return array
	 */
	public function get()
	{
		return $this->api->request("lists/{$this->id}");
	}
}
