<?php

namespace Drupal\ts_generator\ComponentGenerator\Api;

use Drupal\ts_generator\ComponentGenerator\GeneratorBase;
use Drupal\ts_generator\ComponentGenerator\UnionGenerator;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Plugin\TsGenerator\ApiProvider\RestEntity;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class RestEntityGenerator extends GeneratorBase {
  use UnionGenerator;

  public function supportsGeneration($object) {
    return ($object instanceof RestEntity);
  }

  protected function generateEntities($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\ts_generator\Plugin\TsGenerator\ApiProvider\RestEntity $object */

    $entity_types = $object->getEntityTypes();
    foreach ($entity_types as $entity_type) {
      $this->generator->generate($entity_type, $settings, $result);
    }
  }

  protected function getRestEntityComponents($object, Result $result) {
    /** @var \Drupal\ts_generator\Plugin\TsGenerator\ApiProvider\RestEntity $object */

    /** @var \Drupal\ts_generator\ComponentResult[] $entities */
    $entities = $result->getContext('entities');

    $rest_entity_type_components = [];
    $rest_entity_types = $object->getEntityTypes();
    foreach ($rest_entity_types as $entity_type_id => $entity_type) {
      $rest_entity_type_components[$entity_type_id] = $entities[$entity_type_id];
    }

    return $rest_entity_type_components;
  }

  protected function generateType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $rest_entity_type_components = $this->getRestEntityComponents($object, $result);

    return $result->setComponent('types/RestEntity', "type RestEntity = " . $this->generateUnionObject($rest_entity_type_components, 'type'));
  }

  protected function generateTargetType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $rest_entity_type_components = $this->getRestEntityComponents($object, $result);

    return $result->setComponent('types/ParsedRestEntity', "type ParsedRestEntity = " . $this->generateUnionObject($rest_entity_type_components, 'target_type'));
  }

  protected function generateParser($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $rest_entity_type_components = $this->getRestEntityComponents($object, $result);

    return $result->setComponent(
      'parser/rest_entity_parser',
      'const rest_entity_parser = ' . $this->generateUnionParser($rest_entity_type_components, $componentResult->getComponent('type'), $componentResult->getComponent('target_type'))
    );
  }

  public function generateGuard($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $rest_entity_type_components = $this->getRestEntityComponents($object, $result);

    return $result->setComponent(
    'parser/rest_entity_guard',
    'const rest_entity_guard = ' . $this->generateUnionGuard($rest_entity_type_components, 'any', $componentResult->getComponent('type'), $componentResult->getComponent('type'))
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
}