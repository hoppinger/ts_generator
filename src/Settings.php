<?php

namespace Drupal\ts_generator;

use Drupal\Component\Utility\NestedArray;
use Symfony\Component\Yaml\Yaml;

class Settings {
  protected $data;

  public function __construct(array $data = []) {
    $this->data = NestedArray::mergeDeep(
      static::defaultData(),
      $data
    );
  }

  public static function loadFile($filename) {
    $data = Yaml::parseFile($filename);

    return new static($data);
  }

  public static function defaultData() {
    return [];
  }

  public function writeToFile($filename) {
    $data = Yaml::dump($this->data);
    file_put_contents($filename, $data);
  }

  public function get($key) {
    return !empty($this->data[$key]) ? $this->data[$key] : NULL;
  }

  public function getPlugins() {
    if (empty($this->data['plugins'])) {
      return [];
    }

    $plugins = [];
    foreach ($this->data['plugins'] as $k => $v) {
      if (!empty($v) && !is_array($v)) {
        $plugins[$v] = [];
        continue;
      }

      foreach ($v as $p => $s) {
        $plugins[$p] = $s;
      }
    }

    return $plugins;
  }

  public function generateParser() {
    return !empty($this->data['generate_parser']);
  }
}
