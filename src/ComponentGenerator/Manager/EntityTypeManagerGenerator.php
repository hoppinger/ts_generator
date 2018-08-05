<?php

namespace Drupal\ts_generator\ComponentGenerator\Manager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ts_generator\ComponentGenerator\GeneratorBase;
use Drupal\ts_generator\ComponentGenerator\UnionGenerator;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class EntityTypeManagerGenerator extends GeneratorBase {
  use UnionGenerator;

  protected function generateEntities($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $object */

    $entity_ids = $settings->getEntities();
    foreach ($entity_ids as $entity_id) {
      $entity_type = $object->getDefinition($entity_id);
      $this->generator->generate($entity_type, $settings, $result);
    }
  }

  protected function generateType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $object */

    /** @var \Drupal\ts_generator\ComponentResult[] $entities */
    $entities = $result->getContext('entities');

    $input_entities = [];
    foreach ($settings->getInputEntities() as $entity_id) {
      $input_entities[$entity_id] = $entities[$entity_id];
    }

    $componentResult->setComponent(
      'input_type',
      $result->setComponent('types/InputEntity', "type InputEntity = " . $this->generateUnionObject($input_entities, 'type'))
    );

    return $result->setComponent('types/Entity', "type Entity = " . $this->generateUnionObject($entities, 'type'));
  }

  protected function generateTargetType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $object */

    /** @var \Drupal\ts_generator\ComponentResult[] $entities */
    $entities = $result->getContext('entities');

    $input_entities = [];
    foreach ($settings->getInputEntities() as $entity_id) {
      $input_entities[$entity_id] = $entities[$entity_id];
    }

    $componentResult->setComponent(
      'input_target_type',
      $result->setComponent('types/ParsedInputEntity', "type ParsedInputEntity = " . $this->generateUnionObject($input_entities, 'target_type'))
    );

    return $result->setComponent('types/ParsedEntity', "type ParsedEntity = " . $this->generateUnionObject($entities, 'target_type'));
  }

  protected function generateParser($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $entities = $result->getContext('entities');

    $input_entities = [];
    foreach ($settings->getInputEntities() as $entity_id) {
      $input_entities[$entity_id] = $entities[$entity_id];
    }

    $componentResult->setComponent(
      'input_parser',
      $result->setComponent(
        'parser/input_entity_parser',
        'const input_entity_parser = ' . $this->generateUnionParser($input_entities, $componentResult->getComponent('input_type'), $componentResult->getComponent('input_target_type'))
      )
    );

    return $result->setComponent(
      'parser/entity_parser',
      'const entity_parser = ' . $this->generateUnionParser($entities, $componentResult->getComponent('type'), $componentResult->getComponent('target_type'))
    );
  }

  public function generateGuard($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $entities = $result->getContext('entities');

    $input_entities = [];
    foreach ($settings->getInputEntities() as $entity_id) {
      $input_entities[$entity_id] = $entities[$entity_id];
    }

    $componentResult->setComponent(
      'input_guard',
      $result->setComponent(
        'parser/input_entity_guard',
        'const input_entity_guard = ' . $this->generateUnionGuard($input_entities, 'any', $componentResult->getComponent('input_type'), $componentResult->getComponent('input_type'))
      )
    );

    return $result->setComponent(
    'parser/entity_guard',
    'const entity_guard = ' . $this->generateUnionGuard($entities, 'any', $componentResult->getComponent('type'), $componentResult->getComponent('type'))
    );
  }

  protected function preGenerate($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $this->generateEntities($object, $settings, $result, $componentResult);
  }

  protected function postGenerate($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $object */
    parent::postGenerate($object, $settings, $result, $componentResult);

    if ($settings->generateParser()) {
      $componentResult->setComponent('guard', $this->generateGuard($object, $settings, $result, $componentResult));
    }
  }

  public function supportsGeneration($object) {
    return ($object instanceof EntityTypeManagerInterface);
  }

}