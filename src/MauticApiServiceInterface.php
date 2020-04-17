<?php

namespace Drupal\mautic_api;

/**
 * Interface MauticApiServiceInterface
 *
 * @package Drupal\mautic_api
 */
interface MauticApiServiceInterface {

  /**
   * Gets username/password for mautic api call.
   *
   * @return array
   *   Array with 'username' and 'password' as keys.
   */
  public function getCredentials();

  /**
   * @param string $email
   * @param array $data
   *
   * @return mixed
   */
  public function createContact($email, $data);

  /**
   * @param string $email_id
   *   The mautic id of an email.
   * @param array $contact_id
   *   The mautic id of a contact.
   *
   * @return mixed
   */
  public function sendEmailToContact($email_id, $contact_id, $parameters = []);

}
