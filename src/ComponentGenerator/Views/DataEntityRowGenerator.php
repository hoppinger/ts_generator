<?php

namespace Drupal\ts_generator\ComponentGenerator\Views;


use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\Plugin\views\row\DataEntityRow;
use Drupal\ts_generator\ComponentGenerator\SimpleGeneratorBase;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class DataEntityRowGenerator extends SimpleGeneratorBase {
  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function supportsGeneration($object) {
    return ($object instanceof DataEntityRow);
  }

  public function generate($object, Settings $settings, Result $result) {
    /** @var DataEntityRow $object */
    $entity_type = $this->entityTypeManager->getDefinition($object->getEntityTypeId());
    return $this->generator->generate($entity_type, $settings, $result);
  }
}