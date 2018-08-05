<?php

namespace Drupal\ts_generator_metatag\ComponentGenerator;


use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\metatag\MetatagTagPluginManager;
use Drupal\ts_generator\ComponentGenerator\Field\FieldGenerator;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class MetatagFieldGenerator extends FieldGenerator {
  use TagGenerator;

  protected $supportedFieldType = ['metatag'];

  /**
   * @var \Drupal\metatag\MetatagTagPluginManager
   */
  protected $metatagTagPluginManager;

  public function __construct(FieldTypePluginManagerInterface $fieldTypePluginManager, MetatagTagPluginManager $metatagTagPluginManager) {
    parent::__construct($fieldTypePluginManager);
    $this->metatagTagPluginManager = $metatagTagPluginManager;
  }

  protected function getItemProperties($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    return [
      'value' => $componentResult->getContext('tags'),
    ];
  }

  protected function preGenerate($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $tags = $componentResult->getContext('tags');
    if (!isset($tags)) {
      $tags = $this->generateTags($this->metatagTagPluginManager, $settings, $result, $componentResult);
      $componentResult->setContext('tags', $tags);
    }

    parent::preGenerate($object, $settings, $result, $componentResult);
  }
}
