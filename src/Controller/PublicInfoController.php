<?php

namespace Drupal\public_info\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Url;
use Drupal\public_info\Service\PublicInfoApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for public info pages.
 */
class PublicInfoController extends ControllerBase {

  /**
   * The API client.
   *
   * @var \Drupal\public_info\Service\PublicInfoApiClient
   */
  protected $client;

  /**
   * The pager manager.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * Constructs a PublicInfoController object.
   *
   * @param \Drupal\public_info\Service\PublicInfoApiClient $client
   *   The API client.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   The pager manager.
   */
  public function __construct(PublicInfoApiClient $client, PagerManagerInterface $pager_manager) {
    $this->client = $client;
    $this->pagerManager = $pager_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('public_info.api_client'),
      $container->get('pager.manager')
    );
  }

  /**
   * Main page view with pagination.
   */
  public function view() {
    $config = $this->config('public_info.settings');
    $total_to_fetch = (int) $config->get('launch_limit') ?: 20;
    $ttl_minutes = (int) $config->get('cache_ttl') ?: 15;

    $launches = $this->client->getLaunches($total_to_fetch);

    if (!$launches) {
      return [
        '#markup' => $this->t('Unable to load SpaceX launch data at this time.'),
        '#cache' => ['max-age' => 300],
      ];
    }

    $items_per_page = 5;
    $total_items = count($launches);
    $pager = $this->pagerManager->createPager($total_items, $items_per_page);
    $current_page = $pager->getCurrentPage();
    $offset = $current_page * $items_per_page;
    $paged_launches = array_slice($launches, $offset, $items_per_page);

    $refresh_url = Url::fromRoute('public_info.refresh', [], [
      'query' => [
        'csrf_token' => \Drupal::csrfToken()->get('public_info.refresh'),
      ],
    ])->toString();

    return [
      '#theme' => 'public_info_launches',
      '#launches' => $paged_launches,
      '#refresh_url' => $refresh_url,
      '#pager' => [
        '#type' => 'pager',
      ],
      '#attached' => [
        'library' => [
          'core/drupal.pager',
          'public_info/public_info_styles',
        ],
      ],
      '#cache' => [
        'max-age' => $ttl_minutes * 60,
        'tags' => ['public_info:launches'],
        'contexts' => [
          'user.permissions',
          'url.query_args:page',
        ],
      ],
    ];
  }

  /**
   * Refresh cache.
   */
  public function refresh() {
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['public_info:launches']);
    return $this->redirect('public_info.page');
  }

}
