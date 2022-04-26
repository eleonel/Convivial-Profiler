<?php

namespace Drupal\convivial_profiler\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a default implementation of profiler destination plugin.
 */
class ProfilerDestinationDefault extends ProfilerPluginBase implements ProfilerDestinationInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (isset($this->configuration['storage_keys'])) {
      $this->configuration['storage_keys'] = implode(PHP_EOL, $this->configuration['storage_keys']);
    }
    if (isset($this->configuration['static_values'])) {
      $this->configuration['static_values'] = implode(PHP_EOL, $this->configuration['static_values']);
    }
    if (isset($this->configuration['fields_selector'])) {
      $this->configuration['fields_selector'] = implode(PHP_EOL, $this->configuration['fields_selector']);
    }
    if (isset($this->configuration['ranges'])) {
      foreach ($this->configuration['ranges'] as &$range) {
        $range = implode('|', $range);
      }
      $this->configuration['ranges'] = implode(PHP_EOL, $this->configuration['ranges']);
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue('storage_keys')) {
      $paths = array_map('trim', explode(PHP_EOL, $form_state->getValue('storage_keys')));
      $form_state->setValue('storage_keys', $paths);
    }
    if ($form_state->hasValue('static_values')) {
      $static_values = array_map('trim', explode(PHP_EOL, $form_state->getValue('static_values')));
      $form_state->setValue('static_values', $static_values);
    }
    if ($form_state->hasValue('fields_selector')) {
      $fields_selector = array_map('trim', explode(PHP_EOL, $form_state->getValue('fields_selector')));
      $form_state->setValue('fields_selector', $fields_selector);
    }
    if ($form_state->hasValue('ranges')) {
      $ranges = explode(PHP_EOL, $form_state->getValue('ranges'));
      foreach ($ranges as &$range) {
        [$key, $min, $max] = explode('|', $range);
        $range = [
          'key' => trim($key),
          'min' => intval($min),
          'max' => intval($max),
        ];
      }
      $form_state->setValue('ranges', $ranges);
    }

    parent::submitConfigurationForm($form, $form_state);
  }

}
