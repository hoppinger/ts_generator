<?php

namespace Drupal\ts_generator;

interface GeneratorInterface {
  /**
   * @param $object
   * @param \Drupal\ts_generator\Settings $settings
   * @param \Drupal\ts_generator\Result $result
   * @return \Drupal\ts_generator\ComponentResultInterface
   */
  public function generate($object, Settings $settings, Result $result);

  /**
   * @param $object
   * @return bool
   */
  public function supportsGeneration($object);
}