<?php

namespace Drupal\ts_generator;

interface ComponentResultInterface {
  /**
   * ComponentResultInterface constructor.
   * @param string[] $components
   */
  public function __construct(array $components = []);

  /**
   * @param string $key
   * @param string $component
   */
  public function setComponent(string $key, string $component);

  /**
   * @param string $key
   * @return bool|string
   */
  public function getComponent(string $key);

  public function getContext(string $key);
  public function setContext(string $key, $data);
}