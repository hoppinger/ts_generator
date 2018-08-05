<?php

namespace Drupal\ts_generator\ComponentGenerator\Manager;


use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\ts_generator\ComponentGenerator\GeneratorBase;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class LanguageManagerGenerator extends GeneratorBase {

  protected function generateType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var $object \Drupal\Core\Language\LanguageManagerInterface */

    $languages = $object->getLanguages();
    $langcodes = [];
    foreach ($languages as $language) {
      $langcodes[] = '\'' . $language->getId() . '\'';
    }

    return $result->setComponent('types/Language', "type Language = " . implode(' | ', $langcodes));
  }

  public function supportsGeneration($object) {
    return ($object instanceof LanguageManagerInterface);
  }
}