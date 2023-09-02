<?php

namespace Drupal\convivial_profiler_sync\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for exporting the profilers.
 *
 * @internal
 */
class ProfilerExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'convivial_profiler_sync_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $profilers = $this->config('convivial_profiler.settings')->get('profilers');
    $form['export'] = [
      '#title' => $this->t('Profilers JSON:'),
      '#description' => $this->t('Copy the JSON profilers for import into Convivial Profiler hosted on an external system.'),
      '#type' => 'textarea',
      '#rows' => 24,
      '#value' => Json::encode($profilers),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // submitForm is optional.
  }

}
