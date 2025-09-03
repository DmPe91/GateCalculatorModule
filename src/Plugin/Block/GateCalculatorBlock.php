<?php

namespace Drupal\gate_calculator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Gate Calculator' block.
 *
 * @Block(
 *   id = "gate_calculator_block",
 *   admin_label = @Translation("Gate Calculator Block"),
 *   category = @Translation("Custom")
 * )
 */
class GateCalculatorBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $formBuilder;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  public function build() {
    // Получаем форму полностью
    $form = $this->formBuilder->getForm('Drupal\gate_calculator\Form\GateCalculatorForm');

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['gate-calculator-block']],
      'form' => $form,
      '#attached' => ['library' => ['gate_calculator/block_styles']],
      '#cache' => ['max-age' => 0],
    ];
  }

}
