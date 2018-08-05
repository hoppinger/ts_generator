<?php

namespace Drupal\ts_generator\ComponentGenerator\Field;

use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class LanguageFieldGenerator extends FieldGenerator {
  protected $supportedFieldType = ['language'];

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;


  public function __construct(FieldTypePluginManagerInterface $fieldTypePluginManager, LanguageManagerInterface $languageManager) {
    parent::__construct($fieldTypePluginManager);

    $this->languageManager = $languageManager;
  }

  protected function generateLanguageObject($object, Settings $settings, Result $result) {
    return $this->generator->generate($this->languageManager, $settings, $result)->getComponent('type');
  }

  protected function getItemProperties($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $properties = parent::getItemProperties($object, $settings, $result, $componentResult);

    $properties['value'] = $this->generateLanguageObject($object, $settings, $result);
    $componentResult->setComponent('language_type', $properties['value']);

    return $properties;
  }
}