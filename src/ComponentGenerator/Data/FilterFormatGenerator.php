<?php

namespace Drupal\ts_generator\ComponentGenerator\Data;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

class FilterFormatGenerator extends DataGeneratorBase {
  protected $supportedDataType = 'filter_format';

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  protected function generateFilterFormatObject($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    $filter_format_storage = $this->entityTypeManager->getStorage('filter_format');
    $filter_formats = $filter_format_storage->loadMultiple();

    $filter_format_keys = [];
    foreach ($filter_formats as $filter_format) {
      $filter_format_keys[] = '\'' . $filter_format->id() . '\'';
    }

    $filter_format_component = $result->setComponent('types/FilterFormat', "type FilterFormat = " . implode(' | ', $filter_format_keys));
    return $filter_format_component;
  }

  protected function getDataType($object, Settings $settings, Result $result, ComponentResult $componentResult) {
    return $this->generateFilterFormatObject($object, $settings, $result, $componentResult);
  }
}