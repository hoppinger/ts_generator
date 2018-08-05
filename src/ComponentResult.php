<?php

namespace Drupal\ts_generator;

class ComponentResult implements ComponentResultInterface {
  /**
   * @var string[]
   */
  protected $components;
  protected $context;

  public function __construct(array $components = [], $context = []) {
    $this->components = $components;
    $this->context = $context;
  }

  /**
   * @param $key string
   * @param $component string
   */
  public function setComponent(string $key, string $component) {
    $this->components[$key] = $component;
    return $component;
  }

  public function hasComponent(string $key) {
    return isset($this->components[$key]);
  }

  /**
   * @param $key
   * @return bool|string
   */
  public function getComponent(string $key) {
    return isset($this->components[$key]) ? $this->components[$key] : FALSE;
  }


  public function getContext(string $key) {
    return isset($this->context[$key]) ? $this->context[$key] : NULL;
  }

  public function setContext(string $key, $value) {
    $this->context[$key] = $value;
  }
}