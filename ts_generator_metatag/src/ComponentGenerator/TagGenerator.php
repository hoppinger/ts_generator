<?php

namespace Drupal\ts_generator_metatag\ComponentGenerator;

use Drupal\metatag\MetatagTagPluginManager;
use Drupal\ts_generator\ComponentGenerator\PropertiesGenerator;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

trait TagGenerator {

  protected function generateTags(MetatagTagPluginManager $metatagTagPluginManager, Settings $settings, Result $result, ComponentResult $componentResult) {
    $tags = $metatagTagPluginManager->getDefinitions();

    $properties = [];
    foreach ($tags as $tag) {
      $key = $tag['name'];

      $properties[$key . '?'] = 'string';
    }

    return $result->setComponent(
      'types/Metatags',
      'type Metatags = ' . $this->formatObject([], $properties)
    );
  }
}