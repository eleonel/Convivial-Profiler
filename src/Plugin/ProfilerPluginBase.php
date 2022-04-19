<?php

namespace Drupal\convivial_profiler\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides a base implementation of profiler plugin.
 */
abstract class ProfilerPluginBase extends PluginBase implements PluginFormInterface {

  /**
   * Get human-readable plugin name.
   *
   * @return string
   *   The plugin name.
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * Get plugin configuration.
   *
   * @return array
   *   The plugin configuration.
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    foreach ($this->pluginDefinition['form'] as $key => $item) {
      $form[$key] = $item;
      if (!empty($this->configuration[$key])) {
        $form[$key]['#default_value'] = $this->configuration[$key];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->pluginDefinition['form'] as $key => $item) {
      if ($item['#type'] === 'checkbox') {
        $this->configuration[$key] = boolval($form_state->getValue($key));
      }
      elseif ($item['#type'] === 'number') {
        $this->configuration[$key] = intval($form_state->getValue($key));
      }
      else {
        $this->configuration[$key] = $form_state->getValue($key);
      }
    }
  }

}
