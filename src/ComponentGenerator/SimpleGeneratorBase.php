<?php

namespace Drupal\ts_generator\ComponentGenerator;

use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\GeneratorAwareInterface;
use Drupal\ts_generator\GeneratorAwareTrait;
use Drupal\ts_generator\GeneratorInterface;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

abstract class SimpleGeneratorBase implements GeneratorInterface, GeneratorAwareInterface {
  use GeneratorAwareTrait, NoopParserGenerator;
}