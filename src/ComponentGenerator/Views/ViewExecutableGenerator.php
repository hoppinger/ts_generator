<?php

namespace Drupal\ts_generator\ComponentGenerator\Views;

use Drupal\ts_generator\ComponentGenerator\GeneratorBase;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;
use Drupal\views\ViewExecutable;

class ViewExecutableGenerator extends GeneratorBase {
  public function supportsGeneration($object) {
    return ($object instanceof ViewExecutable);
  }

  protected function generateStyle($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var ViewExecutable $object */
    return $this->generator->generate($object->getStyle(), $settings, $result);
  }

  protected function generateType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var ViewExecutable $object */
    return 'string';
  }

  protected function preGenerate($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    parent::preGenerate($object, $settings, $result, $componentResult);

    $style = $componentResult->getContext('style');
    if (!isset($style)) {
      $style = $this->generateStyle($object, $settings, $result, $componentResult);
      $componentResult->setContext('style', $style);
    }
  }
}