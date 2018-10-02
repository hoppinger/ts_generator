<?php

namespace Drupal\ts_generator\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\ts_generator\GeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ApiProviderBase extends PluginBase implements ContainerFactoryPluginInterface, ApiProviderInterface {
  /**
   * @var GeneratorInterface
   */
  protected $generator;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, GeneratorInterface $generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->generator = $generator;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ts_generator.generator')
    );
  }
}