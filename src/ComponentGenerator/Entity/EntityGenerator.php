<?php

namespace Drupal\ts_generator\ComponentGenerator\Entity;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ts_generator\ComponentGenerator\GeneratorBase;
use Drupal\ts_generator\ComponentGenerator\PropertiesGenerator;
use Drupal\ts_generator\ComponentGenerator\UnionGenerator;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;
use Symfony\Component\DependencyInjection\Container;

class EntityGenerator extends GeneratorBase {
  use PropertiesGenerator;
  use UnionGenerator;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  public function supportsGeneration($object) {
    return $object instanceof EntityTypeInterface;
  }

  /**
   * @param $object
   * @return array|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getBundles($object) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $object */
    if ($bundle_entity_type = $object->getBundleEntityType()) {
      $bundles = [];
      foreach ($this->entityTypeManager->getStorage($bundle_entity_type)->loadMultiple() as $entity) {
        $bundles[] = $entity;
      }

      return $bundles;
    }

    return FALSE;
  }

  protected function getBaseName($object) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $object */
    return Container::camelize($object->id()) . "Base";
  }

  protected function getBaseMapping($object, array $properties, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $object */
    $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($object->id());

    $mapping = [
      'entity_type' => new ComponentResult([
        'target_type' => json_encode($object->id()),
        'parser' => '((_: any): ' . json_encode($object->id()) . ' => ' . json_encode($object->id()) . ')',
      ])
    ];

    $custom_mapped_keys = [
      'id' => 'id',
      'status' => 'status',
      'uid' => 'author',
      'langcode' => 'language',
      'label' => 'label',
    ];

    foreach ($custom_mapped_keys as $key => $target_property_key) {
      if ($property_key = $object->getKey($key)) {
        $mapping[$target_property_key] = $property_key;
      }
    }

    $ignored_keys = array_filter([
      $object->getKey('revision'),
      $object->getKey('uuid'),
      $object->getKey('bundle'),
    ]);

    foreach ($properties as $key => $property) {
      if (array_search($key, $mapping) === FALSE && !in_array($key, $ignored_keys)) {
        $mapping[$key] = $key;
      }
    }

    return $mapping;
  }

  protected function getBaseProperties($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $object */
    $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($object->id());

    $properties = [];
    foreach ($base_field_definitions as $key => $field_definition) {
      if ($field_definition->isInternal()) {
        continue;
      }
      $properties[$key] = $this->generator->generate($field_definition, $settings, $result);

      if ($key == $object->getKey('bundle')) {
        $properties[$key] = clone $properties[$key];
        $properties[$key]->setComponent('target_type', 'string');
        $properties[$key]->setComponent('parser', $componentResult->setComponent('bundle_parser', $result->setComponent(
          'parser/' . $object->id() . '_bundle_parser',
          'const ' .  $object->id() . '_bundle_parser = (f: ' . $properties[$key]->getComponent('type') . "): string => f[0].target_id"
        )));
      }
    }

    if (!$this->getBundles($object)) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($object->id(), $object->id());
      foreach ($field_definitions as $key => $field_definition) {
        if ($field_definition->isInternal()) {
          continue;
        }

        if (isset($base_field_definitions[$key])) {
          continue;
        }

        $properties[$key] = $this->generator->generate($field_definition, $settings, $result);
      }
    }

    return $properties;
  }

  protected function generateBase($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $properties = $this->getBaseProperties($object, $settings, $result, $componentResult);

    return $this->generatePropertiesComponentResult(
      $properties,
      $this->getBaseName($object),
      'Parsed' . $this->getBaseName($object),
      Container::underscore($this->getBaseName($object)) . '_parser',
      $this->getBaseMapping($object, $properties, $settings, $result, $componentResult),
      $settings,
      $result
    );
  }

  /**
   * @param $object
   * @param Settings $settings
   * @param Result $result
   * @param ComponentResult $componentResult
   * @return array|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function generateBundles($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $bundle_list = $this->getBundles($object);
    if (!$bundle_list) {
      return FALSE;
    }

    $bundles = [];
    foreach ($bundle_list as $bundle) {
      $bundles[$bundle->id()] = $this->generator->generate($bundle, $settings, $result);
    }
    return $bundles;
  }

  public function preGenerateBundles($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $entity_type_id = $object->id();
    $bundle_list = $this->getBundles($object);
    if (!$bundle_list) {
      return FALSE;
    }

    $bundles = [];
    foreach ($bundle_list as $bundle) {
      $name = Container::camelize($entity_type_id) . Container::camelize($bundle->id());
      $bundles[$bundle->id()] = new ComponentResult([
        'type' => ':types/' . $name . ':',
        'target_type' => ':types/Parsed' . $name . ':',
        'parser' => ':parser/' . Container::underscore($name) . '_parser:',
        'guard' => ':parser/' . Container::underscore($name) . '_guard:',
      ]);
    }
    return $bundles;
  }

  public function generateTargetType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $object */

    $name = 'Parsed' . Container::camelize($object->id());
    $bundles = $componentResult->getContext('bundles');

    if ($bundles) {
      /** @var ComponentResult[] $bundles */
      return $result->setComponent('types/' . $name, 'type ' . $name . " = " . $this->generateUnionObject($bundles, 'target_type'));
    } else {
      $baseResult = $componentResult->getContext('base');

      return $result->setComponent('types/' . $name, 'type ' . $name . ' = ' . $baseResult->getComponent('target_type'));
    }
  }

  public function generateParser($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $object */
    $name =  $object->id() . '_parser';
    $bundles = $componentResult->getContext('bundles');

    if ($bundles) {
      /** @var ComponentResult[] $bundles */
      return $result->setComponent(
        'parser/' . $name,
        'const ' . $name . ' = ' . $this->generateUnionParser($bundles, $componentResult->getComponent('type'), $componentResult->getComponent('target_type'))
      );
    } else {
      $baseResult = $componentResult->getContext('base');

      return $result->setComponent(
        'parser/' . $name,
        'const ' . $name . ' = (t: ' . $componentResult->getComponent('type') . '): ' . $componentResult->getComponent('target_type') . " => " . $baseResult->getComponent('parser') . '(t)'
      );
    }
  }

  public function generateType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $object */
    $name = Container::camelize($object->id());
    $bundles = $componentResult->getContext('bundles');

    if ($bundles) {
      /** @var ComponentResult[] $bundles */
      return $result->setComponent('types/' . $name, 'type ' . $name . " = " . $this->generateUnionObject($bundles, 'type'));
    } else {
      $baseResult = $componentResult->getContext('base');

      return $result->setComponent('types/' . $name, 'type ' . $name . ' = ' . $baseResult->getComponent('type'));
    }
  }

  public function generateGuard($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $object */
    $name = $name = $object->id() . '_guard';
    $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($object->id());

    $conditions = [];
    foreach ($base_field_definitions as $key => $field_definition) {
      if ($field_definition->isInternal() || $field_definition->getType() == 'path') {
        continue;
      }
      $conditions[] = '  (t as any).' . $key . ' !== undefined';

      if ($key == $object->getKey('bundle') && ($bundle_entity_type = $object->getBundleEntityType())) {
        $conditions[] = '  (t as any).' . $key .'[0].target_type == ' . json_encode($bundle_entity_type);
      }
    }

    return $result->setComponent(
      'parser/' . $name,
      'const ' . $name . ' = (t: :types/Entity:): t is ' . $componentResult->getComponent('type') .
      " => (\n" . implode(" &&\n", $conditions) . "\n)"
    );
  }

  /**
   * @param $object
   * @param Settings $settings
   * @param Result $result
   * @param ComponentResult $componentResult
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function preGenerate($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $object */
    parent::preGenerate($object, $settings, $result, $componentResult);

    if (!$componentResult->hasComponent('type')) {
      $name = Container::camelize($object->id());
      $componentResult->setComponent('type', ':types/' . $name . ':');
    }

    if ($settings->generateParser()) {
      if (!$componentResult->hasComponent('target_type')) {
        $name = 'Parsed' . Container::camelize($object->id());
        $componentResult->setComponent('target_type', ':types/' . $name . ':');
      }
      if (!$componentResult->hasComponent('parser')) {
        $name = $name = $object->id() . '_parser';
        $componentResult->setComponent('parser', ':parser/' . $name . ':');
      }
      if (!$componentResult->hasComponent('guard')) {
        $name = $name = $object->id() . '_guard';
        $componentResult->setComponent('guard', ':parser/' . $name . ':');
      }
    }

    $base = $componentResult->getContext('base');
    if (!isset($base)) {
      $base = $this->generateBase($object, $settings, $result, $componentResult);
      $componentResult->setContext('base', $base);
    }

    $bundles = $componentResult->getContext('bundles');
    if (!isset($bundles)) {
      $bundles = $this->preGenerateBundles($object, $settings, $result, $componentResult);
      $componentResult->setContext('bundles', $bundles);
      $bundles = $this->generateBundles($object, $settings, $result, $componentResult);
      $componentResult->setContext('bundles', $bundles);
    }
  }

  protected function postGenerate($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $object */
    parent::postGenerate($object, $settings, $result, $componentResult);

    if ($settings->generateParser()) {
      $componentResult->setComponent('guard', $this->generateGuard($object, $settings, $result, $componentResult));
    }
  }

  public function generate($object, Settings $settings, Result $result) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $object */

    $componentResult = new ComponentResult();

    $name = $object->id();
    $context = $result->getContext('entities');
    if (!isset($context)) {
      $context = [];
    }

    if (isset($context[$name])) {
      return $context[$name];
    }

    $context[$name] = $componentResult;
    $result->setContext('entities', $context);

    $this->generateWithComponentResult($object, $settings, $result, $componentResult);

    return $componentResult;
  }
}