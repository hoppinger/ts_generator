<?php

namespace Drupal\ts_generator;

class Result {
  protected $components;
  protected $context;

  public function __construct() {
    $this->components = [];
    $this->context = [];
  }

  public function setComponent($key, $definition) {
    $this->components[$key] = $definition;

    return ':' . $key . ':';
  }

  public function getComponent($key) {
    return $this->components[$key];
  }

  public function hasComponent($key) {
    return isset($this->components[$key]);
  }

  public function getComponents() {
    return $this->components;
  }

  public function getContext($key) {
    return isset($this->context[$key]) ? $this->context[$key] : NULL;
  }

  public function setContext($key, $value) {
    $this->context[$key] = $value;
  }

  private function groupedComponents() {
    $groups = [];

    foreach ($this->getComponents() as $key => $component) {
      $_key = explode('/', $key);
      $name = array_pop($_key);
      $group_name = implode('/', $_key);

      if (!isset($groups[$group_name])) {
        $groups[$group_name] = [];
      }

      $groups[$group_name][$name] = $component;
    }

    return $groups;
  }

  // a/b/c, a/b/e => ./d
  // a/b/c, a/e => ../d
  // a/b/c/d, a/e => ../../e
  private function resolveFilename($from, $to) {
    if (substr($to, 0, 1) == '/') {
      return substr($to, 1);
    }

    $i = 0;

    $_from = explode('/', $from);
    $_to = explode('/', $to);

    while ($_from[$i] && $_to[$i] && $_from[$i] == $_to[$i]) {
      $i++;
    }

    if (count($_from) - $i <= 1) {
      return './' . implode('/', array_slice($_to, $i));
    }

    return implode('/', array_fill(0, count($_from) - $i - 1, '..')) . '/' . implode('/', array_slice($_to, $i));
  }

  private function fileData() {
    $groups = $this->groupedComponents();

    $files = [];
    foreach ($groups as $file_name => $group) {
      $names = [];

      foreach (array_keys($group) as $_name) {
        $names[$file_name . '/' . $_name] = $_name;
      };

      $file = [
        'imports' => [],
        'components' => [],
      ];

      foreach ($group as $name => $component) {
        $file['components'][$name] = preg_replace_callback('/:([a-zA-Z0-9_*]*\/[a-zA-Z0-9_\/*]+):/', function($matches) use ($file_name, &$file, &$names) {
          $key = $matches[1];
          if (isset($names[$key])) {
            return $names[$key];
          }

          $_key = explode('/', $key);
          $_name = array_pop($_key);
          $_file_name = implode('/', $_key);

          if ($_file_name == $file_name) {
            return $_name;
          }

          $resolved_file_name = $this->resolveFileName($file_name, $_file_name);

          if (!isset($file['imports'][$resolved_file_name])) {
            $file['imports'][$resolved_file_name] = [];
          }

          if (substr($_name, -1, 1) == '*') {
            $target_name = substr($_name, 0, -1);
            $_name = '*';
          } else {
            $target_name = $_name;
          }

          if (array_search($target_name, $names) !== FALSE) {
            $target_name = str_replace('/', '_', $_file_name) . '_' . $target_name;
          }

          $file['imports'][$resolved_file_name][$_name] = $target_name;
          $names[$key] = $target_name;

          return $target_name;
        }, $component);
      }

      $files[$file_name] = $file;
    }

    return $files;
  }

  private function files() {
    $file_data = $this->fileData();

    $files = [];
    foreach ($file_data as $file_name => $file) {
      $imports = [];
      foreach ($file['imports'] as $_file_name => $_imports) {
        $_import_spec = [];
        foreach ($_imports as $key => $target) {
          if ($key == '*') {
            $imports[] = "import * as " . $target . " from '" . $_file_name . '\'';
          } elseif ($key == $target) {
            $_import_spec[] = '  ' . $key;
          } else {
            $_import_spec[] = '  ' . $key . ' as ' . $target;
          }
        }

        if (!empty($_import_spec)) {
          $imports[] = "import {\n" . implode(",\n", $_import_spec) . "\n} from '" . $_file_name . '\'';
        }
      }

      $components = [];
      foreach ($file['components'] as $component) {
        $components[] = 'export ' . $component;
      }

      $files[$file_name] = ($imports ? (implode("\n", $imports) . "\n\n") : '') . implode("\n\n", $components);
    }

    return $files;
  }

  public function write($target) {
    $files = $this->files();

    foreach ($files as $file_name => $content) {
      $target_file_name = $target . '/' . $file_name . '.ts';
      $target_directory = dirname($target_file_name);
      if (!is_dir($target_directory)) {
        mkdir($target_directory, 0777, TRUE);
      }
      file_put_contents($target_file_name, $content);
    }
  }
}