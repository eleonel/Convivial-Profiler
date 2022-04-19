<?php

namespace Drupal\convivial_profiler\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Convivial Profiler list form.
 */
class ProfilerListForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'convivial_profiler_list_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'convivial_profiler.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('convivial_profiler.settings');

    $form['#title'] = $this->t('Profilers');
    $form['#tree'] = TRUE;

    // Build the list of existing profilers.
    $form['profilers'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Machine name'),
        $this->t('Status'),
        $this->t('Description'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
      [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'profiler-order-weight',
      ],
      ],
      '#empty' => $this->t('There are currently no profilers in the system.'),
    ];

    $profilers = $config->get('profilers');
    $weight_delta = round(count($profilers) / 2);
    foreach ($profilers as $key => $profiler) {
      // $key = $datasource->getUuid();
      $form['profilers'][$key]['#attributes']['class'][] = 'draggable';
      $form['profilers'][$key]['#weight'] = isset($user_input['profilers']) ? $user_input['profilers'][$key]['weight'] : $profiler['weight'];
      $form['profilers'][$key]['profiler'] = [
        '#tree' => FALSE,
        'settings' => [
          'label' => [
            '#plain_text' => $profiler['label'],
          ],
        ],
      ];

      $form['profilers'][$key]['machine_name']['#plain_text'] = $profiler['name'];
      $form['profilers'][$key]['status']['#plain_text'] = $profiler['status'] ? $this->t('Enabled') : $this->t('Disabled');
      $form['profilers'][$key]['description']['#plain_text'] = $profiler['description'] ?? '';

      $form['profilers'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @key', ['@key' => $key]),
        '#title_display' => 'invisible',
        '#default_value' => $profiler['weight'] ?? 0,
        '#delta' => $weight_delta,
        '#attributes' => [
          'class' => ['profiler-order-weight'],
        ],
      ];

      $links = [];
      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('convivial_profiler.profiler_edit_form', [
          'profiler_id' => $key,
        ]),
      ];
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('convivial_profiler.profiler_delete', [
          'profiler_id' => $key,
        ]),
      ];
      $form['profilers'][$key]['operations'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('convivial_profiler.settings');
    $profilers = [];
    foreach ($form_state->getValue('profilers') as $key => $profiler) {
      $profilers[$key] = $config->get('profilers.' . $key);
      $profilers[$key]['weight'] = $profiler['weight'];
    }
    $config->set('profilers', $profilers);
    $config->save();
  }

}
