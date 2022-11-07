<?php

namespace MailChimp;

class Campaign
{
	/**
	 * MailChimp\Api object
	 * @var object
	 */
	protected $api;

	protected $defaultSettings = array(
		"type" => "regular"
	);

	/**
	 * __construct
	 * @param object $api      MailChimp\Api object
	 * @param array  $settings Campaign settings (template_id, list_id, title, subject, content)
	 */
	public function __construct(Api $api, $settings = array())
	{
		$this->api = $api;

		$this->settings = array_merge($this->defaultSettings, $settings);
		$this->validateSettings();

		$this->create();
	}

	/**
	 * Validate that all required settings are present, though
	 * it doesn't validate each value. We'll leave that to Mailchimp.
	 * @return boolean
	 */
	protected function validateSettings()
	{
		$required = array("template_id", "list_id", "title", "subject", "template_sections");
		$missing = array_diff($required, array_keys($this->settings));

		if ($missing) throw new Exception\CreateCampaign("Missing campaign settings");

		return true;
	}

	/**
	 * Create a MailChimp campaign
	 * http://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/
	 * @return null
	 */
	protected function create()
	{
		$data = array(
			"type" => $this->settings["type"],
			"settings" => $this->getCampaignSettings(),
			"recipients" => array(
				"list_id" => $this->settings["list_id"]
			)
		);

		$response = $this->api->request("campaigns", "post", $data);

		if (property_exists($response, "error")) {
			throw new Exception\CreateCampaign($response->error);
		}

		$this->id = $response->id;

		$this->addContentToCampaign();
	}

	/**
	 * Set the subject line, from email, and from name, and reply
	 * email according to the passed settings and the list defaults.
	 * @return array Revised settings
	 */
	protected function getCampaignSettings()
	{
		// get list defaults
		$list = new MailingList($this->api, $this->settings["list_id"]);
		$listInfo = $list->get();
		$listDefaults = $listInfo->campaign_defaults;

		// compile settings based on passed settings and list defaults
		$settings = array(
			"title" => $this->settings["title"],
			"subject_line" => isset($this->settings["subject"]) ? $this->settings["subject"] : $listDefaults->subject,
			"from_email" => isset($this->settings["from_email"]) ? $this->settings["from_email"] : $listDefaults->from_email,
			"from_name" => isset($this->settings["from_name"]) ? $this->settings["from_name"] : $listDefaults->from_name
		);

		$settings["reply_to"] = $settings["from_email"];

		return $settings;
	}

	/**
	 * Set the content for the campaign
	 * http://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/content/
	 * @return null
	 */
	protected function addContentToCampaign()
	{
		$data = array(
			"template" => array(
				"id" => $this->settings["template_id"],
				"sections" => $this->settings["template_sections"]
			)
		);
		$response = $this->api->request("campaigns/{$this->id}/content", "put", $data);

		if (property_exists($response, "error")) {
			throw new Exception\CreateCampaign($response->error);
		}
	}

	/**
	 * Schedules the created campaign.
	 * http://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/#action-post_campaigns_campaign_id_actions_schedule
	 * @param  string $time A date/time string readable by strtotime()
	 * @return null
	 */
	public function schedule($time)
	{
		$data = array("schedule_time" => gmdate("Y-m-d H:i:s", strtotime($time)));
		$response = $this->api->request("campaigns/{$this->id}/actions/schedule", "post", $data);

    if ($this->api->getStatusCode() !== 204) {
      throw new Exception\ScheduleCampaign($response->error);
    }
	}
}
