<?php

namespace Drupal\ts_generator\ComponentGenerator\Data;

use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class DateTimeGenerator extends DataGeneratorBase {
  protected $supportedDataType = ['timestamp', 'datetime_iso8601'];

  protected function getDataType($object, Settings $settings, Result $result, ComponentResult $component_result) {
    return 'string';
  }

  protected function generateTargetType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $object */
    $componentResult->setComponent('base_target_type', ':/moment/Moment*:.Moment');
    return $object->isRequired() ? $componentResult->getComponent('base_target_type') : $componentResult->getComponent('base_target_type') . ' | null';
  }

  protected function generateParser($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $object */
    $base_parser = $result->setComponent(
      'parser/moment_parser',
      'const moment_parser = (t: string): :/moment/Moment*:.Moment => :/moment/Moment*:(t)'
    );
    $componentResult->setComponent('base_parser', $base_parser);

    if ($object->isRequired()) {
      $name = 'required_date_time_parser';
      return $result->setComponent('parser/' . $name, 'const ' . $name . ' = (t: string): :/moment/Moment*:.Moment => ' . $base_parser . '(t)');
    } else {
      $name = 'optional_date_time_parser';
      return $result->setComponent('parser/' . $name, 'const ' . $name . ' = (t: string | null): :/moment/Moment*:.Moment | null => t ? ' . $base_parser . '(t) : null');
    }

  }
}