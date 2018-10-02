<?php

namespace Drupal\ts_generator\Plugin\TsGenerator\ApiProvider;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ts_generator\GeneratorInterface;
use Drupal\ts_generator\Plugin\ApiProviderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @TsGeneratorApiProvider(
 *   id = "rest_entity"
 * )
 */
class RestEntity extends ApiProviderBase {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, GeneratorInterface $generator, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $generator);

    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ts_generator.generator'),
      $container->get('entity_type.manager')
    );
  }

  public function getEntityTypes() {
    $entity_types = [];

    $configs = $this->entityTypeManager->getStorage('rest_resource_config')->loadMultiple();
    foreach ($configs as $config) {
      /* @var \Drupal\rest\RestResourceConfigInterface $config */
      $resource_plugin = $config->getResourcePlugin();
      $plugin_id = $resource_plugin->getPluginId();
      if (substr($plugin_id, 0, 7) != 'entity:') {
        continue;
      }

      $entity_type = $this->entityTypeManager->getDefinition($config->getResourcePlugin()->getPluginDefinition()['entity_type']);
      $entity_types[$entity_type->id()] = $entity_type;
    }

    return $entity_types;
  }
}