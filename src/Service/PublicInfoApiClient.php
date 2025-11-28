<?php

namespace Drupal\public_info\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Client service for the SpaceX public API.
 */
class PublicInfoApiClient {

  const API_URL = 'https://api.spacexdata.com/v4/launches';

  protected $client;
  protected $cache;
  protected $config;
  protected $logger;

  /**
   * PublicInfoApiClient constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(ClientInterface $client, CacheBackendInterface $cache, ConfigFactoryInterface $config, LoggerInterface $logger) {
    $this->client = $client;
    $this->cache = $cache;
    $this->config = $config;
    $this->logger = $logger;
  }

  /**
   * Get launches from SpaceX API.
   *
   * If $requested_limit is provided it overrides the configured launch_limit.
   *
   * @param int|null $requested_limit
   *   Optional limit to override config value.
   *
   * @return array|null
   *   An array of launches or NULL on error.
   */
  public function getLaunches(int $requested_limit = NULL): ?array {
    $settings = $this->config->get('public_info.settings');
    $ttl = ($settings->get('cache_ttl') ?? 15) * 60;
    $limit = $settings->get('launch_limit') ?? 5;

    if ($requested_limit !== NULL) {
      $limit = $requested_limit;
    }

    $cid = 'public_info:launches';

    if ($cache = $this->cache->get($cid)) {
      return array_slice($cache->data, 0, $limit);
    }

    try {
      $response = $this->client->get(self::API_URL, ['timeout' => 30]);
      if ($response->getStatusCode() !== 200) {
        $this->logger->error('SpaceX API returned HTTP @code', ['@code' => $response->getStatusCode()]);
        return NULL;
      }

      $data = json_decode($response->getBody(), TRUE);
      if (!is_array($data)) {
        $this->logger->error('Invalid JSON from SpaceX API.');
        return NULL;
      }

      $this->cache->set($cid, $data, time() + $ttl);
      return array_slice($data, 0, $limit);
    }
    catch (\Exception $e) {
      $this->logger->error('API Error: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Get launches with a block-specific limit and TTL.
   *
   * @param int $limit
   *   Number of launches to return.
   * @param int $ttl_minutes
   *   Cache TTL in minutes.
   *
   * @return array|null
   *   An array of launches or NULL on error.
   */
  public function getLaunchesLimitedWithTTL(int $limit, int $ttl_minutes): ?array {
    $cid = "public_info:block_launches_{$limit}_{$ttl_minutes}";

    if ($cache = $this->cache->get($cid)) {
      return array_slice($cache->data, 0, $limit);
    }

    $data = $this->getLaunches(50);

    if ($data) {
      $expire = time() + ($ttl_minutes * 60);
      $this->cache->set($cid, $data, $expire);
      return array_slice($data, 0, $limit);
    }

    return NULL;
  }

}
