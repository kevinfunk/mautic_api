<?php

namespace Drupal\mautic_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;
use Symfony\Component\HttpFoundation\RequestStack;

class MauticApiService implements MauticApiServiceInterface {

  /**
   * The immutable entity clone settings configuration entity.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @var \Mautic\Auth\AuthInterface
   */
  protected $auth;

  /**
   * InstallmentsManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->config = $config_factory->get('mautic_api.settings');
    $this->request = $request_stack->getCurrentRequest();

    $initAuth = new ApiAuth();
    $this->auth = $initAuth->newAuth([
      'userName' => $this->config->get('basic_auth_username'),
      'password' => $this->config->get('basic_auth_password')
    ], 'BasicAuth');
  }

  /**
   * {@inheritdoc}
   */
  public function createContact($email, $data) {
    if (!$this->auth) {
      throw new \Exception("Mautic API not authorized.");
    }
    $api = new MauticApi();
    $contact_api = $api->newApi('contacts', $this->auth, $this->config->get('base_url'));

    $contact_data = [
      'email' => $email,
      'ipAddress' => $this->request->getClientIp(),
    ];

    $contact_fields = $contact_api->getFieldList();
    if (empty($contact_fields['errors'])) {
      foreach ($contact_fields as $contact_field) {
        $alias = $contact_field['alias'];
        if (isset($data[$alias])) {
          $contact_data[$alias] = $data[$alias];
        }
      }
    }

    // Create the contact
    $response = $contact_api->create($contact_data);
    $this->logErrors($response);
    if (isset($response[$contact_api->itemName()]) && $contact = $response[$contact_api->itemName()]) {
      return $contact;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function sendEmailToContact($email_id, $contact_id, $parameters = []) {
    if (!$this->auth) {
      throw new \Exception("Mautic API not authorized.");
    }
    $api = new MauticApi();
    $emails_api = $api->newApi('emails', $this->auth, $this->config->get('base_url'));
    $response = $emails_api->sendToContact($email_id, $contact_id, $parameters);
    $this->logErrors($response);
    return $response;
  }

  /**
   * @param $response
   */
  protected function logErrors($response) {
    if (!isset($response['errors']) || empty($response['errors'])) {
      return;
    }
    // Log all errors.
    foreach ($response['errors'] as $error) {
      $message = $error['message'];
      \Drupal::logger('commerce_mautic')->error('Mautic API Error: @message', ['@message' => $message]);
    }
  }

}