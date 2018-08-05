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
    return $this->data[$key];
  }

  public function getEntities() {
    if (!is_array($this->data['entities'])) {
      return [$this->data['entities']];
    }

    if (!isset($this->data['entities']['input']) && !isset($this->data['entities']['extra'])) {
      return $this->data['entities'];
    }

    return array_merge(
      isset($this->data['entities']['input']) ? $this->data['entities']['input'] : [],
      isset($this->data['entities']['extra']) ? $this->data['entities']['extra'] : []
    );
  }

  public function getInputEntities() {
    if (!is_array($this->data['entities'])) {
      return [$this->data['entities']];
    }

    if (!isset($this->data['entities']['input']) && !isset($this->data['entities']['extra'])) {
      return $this->data['entities'];
    }

    return isset($this->data['entities']['input']) ? $this->data['entities']['input'] : [];
  }

  public function generateParser() {
    return !empty($this->data['generate_parser']);
  }
}
