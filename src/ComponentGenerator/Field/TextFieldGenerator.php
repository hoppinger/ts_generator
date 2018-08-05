<?php

namespace Drupal\ts_generator\ComponentGenerator\Field;

use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class TextFieldGenerator extends FieldGenerator {
  protected $supportedFieldType = ['text', 'text_long'];

  protected function getItemName($object, Settings $settings, Result $result, ComponentResult $component_result) {
    return 'TextItem';
  }
}