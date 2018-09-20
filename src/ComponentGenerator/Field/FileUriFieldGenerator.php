<?php

namespace Drupal\ts_generator\ComponentGenerator\Field;

use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class FileUriFieldGenerator extends FieldGenerator {
  protected $supportedFieldType = ['file_uri'];

  public function getItemMapping($object, $properties, Settings $settings, Result $result, ComponentResult $componentResult) {
    return 'url';
  }
}
