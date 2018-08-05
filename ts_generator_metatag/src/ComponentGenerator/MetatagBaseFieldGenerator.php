<?php

namespace Drupal\ts_generator_metatag\ComponentGenerator;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\metatag\MetatagTagPluginManager;
use Drupal\ts_generator\ComponentGenerator\GeneratorBase;
use Drupal\ts_generator\ComponentGenerator\PropertiesGenerator;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;
use Symfony\Component\DependencyInjection\Container;

class MetatagBaseFieldGenerator extends GeneratorBase {
  use PropertiesGenerator;
  use TagGenerator;

  /**
   * @var \Drupal\metatag\MetatagTagPluginManager
   */
  protected $metatagTagPluginManager;

  public function __construct(MetatagTagPluginManager $metatagTagPluginManager) {
    $this->metatagTagPluginManager = $metatagTagPluginManager;
  }

  /**
   * @inheritDoc
   */
  public function supportsGeneration($object) {
    if (!($object instanceof FieldDefinitionInterface)) {
      return FALSE;
    }

    return $object->getClass() == '\Drupal\metatag\Plugin\Field\MetatagEntityFieldItemList';
  }

  protected function getName($object) {
    return 'MetatagField';
  }

  protected function getProperties($object, Settings $settings, Result $result, ComponentResult $component_result) {
    return [
      'value' => $component_result->getContext('tags'),
    ];
  }

  protected function getMapping($object, $properties, Settings $settings, Result $result, ComponentResult $component_result) {
    return 'value';
  }

  protected function generateInternal($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $properties = $this->getProperties($object, $settings, $result, $componentResult);
    $mapping = $this->getMapping($object, $properties, $settings, $result, $componentResult);
    $name = $this->getName($object);

    $internal_component_result = $this->generatePropertiesComponentResult(
      $properties,
      $name,
      'Parsed' . $name,
      Container::underscore($name) . '_parser',
      $mapping,
      $settings,
      $result
    );

    $internal_component_result->setComponent(
      'parser',
      $result->setComponent(
        'parser/' . Container::underscore($name) . '_parser',
        'const ' . Container::underscore($name) . '_parser' . ' = ' .
          '(t: ' . $internal_component_result->getComponent('type') . '): ' . $internal_component_result->getComponent('target_type') . " => " .
            "t ? (" . $this->generatePropertiesParserContent($properties, $mapping) . ") : {}"
      )
    );

    return $internal_component_result;
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

  protected function preGenerate($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    parent::preGenerate($object, $settings, $result, $componentResult);

    $tags = $componentResult->getContext('tags');
    if (!isset($tags)) {
      $tags = $this->generateTags($this->metatagTagPluginManager, $settings, $result, $componentResult);
      $componentResult->setContext('tags', $tags);
    }

    $internal = $componentResult->getContext('internal');
    if (!isset($internal)) {
      $internal = $this->generateInternal($object, $settings, $result, $componentResult);
      $componentResult->setContext('internal', $internal);
    }
  }

}