<?php

namespace Drupal\ts_generator\ComponentGenerator\Api;

use Drupal\ts_generator\ComponentGenerator\SimpleGeneratorBase;
use Drupal\ts_generator\Plugin\TsGenerator\ApiProvider\RestView;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class RestViewGenerator extends SimpleGeneratorBase {

  public function supportsGeneration($object) {
    return ($object instanceof RestView);
  }

  public function generate($object, Settings $settings, Result $result) {
    /** @var \Drupal\ts_generator\Plugin\TsGenerator\ApiProvider\RestView $object */
    $this->generator->generate($object->getView(), $settings, $result);
  }
}
