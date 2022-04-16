<?php

namespace Drupal\convivial_profiler\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a default implementation of profiler processor plugin.
 */
class ProfilerProcessorDefault extends ProfilerPluginBase implements ProfilerProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (isset($this->configuration['mappings'])) {
      $this->configuration['mappings'] = implode(PHP_EOL, $this->configuration['mappings']);
    }
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue('mappings')) {
      $mappings = array_map('mappings', explode(PHP_EOL, $form_state->getValue('mappings')));
      $form_state->setValue('mappings', $mappings);
    }
    parent::submitConfigurationForm($form, $form_state);
  }

}
