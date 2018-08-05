<?php

namespace Drupal\ts_generator\ComponentGenerator\Field;

use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class PathFieldGenerator extends FieldGenerator {
  protected $supportedFieldType = ['path'];

  public function getItemMapping($object, $properties, Settings $settings, Result $result, ComponentResult $componentResult) {
    return 'alias';
  }

  public function generateType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    return parent::generateType($object, $settings, $result, $componentResult) . " | undefined";
  }
}