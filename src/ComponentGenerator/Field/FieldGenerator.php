<?php

namespace Drupal\ts_generator\ComponentGenerator\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\ts_generator\ComponentGenerator\GeneratorBase;
use Drupal\ts_generator\ComponentGenerator\PropertiesGenerator;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;
use Symfony\Component\DependencyInjection\Container;

class FieldGenerator extends GeneratorBase {
  use PropertiesGenerator;

  protected $supportedFieldType;
  protected $needsItemGuard = false;

  /**
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  public function __construct(FieldTypePluginManagerInterface $fieldTypePluginManager) {
    $this->fieldTypePluginManager = $fieldTypePluginManager;
  }

  public function supportsGeneration($object) {
    if (!($object instanceof FieldDefinitionInterface)) {
      return FALSE;
    }

    $supported = (array) $this->supportedFieldType;

    return !isset($this->supportedFieldType) || in_array($object->getType(), $supported);
  }

  protected function getName($object, Settings $settings, Result $result, ComponentResult $component_result) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $object */
    return Container::camelize($object->getType());
  }

  protected function getItemName($object, Settings $settings, Result $result, ComponentResult $component_result) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $object */
    return $this->getName($object, $settings, $result, $component_result) . 'Item';
  }

  protected function getItemProperties($object, Settings $settings, Result $result, ComponentResult $component_result) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $object */
    $storage_object = $object->getFieldStorageDefinition();

    $properties = [];
    foreach ($storage_object->getPropertyDefinitions() as $key => $property) {
      if ($property->isInternal()) {
        continue;
      }

      $properties[$key] = $this->generator->generate($property, $settings, $result);
    }

    return $properties;
  }

  public function getItemMapping($object, $properties, Settings $settings, Result $result, ComponentResult $componentResult) {
    if (count($properties) == 1) {
      $property_keys = array_keys($properties);
      return $property_keys[0];
    }

    return NULL;
  }

  protected function generateItem($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $name = $this->getItemName($object, $settings, $result, $componentResult);

    $properties = $this->getItemProperties($object, $settings, $result, $componentResult);
    $mapping = $this->getItemMapping($object, $properties, $settings, $result, $componentResult);

    return $this->generatePropertiesComponentResult(
      $properties,
      $name,
      'Parsed' . $name,
      Container::underscore($name) . '_parser',
      $mapping,
      $settings,
      $result,
      $this->needsItemGuard ? (Container::underscore($name) . '_guard') : null
    );
  }

  protected function generateWrapperType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $object */
    return $result->setComponent('types/FieldItemList', "type FieldItemList<T> = T[]");
  }

  public function generateType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $object */
    $componentResult->setComponent('wrapper_type', $this->generateWrapperType($object, $settings, $result, $componentResult));
    return $componentResult->getComponent('wrapper_type') . '<' . $componentResult->getContext('item')->getComponent('type') . '>';
  }

  public function generateTargetType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $object */

    $item_target_type = $componentResult->getContext('item')->getComponent('target_type');
    if ($object->getFieldStorageDefinition()->getCardinality() == 1) {
      return $object->isRequired() ? $item_target_type : ($item_target_type . ' | null');
    } else {
      return ':/immutable/List:<' . $item_target_type . '>';
    }
  }

  public function generateParser($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $object */

    $type = $this->generateType($object, $settings, $result, $componentResult);
    $target_type = $this->generateTargetType($object, $settings, $result, $componentResult);
    $target_type = $this->cleanupPropertyType($target_type);

    $item_target_type = $componentResult->getContext('item')->getComponent('target_type');
    $item_parser = $componentResult->getContext('item')->getComponent('parser');
    $item_guard = $componentResult->getContext('item')->getComponent('guard');

    if ($object->getFieldStorageDefinition()->getCardinality() == 1) {
      if ($object->isRequired()) {
        $name = 'singular_required_' . Container::underscore($this->getName($object, $settings, $result, $componentResult)) . '_parser';
        return $result->setComponent('parser/' . $name, 'const ' . $name . ' = (f: ' . $type . '): ' . $target_type . ' => ' . $item_parser . '(f[0])');
      } else {
        $name = 'singular_optional_' . Container::underscore($this->getName($object, $settings, $result, $componentResult)) . '_parser';
        return $result->setComponent('parser/' . $name, 'const ' . $name . ' = (f: ' . $type . '): ' . $target_type . ' => f && f.length > 0' . ($item_guard ? ' && ' .  $item_guard . '(f[0])' : '') . ' ? ' . $item_parser . '(f[0]) : null');
      }
    } else {
      // parse from FieldItemList to Immutable.List
      $name = 'plural_' . Container::underscore($this->getName($object, $settings, $result, $componentResult)) . '_parser';
      return $result->setComponent('parser/' . $name, 'const ' . $name . " =\n  (f: " . $type . '): ' . $target_type . " =>\n    :/immutable/List:<" . $item_target_type . '>(f' . ($item_guard ? '.filter(i => ' .  $item_guard . '(i))' : '') . '.map(i => ' . $item_parser . '(i)))');
    }
  }

  protected function preGenerate($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    parent::preGenerate($object, $settings, $result, $componentResult);

    $item = $componentResult->getContext('item');
    if (!isset($item)) {
      $item = $this->generateItem($object, $settings, $result, $componentResult);
      $componentResult->setContext('item', $item);
    }
  }
}