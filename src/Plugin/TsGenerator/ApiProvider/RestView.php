<?php

namespace Drupal\ts_generator\Plugin\TsGenerator\ApiProvider;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\ts_generator\GeneratorInterface;
use Drupal\ts_generator\Plugin\ApiProviderBase;
use Drupal\views\Plugin\views\display\DisplayPluginInterface;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @TsGeneratorApiProvider(
 *   id = "rest_view",
 *   deriver = "Drupal\ts_generator\Plugin\Derivative\RestViewApiProvider"
 * )
 */
class RestView extends ApiProviderBase {

  /**
   * @var string
   */
  protected $viewName;
  /**
   * @var string
   */
  protected $displayName;

  /**
   * @var DisplayPluginInterface
   */
  protected $display;

  /**
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;


  public function __construct(array $configuration, $plugin_id, $plugin_definition, GeneratorInterface $generator, ViewExecutableFactory $executable_factory, EntityStorageInterface $storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $generator);

    $delta = $this->getDerivativeId();
    list($this->viewName, $this->displayName) = explode('-', $delta, 2);

    $view = $storage->load($this->viewName);
    $this->view = $executable_factory->get($view);
    $this->view->setDisplay($this->displayName);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ts_generator.generator'),
      $container->get('views.executable'),
      $container->get('entity.manager')->getStorage('view')
    );
  }

  /**
   * @return \Drupal\views\ViewExecutable
   */
  public function getView() {
    return $this->view;
  }
}