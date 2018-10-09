<?php

namespace Drupal\ts_generator\Plugin\Derivative;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;


class RestViewApiProvider implements ContainerDeriverInterface {
  protected $derivatives = [];
  protected $basePluginId;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewStorage;

  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity.manager')->getStorage('view')
    );
  }

  public function __construct($base_plugin_id, EntityStorageInterface $view_storage) {
    $this->basePluginId = $base_plugin_id;
    $this->viewStorage = $view_storage;
  }

  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    foreach ($this->viewStorage->loadMultiple() as $view) {
      /** @var \Drupal\views\ViewEntityInterface $view */

      if (!$view->status()) {
        continue;
      }

      $executable = $view->getExecutable();
      $executable->initDisplay();

      foreach ($executable->displayHandlers as $display) {
        /** @var \Drupal\views\Plugin\views\display\DisplayPluginInterface $display */
        if (!isset($display) || empty($display->definition['returns_response']) || empty($display->definition['provider']) || $display->definition['provider'] != 'rest') {
          continue;
        }

        $delta = $view->id() . '-' . $display->display['id'];

        $this->derivatives[$delta] = [
          'config_dependencies' => [
            'config' => [
              $view->getConfigDependencyName(),
            ],
          ],
        ] + $base_plugin_definition;
      }
    }
    return $this->derivatives;
  }

}