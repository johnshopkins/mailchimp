<?php

namespace MailChimp;

class Campaign
{
	/**
	 * MailChimp\Api object
	 * @var object
	 */
	protected $api;

	/**
	 * Campaign ID
	 * @var string
	 */
	public $id;
	
	/**
	 * __construct
	 * @param object $api MailChimp\Api object
	 * @param array $content Content array expected by MailChimp API (http://apidocs.mailchimp.com/api/2.0/campaigns/create.php)
	 * @param array  $options Options array expected by MailChimp API (http://apidocs.mailchimp.com/api/2.0/campaigns/create.php)
	 * @param string $type Type of campaign
	 */
	public function __construct(Api $api, $content, $options = array(), $type = "regular")
	{
		$this->api = $api;
		$this->create($content, $options, $type);
	}

	/**
	 * Creates a new MailChimp campaign.
	 * @param  array $content
	 * @param  array $options
	 * @param  string $type
	 * @return null
	 */
	protected function create($content, $options, $type)
	{
		$data = array(
			"content" => $content,
			"options" => $this->getListDefaults($options),
			"type" => $type
		);

		$response = $this->api->post("/campaigns/create.json", $data);

		if (property_exists($response, "error")) {
			throw new Exception\CreateCampaign($response->error);
		}

		$this->id = $response->id;
	}

	/**
	 * Schedules the created campaign.
	 * @param  string $time A date/time string readable by strtotime()
	 * @return null
	 */
	public function schedule($time)
	{
		$data = array(
			"cid" => $this->id,
			"schedule_time" => gmdate("Y-m-d H:i:s", strtotime($time))
		);

		$response = $this->api->post("/campaigns/schedule.json", $data);

		if (property_exists($response, "error")) {
			throw new Exception\ScheduleCampaign($response->error);
		}
	}

	/**
	 * Set the subject, from email, and from name according to the
	 * list defaults if they are not already set.
	 * @param  array $options
	 * @return array Revised options
	 */
	protected function getListDefaults($options)
	{
		if (isset($options["subject"]) && isset($options["from_email"]) && isset($options["from_name"])) {
			return $options;
		}

		$listInfo = $this->getListInfo($options["list_id"]);

		$options["subject"] = isset($options["subject"]) ? $options["subject"] : $listInfo->default_subject;
		$options["from_email"] = isset($options["from_email"]) ? $options["from_email"] : $listInfo->default_from_email;
		$options["from_name"] = isset($options["from_name"]) ? $options["from_name"] : $listInfo->default_from_name;

		return $options;
	}

	/**
	 * Get information on a given MailChimp list.
	 * @param  string $id List ID
	 * @return array
	 */
	protected function getListInfo($id)
	{
		$data = array(
			"filters" => array(
				"list_id" => $id
			)
		);
		
		$lists = $this->api->post("lists/list", $data);
		return $lists->data[0];
	}
}