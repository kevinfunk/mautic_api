<?php

namespace Drupal\mautic_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide the settings form for entity clone.
 */
class MauticApiSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['mautic_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mautic_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mautic_api.settings');

    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base url'),
      '#default_value' => $config->get('base_url'),
      '#description' => $this->t('The base url of the mautic installation.')
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('basic_auth_username'),
    ];

    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('basic_auth_password'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mautic_api.settings');
    $form_state->cleanValues();

    $config->set('base_url', $form_state->getValue('base_url'));
    $config->set('basic_auth_username', $form_state->getValue('username'));
    $config->set('basic_auth_password', $form_state->getValue('password'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
