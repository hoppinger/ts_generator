<?php

namespace Drupal\ts_generator\ComponentGenerator;

use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

trait NoopParserGenerator {
  /**
   * @param \Drupal\ts_generator\Settings $settings
   * @param \Drupal\ts_generator\Result $result
   * @param \Drupal\ts_generator\ComponentResultInterface $componentResult
   * @return string
   */
  protected function generateNoopParser(Settings $settings, Result $result, ComponentResult $componentResult) {
    return $result->setComponent('parser/noop_parser', 'const noop_parser = <T>(t: T): T => t');
  }
}