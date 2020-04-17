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
      '#description' => $this->t('The base url of the mautic installation.'),
      '#required' => TRUE,
    ];

    $form['credential_provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Credential provider'),
      '#options' => [
        'config' => 'Mautic API (config)',
      ],
      '#default_value' => $config->get('credential_provider'),
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'api-credentials',
        'method' => 'replace',
        'effect' => 'fade',
      ],
      '#required' => TRUE,
    ];

    $form['credentials'] = [
      '#type' => 'details',
      '#title' => $this->t('API-User credentials'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#prefix' => '<div id="api-credentials">',
      '#suffix' => '</div>',
    ];

    $credential_provider = $form_state->getValue('credential_provider', $config->get('credential_provider'));

    if (\Drupal::moduleHandler()->moduleExists('key')) {
      $form['credential_provider']['#options']['key'] = 'Key Module';

      /** @var \Drupal\key\Plugin\KeyPluginManager $key_type */
      $key_type = \Drupal::service('plugin.manager.key.key_type');
      if ($key_type->hasDefinition('user_password')) {
        $form['credential_provider']['#options']['multikey'] = 'Key Module (user/password)';
      }
    }

    if ($credential_provider === 'config') {
      $form['credentials']['config']['username'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Username'),
        '#default_value' => $config->get('credentials.config.username'),
      ];

      $form['credentials']['config']['password'] = [
        '#type' => 'password',
        '#title' => $this->t('Password'),
        '#default_value' => $config->get('credentials.config.password'),
        '#attributes' => [
          'autocomplete' => 'off',
        ],
      ];
    }
    elseif ($credential_provider === 'key') {
      $form['credentials']['key']['username'] = [
        '#type' => 'key_select',
        '#title' => $this->t('Username'),
        '#description' => $this->t('A username required by the api server.'),
        '#default_value' => $config->get('smtp_credentials.key.username'),
        '#empty_option' => $this->t('- Please select -'),
        '#key_filters' => ['type' => 'authentication'],
        '#required' => TRUE,
      ];
      $form['credentials']['key']['password'] = [
        '#type' => 'key_select',
        '#title' => $this->t('Password'),
        '#description' => $this->t('A password required by the api server.'),
        '#default_value' => $config->get('smtp_credentials.key.password'),
        '#empty_option' => $this->t('- Please select -'),
        '#key_filters' => ['type' => 'authentication'],
        '#required' => TRUE,
      ];
    }
    elseif ($credential_provider === 'multikey') {
      $form['credentials']['multikey']['user_password'] = [
        '#type' => 'key_select',
        '#title' => $this->t('User/password'),
        '#description' => $this->t('A username + password required by the SMTP server.'),
        '#default_value' => $config->get('credentials.multikey.user_password'),
        '#empty_option' => $this->t('- Please select -'),
        '#key_filters' => ['type' => 'user_password'],
        '#required' => TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mautic_api.settings');
    $form_state->cleanValues();

    $config->set('base_url', $form_state->getValue('base_url'));
    $config->set('credential_provider', $form_state->getValue('credential_provider'));

    $config->set('credentials', [
      $config->get('credential_provider') => $form_state->getValue(['credentials', $config->get('credential_provider')])
    ]);

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Ajax callback for the transport dependent configuration options.
   *
   * @param $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state.
   * @return array
   *   The form element containing the configuration options.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form['credentials'];
  }

}
