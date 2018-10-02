<?php

namespace Drupal\ts_generator\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\ts_generator\ApiProviderPluginManager;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;
use Drupal\ts_generator\Generator;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class TsGeneratorCommands extends DrushCommands {

  /**
   * Generate TypeScript code for the REST Resources
   *
   * @param $filename
   *   Config file
   * @usage ts_generator-generator foo
   *   Usage description
   *
   * @command ts_generator:generate
   */
  public function generate($filename) {
    if (!file_exists($filename)) {
      $this->logger()->error(dt('The specified file does not exist.'));
    }

    $working_directory = dirname($filename);
    $settings = Settings::loadFile($filename);

    /** @var \Drupal\ts_generator\ApiProviderPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.ts_generator_api_provider');
    $result = new Result();

    $execution_plan = $this->planExecution($settings, $plugin_manager);
    foreach ($execution_plan as $plugin_name => $plugin_settings) {
      /** @var \Drupal\ts_generator\Plugin\ApiProviderInterface $plugin */
      $plugin = $plugin_manager->createInstance($plugin_name, $plugin_settings);
      $plugin->generate($settings, $result);
    }

    $target_directory = $working_directory . '/' . $settings->get('target_directory');
    $result->write($target_directory);
  }

  protected function planExecution(Settings $settings, ApiProviderPluginManager $plugin_manager) {
    $available_plugins = $plugin_manager->getDefinitions();

    $plugins_to_execute = $settings->getPlugins();
    $execution_plan = [];

    foreach ($plugins_to_execute as $wildcard => $plugin_settings) {
      $matching_plugins = $this->matchingPlugins($wildcard, array_keys($available_plugins));

      if (empty($matching_plugins)) {
        throw new \Exception("No plugin found for " . $wildcard);
      }

      foreach ($matching_plugins as $plugin_name) {
        if (isset($execution_plan[$plugin_name])) {
          continue;
        }

        $execution_plan[$plugin_name] = $plugin_settings;
      }
    }

    return $execution_plan;
  }

  protected function matchingPlugins($wildcard, $plugins) {
    $regex = $this->regexForWildcard($wildcard);

    return array_filter($plugins, function($plugin) use ($regex) {
      return !!preg_match($regex, $plugin);
    });
  }

  protected function regexForWildcard($wildcard) {
    return '/^' . implode('', array_map(function($p) {
      return $p == '*' ? '.*' : preg_quote($p, '/');
    }, str_split($wildcard, 1))) . '$/';
  }
}
