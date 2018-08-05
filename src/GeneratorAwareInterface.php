<?php

namespace Drupal\ts_generator;


interface GeneratorAwareInterface {
  public function setGenerator(GeneratorInterface $generator);
}