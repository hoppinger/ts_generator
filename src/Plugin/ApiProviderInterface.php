<?php

namespace Drupal\ts_generator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

interface ApiProviderInterface extends PluginInspectionInterface {
  public function generate(Settings $settings, Result $result);
}
