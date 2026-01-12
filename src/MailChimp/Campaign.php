<?php

namespace MailChimp;

use phpDocumentor\Reflection\Types\Boolean;
use Psr\Log\LoggerInterface;

class Campaign
{
  /**
   * MailChimp\Api object
   * @var object
   */
  protected $api;

  protected $id = null;
  protected array $settings = [];

  protected array $defaultSettings = [
    'type' => 'regular'
  ];

  /**
   * __construct
   * @param object $api      MailChimp\Api object
   * @param array  $settings Campaign settings (template_id, list_id, title, subject, content)
   */
  public function __construct(Api $api, $settings, protected LoggerInterface $logger)
  {
    $this->api = $api;
    $this->settings = $this->validateSettings($settings);
  }

  /**
   * Validate that all required settings are present. Value validation is done within MailChimp.
   * @return array
   */
  protected function validateSettings($passedSettings = []): array
  {
    $settings = array_merge($this->defaultSettings, $passedSettings);

    // make sure all required keys are present
    $required = isset($passedSettings['web_id']) ?
      ['template_id', 'list_id', 'template_sections'] :
      ['template_id', 'list_id', 'title', 'subject', 'template_sections'];

    $missing = array_diff($required, array_keys($settings));


    // validate campaign and get the ID based on the web ID provided
    if (isset($passedSettings['web_id'])) {
      // web id is NOT the campaign ID. unfortumately, the only way to find the actual
      // campaign ID is to query the campaigns endpoing and filter by web id

      // assigns $this->id if campaign is found
      $this->findCampaignByWebId($settings['web_id'], $settings['list_id']);

      if (is_null($this->id)) {
        $list = $this->getList($settings['list_id']);
        throw new \InvalidArgumentException("Campaign {$settings['web_id']} was not found. Please ensure you are providing the correct ID and that the assigned list is correct. The list we are looking for is \"{$list->name}.\"");
      }

    }

    return $settings;
  }

  protected function findCampaignByWebId($webId, $listId)
  {
    $data = [
      'status' => 'save', // campaigns that have not been sent yet
      'list_id' => $listId, // only return campaigns assigned to the correct list
      'type' => 'regular',
      'sort_field' => 'create_time',
      'sort_dir' => 'DESC',
    ];

    // validate existing campaign
    $response = $this->api->request('campaigns', 'get', [], $data);
    $body = $response->getBody();

    foreach ($body->campaigns as $campaign) {
      if ($campaign->web_id == $webId) {
        $this->id = $campaign->id;
        break;
      }
    }

    return $this->id;
  }

  /**
   * Create a MailChimp campaign
   * http://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/
   * @return null
   */
  public function create()
  {
    $data = [
      'type' => $this->settings['type'],
      'settings' => $this->getCampaignSettings(),
      'recipients' => [
        'list_id' => $this->settings['list_id']
      ],
    ];

    $response = $this->api->request('campaigns', 'post', $data);

    $body = $response->getBody();
    $this->id = $body->id;

    $this->addContentToCampaign();

    return $body;
  }

  /**
   * Set the content for the campaign
   * http://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/content/
   * @return null
   */
  public function addContentToCampaign()
  {
    $data = [
      'template' => [
        'id' => $this->settings['template_id'],
        'sections' => $this->settings['template_sections'],
      ],
    ];

    $this->api->request("campaigns/{$this->id}/content", 'put', $data);
  }

  /**
   * Set the subject line, from email, and from name, and reply
   * email according to the passed settings and the list defaults.
   * @return array Revised settings
   */
  protected function getCampaignSettings()
  {
    // get list defaults
    $list = new MailingList($this->api, $this->settings['list_id']);
    $response = $list->get(['campaign_defaults']);

    $listInfo = $response->getBody();

    if (empty($listInfo->campaign_defaults)) {

      // an exception wasn't thrown in the API, but the data isn't what we expect; log some info
      $this->logger->error('Mailing list . ' . $this->settings['list_id'] . ' returned no campaign defaults.', [
        'context' => [
          'list' => $this->settings['list_id'],
          'response' => $this->api->getResponseDetails()
        ]
      ]);

      throw new \Exception('Mailing list . ' . $this->settings["list_id"] . ' returned no campaign defaults.');
    }

    $listDefaults = $listInfo->campaign_defaults;

    // compile settings based on passed settings and list defaults
    $settings = [
      'title' => $this->settings['title'],
      'subject_line' => isset($this->settings['subject']) ? $this->settings['subject'] : $listDefaults->subject,
      'from_email' => isset($this->settings['from_email']) ? $this->settings['from_email'] : $listDefaults->from_email,
      'from_name' => isset($this->settings['from_name']) ? $this->settings['from_name'] : $listDefaults->from_name,
    ];

    $settings['reply_to'] = $settings['from_email'];

    return $settings;
  }

  /**
   * Returns the list onject or NULL if list not found
   * @param $listId
   * @return null|object
   */
  protected function getList($listId)
  {
    try {
      $response = $this->api->request("lists/{$listId}", 'get');
      return $response->getBody();
    } catch (\Throwable $e) {
      return null;
    }
  }

  // /**
  //  * Schedules the created campaign.
  //  * http://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/#action-post_campaigns_campaign_id_actions_schedule
  //  * @param  string $time A date/time string readable by strtotime()
  //  * @return null
  //  */
  // public function schedule($time)
  // {
  // 	$data = ['schedule_time' => gmdate('Y-m-d H:i:s', strtotime($time))];
  // 	$response = $this->api->request("campaigns/{$this->id}/actions/schedule", 'post', $data);
  //
  //   if ($response->getStatusCode() !== 204) {
  //
  //     // an exception wasn't thrown in the API, but the data isn't what we expect; log some info
  //     $this->logger->error('Failed to schedule campaign.', [
  //       'context' => [
  //         'campaign_id' => $this->id,
  //         'response' => $this->api->getResponseDetails()
  //       ]
  //     ]);
  //
  //     // kill the app
  //     die();
  //   }
  // }
}
