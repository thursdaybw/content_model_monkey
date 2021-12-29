<?php

namespace Drupal\content_model_monkey;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * ContentModelMonkeyField plugin manager.
 */
class ContentModelMonkeyFieldPluginManager extends DefaultPluginManager {

  /**
   * Constructs ContentModelMonkeyFieldPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ContentModelMonkeyField',
      $namespaces,
      $module_handler,
      'Drupal\content_model_monkey\ContentModelMonkeyFieldInterface',
      'Drupal\content_model_monkey\Annotation\ContentModelMonkeyField'
    );
    $this->alterInfo('content_model_monkey_field_info');
    $this->setCacheBackend($cache_backend, 'content_model_monkey_field_plugins');
  }

}
