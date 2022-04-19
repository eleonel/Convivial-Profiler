<?php

namespace Drupal\convivial_profiler\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form for adding a profiler.
 *
 * @internal
 */
class ProfilerAddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'convivial_profiler_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Profiler name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this, 'profilerExists'],
      ],
      '#required' => TRUE,
    ];
    $form['weight'] = [
      '#type' => 'hidden',
      '#value' => 0,
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
    ];
    $form['deferred'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Deferred'),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Use this to specify the admin description for this profiler.'),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#submit' => ['::submitForm'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function profilerExists($name) {
    $profilers = $this->config('convivial_profiler.settings')->get('profilers');
    return array_key_exists($name, $profilers);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @codingStandardsIgnoreLine
    $profiler = array_intersect_key($form_state->getValues(), array_flip(['name', 'label', 'weight', 'status', 'deferred', 'description']));
    $profiler['sources'] = $profiler['processors'] = $profiler['destinations'] = [];
    // Save the Profiler.
    $config = $this->configFactory->getEditable('convivial_profiler.settings');
    $config->set('profilers.' . $profiler['name'], $profiler);
    $config->save();
    $this->messenger()->addStatus($this->t('Profiler %name was created.', ['%name' => $profiler['name']]));
    $form_state->setRedirectUrl(Url::fromRoute('convivial_profiler.profiler_edit_form', ['profiler_id' => $profiler['name']]));
  }

}
