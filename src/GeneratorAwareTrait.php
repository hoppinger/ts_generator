<?php

namespace Drupal\ts_generator;

trait GeneratorAwareTrait {
  /**
   * @var \Drupal\ts_generator\GeneratorInterface
   */
  protected $generator;

  /**
   * @param GeneratorInterface $generator
   */
  public function setGenerator(GeneratorInterface $generator) {
    $this->generator = $generator;
  }
}