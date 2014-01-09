<?php

namespace app\mailchimp;

class Campaign
{
	protected $api;
	public $id;

	public function __construct($api, $content, $options = array(), $type = "regular")
	{
		$this->api = $api;
		$this->create($content, $options, $type);
	}

	protected function create($content, $options, $type)
	{
		$data = array(
			"content" => $content,
			"options" => $this->getCampaignDefaults($options),
			"type" => $type
		);

		$response = $this->api->post("/campaigns/create.json", $data);

		if (property_exists($response, "error")) {
			throw new \app\exceptions\mailchimp\CreateCampaign($response->error);
		}

		$this->id = $response->id;
	}

	public function schedule($time)
	{
		$data = array(
			"cid" => $this->id,
			"schedule_time" => gmdate("Y-m-d H:i:s", strtotime($time))
		);

		$response = $this->api->post("/campaigns/schedule.json", $data);

		if (property_exists($response, "error")) {
			throw new \app\exceptions\mailchimp\ScheduleCampaign($response->error);
		}
	}

	protected function getCampaignDefaults($options)
	{
		if (isset($options["subject"]) && isset($options["from_email"]) && isset($options["from_name"])) {
			return $options;
		}

		// get default list info
		$listInfo = $this->getListInfo($options["list_id"]);

		$options["subject"] = isset($options["subject"]) ? $options["subject"] : $listInfo->default_subject;
		$options["from_email"] = isset($options["from_email"]) ? $options["from_email"] : $listInfo->default_from_email;
		$options["from_name"] = isset($options["from_name"]) ? $options["from_name"] : $listInfo->default_from_name;

		return $options;
	}

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