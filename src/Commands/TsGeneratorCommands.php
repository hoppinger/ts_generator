<?php

namespace Drupal\ts_generator\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;
use Drupal\ts_generator\Generator;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class TsGeneratorCommands extends DrushCommands {

  /**
   * Generate TypeScript code for the REST Resources
   *
   * @param $filename
   *   Config file
   * @usage ts_generator-generator foo
   *   Usage description
   *
   * @command ts_generator:generate
   */
  public function generate($filename) {
    if (!file_exists($filename)) {
      $this->logger()->error(dt('The specified file does not exist.'));
    }

    $working_directory = dirname($filename);
    $settings = Settings::loadFile($filename);

    /** @var \Drupal\ts_generator\GeneratorInterface $generator */
    $generator = \Drupal::service('ts_generator.generator');

    $entity_type_manager = \Drupal::entityTypeManager();

    $result = new Result();
    $generator->generate($entity_type_manager, $settings, $result);

    $target_directory = $working_directory . '/' . $settings->get('target_directory');
    $result->write($target_directory);
  }
}
