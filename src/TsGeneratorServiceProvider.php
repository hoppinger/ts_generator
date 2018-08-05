<?php

namespace Drupal\ts_generator;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

class TsGeneratorServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->addCompilerPass(new RegisterGeneratorClassesCompilerPass());
  }
}