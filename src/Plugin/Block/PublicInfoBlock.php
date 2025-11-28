<?php

namespace Drupal\public_info\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\public_info\Service\PublicInfoApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Public Info' block with per-block config.
 *
 * @Block(
 *   id = "public_info_block",
 *   admin_label = @Translation("Public Info (SpaceX) Block"),
 *   category = @Translation("Custom")
 * )
 */
class PublicInfoBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The API client.
   *
   * @var \Drupal\public_info\Service\PublicInfoApiClient
   */
  protected $apiClient;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, PublicInfoApiClient $api_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->apiClient = $api_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('public_info.api_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'launch_limit' => 3,
      'cache_ttl' => 10,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['launch_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of launches to display'),
      '#default_value' => $this->configuration['launch_limit'],
      '#min' => 1,
    ];

    $form['cache_ttl'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache TTL (minutes)'),
      '#default_value' => $this->configuration['cache_ttl'],
      '#min' => 1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['launch_limit'] = $form_state->getValue('launch_limit');
    $this->configuration['cache_ttl'] = $form_state->getValue('cache_ttl');
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return $account->hasPermission('access public info page')
      ? AccessResult::allowed()
      : AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $limit = (int) $this->configuration['launch_limit'];
    $ttl = (int) $this->configuration['cache_ttl'];

    $launches = $this->apiClient->getLaunchesLimitedWithTTL($limit, $ttl);

    if (!$launches) {
      return [
        '#markup' => $this->t('Unable to load SpaceX launch data.'),
      ];
    }

    return [
      '#theme' => 'public_info_launches',
      '#launches' => $launches,
      '#cache' => [
        'max-age' => $ttl * 60,
        'contexts' => ['user.roles'],
      ],
    ];
  }

}
