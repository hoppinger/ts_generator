<?php

namespace Drupal\ts_generator\ComponentGenerator\Data;

use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class StringGenerator extends DataGeneratorBase {
  protected $supportedDataType = ['string', 'email', 'uri'];

  protected function getDataType($object, Settings $settings, Result $result, ComponentResult $component_result) {
    return 'string';
  }
}