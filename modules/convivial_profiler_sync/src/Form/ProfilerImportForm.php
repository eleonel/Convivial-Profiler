<?php

namespace Drupal\convivial_profiler_sync\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form for importing the profilers.
 *
 * @internal
 */
class ProfilerImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'convivial_profiler_sync_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['import'] = [
      '#title' => $this->t('Profilers JSON:'),
      '#type' => 'textarea',
      '#description' => $this->t('Paste profilers JSON to import profilers into Convivial Profiler. This will update the configuration.'),
      '#rows' => 24,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Profilers'),
      '#submit' => ['::submitForm'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $profilers = Json::decode($form_state->getValue('import'));
    $config = \Drupal::configFactory()->getEditable('convivial_profiler.settings');
    $config->set('profilers', $profilers);
    $config->save();
    $this->messenger()->addStatus($this->t('Profilers have been imported successfully.'));
    $form_state->setRedirectUrl(Url::fromRoute('convivial_profiler.list'));
  }

}
