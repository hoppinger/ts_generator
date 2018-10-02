<?php

namespace Drupal\ts_generator;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class ApiProviderPluginManager extends DefaultPluginManager {
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/TsGenerator/ApiProvider', $namespaces, $module_handler, 'Drupal\ts_generator\Plugin\ApiProviderInterface', 'Drupal\ts_generator\Annotation\TsGeneratorApiProvider');

    $this->setCacheBackend($cache_backend, 'ts_generator_api_providers');
    $this->alterInfo('ts_generator_api_provider');
  }
}
