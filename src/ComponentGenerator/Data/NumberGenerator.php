<?php

namespace Drupal\ts_generator\ComponentGenerator\Data;

use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class NumberGenerator extends DataGeneratorBase {
  protected $supportedDataType = ['integer'];

  protected function getDataType($object, Settings $settings, Result $result, ComponentResult $component_result) {
    return 'number';
  }
}