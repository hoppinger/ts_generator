<?php

namespace Drupal\ts_generator;

/**
 * Holds the objects that represent the end result of the generation and write them to disk.
 */
class Result {
  /**
   * The generated components of the end result
   *
   * @var string[]
   */
  protected $components;

  /**
   * Temporary storage that is shared over all component generators
   *
   * @var mixed[]
   */
  protected $context;

  /**
   * Result constructor.
   */
  public function __construct() {
    $this->components = [];
    $this->context = [];
  }

  /**
   * Get all component definitions.
   *
   * @return string[]
   */
  public function getComponents() {
    return $this->components;
  }

  /**
   * Get a component definition for a certain key.
   *
   * @param string $key
   * @return string
   */
  public function getComponent($key) {
    return $this->components[$key];
  }

  /**
   * Set a component definition for a certain key.
   *
   * Put at least one slash (/) character in the key. The part before the last slash is in the written files the
   * filename, the part after the last slash is the name of the component in that file.
   *
   * The return value is the identifier that can be used to reference this component in definition of other components.
   *
   * @param string $key
   * @param string $definition
   * @return string
   */
  public function setComponent($key, $definition) {
    $this->components[$key] = $definition;

    return ':' . $key . ':';
  }

  /**
   * Check if a component definition exists for a certain key.
   *
   * @param string $key
   * @return bool
   */
  public function hasComponent($key) {
    return isset($this->components[$key]);
  }

  /**
   * Get the context value for a certain key.
   *
   * Returns NULL when the context is not set.
   *
   * @param string $key
   * @return mixed|null
   */
  public function getContext($key) {
    return isset($this->context[$key]) ? $this->context[$key] : NULL;
  }

  /**
   * Set the context value for a certain key.
   *
   * @param string $key
   * @param mixed $value
   */
  public function setContext($key, $value) {
    $this->context[$key] = $value;
  }

  /**
   * Get a list of components, grouped by filename.
   *
   * @return string[][]
   */
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

  /**
   * Resolve how to reference a file from another file.
   *
   * a/b/c, a/b/e => ./d
   * a/b/c, a/e => ../d
   * a/b/c/d, a/e => ../../e
   *
   * @param string $from
   * @param string $to
   * @return string
   */
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

  /**
   * Generate the file data from the grouped components.
   *
   * The return value is an associative array with the filename as keys and file contents as values. The file contents
   * is represented as an associative array with keys 'imports' (represents the imports from other files) and
   * 'components' (represents the components in the file).
   *
   * @return array[]
   */
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

  /**
   * Convert the generated file data to strings.
   *
   * @return string[]
   */
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

  /**
   * Writes to disk.
   *
   * Writes all the generated files to disk in the specified directory.
   *
   * @param string $target
   *   The path to the directory where the generated files should be placed.
   */
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