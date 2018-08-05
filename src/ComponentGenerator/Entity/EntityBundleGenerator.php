<?php

namespace Drupal\ts_generator\ComponentGenerator\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Entity\Exception\AmbiguousEntityClassException;
use Drupal\Core\Entity\Exception\NoCorrespondingEntityClassException;
use Drupal\ts_generator\ComponentGenerator\GeneratorBase;
use Drupal\ts_generator\ComponentGenerator\PropertiesGenerator;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;
use Symfony\Component\DependencyInjection\Container;

class EntityBundleGenerator extends GeneratorBase {
  use PropertiesGenerator;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, EntityTypeRepositoryInterface $entityTypeRepository) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeRepository = $entityTypeRepository;
  }

  public function supportsGeneration($object) {
    if (!($object instanceof ConfigEntityInterface)) {
      return FALSE;
    }

    try {
      $entity_type_id = $this->entityTypeRepository->getEntityTypeFromClass(get_class($object));
    } catch (AmbiguousEntityClassException $e) {
      return FALSE;
    } catch (NoCorrespondingEntityClassException $e) {
      return FALSE;
    }

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundle_of = $entity_type->getBundleOf();

    if (empty($bundle_of)) {
      return FALSE;
    } else {
      return TRUE;
    }
  }

  protected function getEntityId($object) {
    $bundle_entity_type_id = $this->entityTypeRepository->getEntityTypeFromClass(get_class($object));
    $bundle_entity_type = $this->entityTypeManager->getDefinition($bundle_entity_type_id);
    return $bundle_entity_type->getBundleOf();
  }

  protected function getName($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $object */
    $entity_type_id = $this->getEntityId($object);
    return Container::camelize($entity_type_id) . Container::camelize($object->id());
  }

  protected function getProperties($object, Settings $settings, Result $result, ComponentResult $component_result) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $object */
    $entity_type_id = $this->getEntityId($object);
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $bundle = $object->id();

    $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

    $_bundle_type = $this->generator->generate($base_field_definitions[$entity_type->getKey('bundle')], $settings, $result);
    $bundle_type = $_bundle_type->getComponent('wrapper_type') . '<' . $_bundle_type->getComponent('specific_item_type') . "<" . json_encode($bundle) . ">>";

    $properties = [
      $entity_type->getKey('bundle') => new ComponentResult([
        'type' => $bundle_type,
        'target_type' => json_encode($bundle),
        'parser' => '((_: any): ' . json_encode($bundle) . ' => ' . json_encode($bundle) . ')',
      ])
    ];

    foreach ($field_definitions as $key => $field_definition) {
      if ($field_definition->isInternal()) {
        continue;
      }

      $property_value = $this->generator->generate($field_definition, $settings, $result);

      if (!empty($base_field_definitions[$key])) {
        $base_field_property_value = $this->generator->generate($base_field_definitions[$key], $settings, $result);
        if ($base_field_property_value->getComponent('type') == $property_value->getComponent('type')) {
          continue;
        }
      }

      $properties[$key] = $property_value;
    }

    return $properties;
  }

  protected function getMapping($object, Settings $settings, Result $result, ComponentResult $component_result) {
    $properties = $this->getProperties($object, $settings, $result, $component_result);

    $entity_type_id = $this->getEntityId($object);
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $entities = $result->getContext('entities');

    if (!$entities || !isset($entities[$entity_type_id])) {
      return NULL;
    }

    $mapping = [
      $entities[$entity_type_id]->getContext('base'),
      'bundle' => $entity_type->getKey('bundle'),
    ];

    foreach ($properties as $key => $property) {
      if (array_search($key, $mapping) === FALSE) {
        $mapping[$key] = $key;
      }
    }

    return $mapping;
  }

  public function generateTargetType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $object */
    return $componentResult->getContext('internal')->getComponent('target_type');
  }

  public function generateParser($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $object */
    return $componentResult->getContext('internal')->getComponent('parser');
  }

  public function generateType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $object */
    return $componentResult->getContext('internal')->getComponent('type');
  }

  protected function generateInternal($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $properties = $this->getProperties($object, $settings, $result, $componentResult);
    $mapping = $this->getMapping($object, $settings, $result, $componentResult);
    $name = $this->getName($object, $settings, $result, $componentResult);

    return $this->generatePropertiesComponentResult(
      $properties,
      $name,
      'Parsed' . $name,
      Container::underscore($name) . '_parser',
      $mapping,
      $settings,
      $result
    );
  }

  protected function preGenerate($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    parent::preGenerate($object, $settings, $result, $componentResult);

    $internal = $componentResult->getContext('internal');
    if (!isset($internal)) {
      $internal = $this->generateInternal($object, $settings, $result, $componentResult);
      $componentResult->setContext('internal', $internal);
    }
  }

  public function generateGuard($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $object */
    $name = Container::underscore($this->getName($object, $settings, $result, $componentResult)) . '_guard';

    $entity_type_id = $this->getEntityId($object);
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    return $result->setComponent(
      'parser/' . $name,
      'const ' . $name . ' = (t: :types/' . Container::camelize($entity_type_id) . ':): t is ' . $componentResult->getComponent('type') .
        " => :parser/" . $entity_type_id . '_bundle_parser:' . '(t.' . $entity_type->getKey('bundle') . ') == ' . json_encode($object->id())
    );
  }

  protected function postGenerate($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $object */

    if ($settings->generateParser()) {
      $componentResult->setComponent('guard', $this->generateGuard($object, $settings, $result, $componentResult));
    }
  }
}