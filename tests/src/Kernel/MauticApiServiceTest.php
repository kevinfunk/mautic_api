<?php

namespace Drupal\Tests\mautic_api\Kernel;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Render\Markup;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\mautic_api\MauticApiService
 * @group mautic_api
 */
class MauticApiServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'mautic_api',
  ];

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The mautic_api.
   *
   * @var \Drupal\mautic_api\MauticApiService
   */
  protected $mauticApi;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig([
      'mautic_api',
    ]);
    $this->configFactory = $this->container->get('config.factory');
    $config = $this->configFactory->getEditable('mautic_api.settings');
    $config->set('credential_provider', 'config');
    $config->set('credentials', [
      'config' => [
        'username' => 'Test',
        'password' => 'Password',
      ],
    ]);
    $config->save();
    $this->mauticApi = $this->container->get('mautic_api');
  }

  /**
   * Tests getting the credentials from config.
   */
  public function testGetCredentials() {
    $config = $this->mauticApi->getCredentials();
    $this->assertTrue($config['username'] == 'Test');
    $this->assertTrue($config['password'] == 'Password');
  }

}
