<?php

namespace Drupal\ts_generator;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class RegisterGeneratorClassesCompilerPass implements CompilerPassInterface {
  public function process(ContainerBuilder $container) {
    $definition = $container->getDefinition('ts_generator.generator');

    $component_generators = [];
    foreach ($container->findTaggedServiceIds('ts_generator_component') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $component_generators[$priority][] = new Reference($id);
    }

    // Add the registered Normalizers and Encoders to the Serializer.
    if (!empty($component_generators)) {
      $definition->replaceArgument(0, $this->sort($component_generators));
    }
  }

  /**
   * Sorts by priority.
   *
   * Order services from highest priority number to lowest (reverse sorting).
   *
   * @param array $services
   *   A nested array keyed on priority number. For each priority number, the
   *   value is an array of Symfony\Component\DependencyInjection\Reference
   *   objects, each a reference to a normalizer or encoder service.
   *
   * @return array
   *   A flattened array of Reference objects from $services, ordered from high
   *   to low priority.
   */
  protected function sort($services) {
    $sorted = [];
    krsort($services);

    // Flatten the array.
    foreach ($services as $a) {
      $sorted = array_merge($sorted, $a);
    }

    return $sorted;
  }

}
