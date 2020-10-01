<?php

namespace Drupal\mautic_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class MauticApiService
 * @package Drupal\mautic_api
 */
class MauticApiService implements MauticApiServiceInterface {

  /**
   * @var EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   *   The immutable entity clone settings configuration entity.
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
   * MauticApiService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param RequestStack $request_stack
   *   The current request stack.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->request = $request_stack->getCurrentRequest();
    $this->config = $config_factory->get('mautic_api.settings');
    $credentials = $this->getCredentials();

    $initAuth = new ApiAuth();
    $this->auth = $initAuth->newAuth([
      'userName' => $credentials['username'],
      'password' => $credentials['password']
    ], 'BasicAuth');
  }

  /**
   * {@inheritdoc}
   */
  public function getCredentials() {
    $credential_provider = $this->config->get('credential_provider');

    switch ($credential_provider) {
      case 'config':
        $credentials = $this->config->get('credentials');
        if (isset($credentials['config'])) {
          $username = $credentials['config']['username'];
          $password = $credentials['config']['password'];
        }
        break;

      case 'key':
        /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
        $storage = $this->entityTypeManager->getStorage('key');
        /** @var \Drupal\key\KeyInterface $username_key */
        if ($username_key = $storage->load($this->config['credentials']['key']['username'])) {
          $username = $username_key->getKeyValue();
        }
        /** @var \Drupal\key\KeyInterface $password_key */
        if ($password_key = $storage->load($this->config['credentials']['key']['password'])) {
          $password = $password_key->getKeyValue();
        }
        break;

      case 'multikey':
        /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
        $storage = \Drupal::entityTypeManager()->getStorage('key');
        /** @var \Drupal\key\KeyInterface $username_key */
        if ($user_password_key = $storage->load($this->config['credentials']['multikey']['user_password'])) {
          if ($values = $user_password_key->getKeyValues()) {
            $username = $values['username'];
            $password = $values['password'];
          }
        }
        break;
    }
    return [
      'username' => $username,
      'password' => $password
    ];
  }

  /**
   * Helper function to initiate connection with a Mautic instance.
   *
   * @param $endpoint
   *
   * @return \Mautic\Api\Api
   * @throws \Mautic\Exception\ContextNotFoundException
   */
  private function initiateConnection($endpoint) {
    if (!$this->auth) {
      throw new \Exception("Mautic API not authorized.");
    }
    $api = new MauticApi();
    return $api->newApi($endpoint, $this->auth, $this->config->get('base_url'));
  }

  /**
   * {@inheritdoc}
   */
  public function createContact($email, $data) {
    $connection = $this->initiateConnection('contacts');

    $contact_data = [
      'email' => $email,
      'ipAddress' => $this->request->getClientIp(),
    ];

    $contact_fields = $connection->getFieldList();
    if (empty($contact_fields['errors'])) {
      foreach ($contact_fields as $contact_field) {
        $alias = $contact_field['alias'];
        if (isset($data[$alias])) {
          $contact_data[$alias] = $data[$alias];
        }
      }
    }

    // Create the contact
    $response = $connection->create($contact_data);
    $this->logErrors($response);
    if (isset($response[$connection->itemName()]) && $contact = $response[$connection->itemName()]) {
      return $contact;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function sendEmailToContact($email_id, $contact_id, $parameters = []) {
    $connection = $this->initiateConnection('emails');
    $response = $connection->sendToContact($email_id, $contact_id, $parameters);
    $this->logErrors($response);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getList($endpoint) {
    $connection = $this->initiateConnection($endpoint);
    return $connection->getList();
  }

  /**
   * Small helper function to log mautic api errors.
   *
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
