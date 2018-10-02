<?php

namespace Drupal\ts_generator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class TsGeneratorApiProvider extends Plugin {

  /**
   * The Api Provider plugin ID.
   *
   * @var string
   */
  public $id;
}