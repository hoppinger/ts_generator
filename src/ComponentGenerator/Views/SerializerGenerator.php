<?php

namespace Drupal\ts_generator\ComponentGenerator\Views;

use Drupal\rest\Plugin\views\style\Serializer;
use Drupal\ts_generator\ComponentGenerator\GeneratorBase;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;
use Symfony\Component\DependencyInjection\Container;

class SerializerGenerator extends GeneratorBase {

  public function supportsGeneration($object) {
    return ($object instanceof Serializer);
  }

  protected function getName($object) {
    /** @var Serializer $object */

    return 'View' . Container::camelize($object->view->id()) . Container::camelize($object->view->current_display);
  }

  protected function generateRow($object, Settings $settings, Result $result) {
    /** @var Serializer $object */
    return $this->generator->generate($object->view->rowPlugin, $settings, $result);
  }

  protected function generateType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var Serializer $object */
    $name = $this->getName($object);

    return $result->setComponent(
      'types/' . $name,
      'type ' . $name . " = " . $componentResult->getContext('row')->getComponent('type') . "[]"
    );
  }

  protected function generateTargetType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var Serializer $object */
    $name = 'Parsed' . $this->getName($object);

    return $result->setComponent(
      'types/' . $name,
      'type ' . $name . " = :/immutable/List:<" . $componentResult->getContext('row')->getComponent('target_type') . ">"
    );
  }

  protected function generateParser($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var Serializer $object */
    $name =  Container::underscore($this->getName($object)) . '_parser';

    $type = $this->generateType($object, $settings, $result, $componentResult);
    $target_type = $this->generateTargetType($object, $settings, $result, $componentResult);
    $row_target_type = $componentResult->getContext('row')->getComponent('target_type');
    $row_parser = $componentResult->getContext('row')->getComponent('parser');
    $row_guard = $componentResult->getContext('row')->getComponent('guard');

    return $result->setComponent(
      'parser/' . $name,
      'const ' . $name . " =\n  (f: " . $type . '): ' . $target_type . " =>\n    :/immutable/List:<" . $row_target_type . '>(f' . ($row_guard ? '.filter(i => ' .  $row_guard . '(i))' : '') . '.map(i => ' . $row_parser . '(i)))'
    );
  }

  protected function preGenerate($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    parent::preGenerate($object, $settings, $result, $componentResult);

    $row = $componentResult->getContext('row');
    if (!isset($row)) {
      $row = $this->generateRow($object, $settings, $result);
      $componentResult->setContext('row', $row);
    }
  }
}