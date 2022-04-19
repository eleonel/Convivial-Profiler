<?php

namespace Drupal\convivial_profiler\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\convivial_profiler\Plugin\ProfilerDestinationPluginManager;
use Drupal\convivial_profiler\Plugin\ProfilerProcessorPluginManager;
use Drupal\convivial_profiler\Plugin\ProfilerSourcePluginManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Form for editing a profiler.
 *
 * @internal
 */
class ProfilerEditForm extends FormBase {

  /**
   * The profiler source plugin manager.
   *
   * @var \Drupal\convivial_profiler\Plugin\ProfilerSourcePluginManager
   */
  protected $sourceManager;

  /**
   * The profiler processor plugin manager.
   *
   * @var \Drupal\convivial_profiler\Plugin\ProfilerProcessorPluginManager
   */
  protected $processorManager;

  /**
   * The profiler destination plugin manager.
   *
   * @var \Drupal\convivial_profiler\Plugin\ProfilerDestinationPluginManager
   */
  protected $destinationManager;

  /**
   * The profiler source plugin definitions.
   *
   * @var array[]
   */
  protected $sourceDefinitions;

  /**
   * The profiler processor plugin definitions.
   *
   * @var array[]
   */
  protected $processorDefinitions;

  /**
   * The profiler destination plugin definitions.
   *
   * @var array[]
   */
  protected $destinationDefinitions;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new ProfilerEditForm instance.
   *
   * @param \Drupal\convivial_profiler\Plugin\ProfilerSourcePluginManager $profiler_source_manager
   *   The profiler source plugin manager.
   * @param \Drupal\convivial_profiler\Plugin\ProfilerProcessorPluginManager $profiler_processor_manager
   *   The profiler processor plugin manager.
   * @param \Drupal\convivial_profiler\Plugin\ProfilerDestinationPluginManager $profiler_destination_manager
   *   The profiler destination plugin manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ProfilerSourcePluginManager $profiler_source_manager, ProfilerProcessorPluginManager $profiler_processor_manager, ProfilerDestinationPluginManager $profiler_destination_manager, MessengerInterface $messenger) {

    $this->sourceManager = $profiler_source_manager;
    $this->processorManager = $profiler_processor_manager;
    $this->destinationManager = $profiler_destination_manager;
    $this->messenger = $messenger;

    $this->sourceDefinitions = $this->sourceManager->getDefinitions();
    $this->processorDefinitions = $this->processorManager->getDefinitions();
    $this->destinationDefinitions = $this->destinationManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('plugin.manager.profiler_source'),
    $container->get('plugin.manager.profiler_processor'),
    $container->get('plugin.manager.profiler_destination'),
    $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'convivial_profiler_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $profiler_id = NULL) {
    $profilers = $this->config('convivial_profiler.settings')->get('profilers');
    if (!isset($profilers[$profiler_id])) {
      throw new NotFoundHttpException();
    }
    $profiler = $profilers[$profiler_id];
    $form['#title'] = $this->t('Edit profiler %name', ['%name' => $profiler['name']]);
    $form['#tree'] = TRUE;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Profiler name'),
      '#default_value' => $profiler['label'] ?? '',
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#default_value' => $profiler['name'] ?? '',
      '#machine_name' => [
        'exists' => [$this, 'profilerExists'],
      ],
      '#required' => TRUE,
      '#disabled' => TRUE,
    ];
    $form['weight'] = [
      '#type' => 'hidden',
      '#default_value' => $profiler['weight'] ?? 0,
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#default_value' => $profiler['status'] ?? FALSE,
      '#title' => $this->t('Enabled'),
    ];
    $form['deferred'] = [
      '#type' => 'checkbox',
      '#default_value' => $profiler['deferred'] ?? FALSE,
      '#title' => $this->t('Deferred'),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $profiler['description'] ?? '',
      '#description' => $this->t('Use this to specify the admin description for this profiler.'),
    ];
    $form['profiler'][$profiler_id]['item']['source'] = [
      '#type' => 'details',
      '#title' => $this->t('Sources'),
    ];
    $this->buildDraggable($form, $form_state, $form['profiler'][$profiler_id]['item']['source'], 'source', $profiler['sources'], 'profiler', $profiler_id);
    $form['profiler'][$profiler_id]['item']['processor'] = [
      '#type' => 'details',
      '#title' => $this->t('Processors'),
    ];
    $this->buildDraggable($form, $form_state, $form['profiler'][$profiler_id]['item']['processor'], 'processor', $profiler['processors'], 'profiler', $profiler_id);
    $form['profiler'][$profiler_id]['item']['destination'] = [
      '#type' => 'details',
      '#title' => $this->t('Destinations'),
    ];
    $this->buildDraggable($form, $form_state, $form['profiler'][$profiler_id]['item']['destination'], 'destination', $profiler['destinations'], 'profiler', $profiler_id);
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm'],
      '#attributes' => [
        'class' => ['button--primary'],
      ],
    ];
    $form['actions']['delete'] = [
      '#type' => 'link',
      '#title' => $this->t('Delete'),
      '#attributes' => [
        'class' => [
          'action-link',
          'action-link--danger',
          'action-link--icon-trash',
        ],
      ],
      '#url' => Url::fromRoute('convivial_profiler.profiler_delete', ['profiler_id' => $profiler_id]),
    ];
    return $form;
  }

  /**
   * Build draggable table for source, processor, and destination plugins.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $element
   *   The current state of the form element.
   * @param string $type
   *   The plugin type e.g. source, processor, destination.
   * @param array $items
   *   The plugin items.
   * @param string $parent_type
   *   The plugin type e.g. profiler.
   * @param string $parent_key
   *   The config key name of the parent e.g. set, unset, etc.
   */
  protected function buildDraggable(array &$form, FormStateInterface $form_state, array &$element, $type, array $items, $parent_type = 'profiler', $parent_key = '0') {
    $definitions = $this->{$type . 'Definitions'};
    $wrapper_id = implode('--', [$type, $parent_type, $parent_key, 'wrapper']);
    $wrapper_order_id = $wrapper_id . '--order';
    $weight = -50;

    $element['wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => $wrapper_id],
    ];
    $element['wrapper'][$type] = [
      '#type' => 'table',
      '#header' => [$element['#title'], $this->t('Weight')],
      '#tabledrag' => [
                [
                  'action' => 'order',
                  'relationship' => 'sibling',
                  'group' => $wrapper_order_id,
                ],
      ],
      '#tree' => TRUE,
    ];

    foreach ($items as $key => $item) {
      $item_id = implode('--', [$type, $key, $parent_type, $parent_key]);
      // Check the details element has been called to open.
      // @see $this->saveItemSubmit and $this->editItemSubmit methods.
      // @codingStandardsIgnoreLine
      $open = !empty($form_state->get(['open', $parent_type, $parent_key, $type, $key]));
      $validation = $this->getValidationLimit($type, $key, $parent_type, $parent_key);

      $element['wrapper'][$type][$key] = [
        '#attributes' => ['class' => ['draggable']],
      ];
      $row = &$element['wrapper'][$type][$key];

      $definition = $definitions[$item['type']];
      $title = $definition['label'] . ' - ' . $definition['description'];

      $row['item'] = [
        '#type' => 'details',
        '#title' => $title,
        '#prefix' => '<div id="' . $item_id . '">',
        '#suffix' => '</div>',
        '#open' => $open,
      ];

      if ($open) {
        $this->buildItem($form, $form_state, $row['item'], $type, $key, $parent_type, $parent_key, $item);
        $row['item']['save'] = $this->buildButton('save', $this->t('Save'), $item_id, $item_id, $validation, 'primary');
      }
      else {
        $row['item']['edit'] = $this->buildButton('edit', $this->t('Edit'), $item_id, $item_id, [], 'primary');
      }
      $row['item']['delete'] = $this->buildButton('delete', $this->t('Delete'), $item_id, $wrapper_id, $validation, 'danger');
      $row['weight'] = $this->buildWeight($weight++, $wrapper_order_id);
    }

    // Add new plugin(source, processor, and destination) details element.
    // $add_item could be source, processor, destination.
    $add_item = $form_state->get(['add', $parent_type, $parent_key, $type]);
    if (!empty($add_item)) {
      $key = 'profiler_add_item';
      $item_id = implode('--', [$type, $key, $parent_type, $parent_key]);
      $validation = $this->getValidationLimit($type, $key, $parent_type, $parent_key);

      $element['wrapper'][$type][$key] = [
        '#attributes' => ['class' => ['draggable']],
      ];
      $row = &$element['wrapper'][$type][$key];

      $row['item'] = [
        '#type' => 'details',
        '#title' => strtoupper($this->t('New %type', ['%type' => $type])),
        '#prefix' => '<div id="' . $item_id . '">',
        '#suffix' => '</div>',
        '#open' => TRUE,
      ];
      $definition = $definitions[$add_item];
      $row['item']['#title'] .= ' - [' . $definition['label'] . ']';

      $item = ['type' => $add_item];
      $this->buildItem($form, $form_state, $row['item'], $type, $key, $parent_type, $parent_key, $item);

      $row['item']['save'] = $this->buildButton('save', $this->t('Save'), $item_id, $item_id, $validation, 'primary');

      $row['weight'] = $this->buildWeight($weight++, $wrapper_order_id);
    }

    $element['wrapper']['add'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add new %type', ['%type' => $type]),
    ];
    foreach ($definitions as $definition) {
      // @codingStandardsIgnoreLine
      $item_id = implode('--', [$type, $definition['id'], $parent_type, $parent_key]);
      $element['wrapper']['add'][$definition['id']] = $this->buildButton('add', $definition['label'], $item_id, $wrapper_id);
      $element['wrapper']['add'][$definition['id']]['#attributes'] = [
        'title' => $definition['description'],
      ];
    }
  }

  /**
   * Build subform of the provided plugin type.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $element
   *   The current state of the form element.
   * @param string $type
   *   The plugin type e.g. source, processor, destination.
   * @param string $key
   *   The plugin item key.
   * @param string $parent_type
   *   The plugin type e.g. profiler.
   * @param string $parent_key
   *   The config key name of the parent e.g. set, unset, etc.
   * @param array $item
   *   The plugin item.
   */
  protected function buildItem(array &$form, FormStateInterface $form_state, array &$element, $type, $key, $parent_type, $parent_key, array $item = []) {
    $subform = [];
    $plugin = $this->{$type . 'Manager'}->createInstance($item['type'], $item);
    $form_state->set([$parent_type, $parent_key, $type, $key], $plugin);
    $subform_state = SubformState::createForSubform($subform, $form, $form_state);
    $subform = $plugin->buildConfigurationForm($subform, $subform_state);
    $element['subform'] = $subform;
  }

  /**
   * Build button form element.
   *
   * @param string $key
   *   The unique identifier of the button element e.g. save, edit, delete.
   * @param string $value
   *   The button label translateable value.
   * @param string $name_prefix
   *   The unique identifier prefix of the element.
   * @param string $wrapper
   *   The ajax wrapper of the button element.
   * @param array $validation
   *   The config key name of the parent e.g. set, unset, etc.
   * @param string $style
   *   The style to apply on the button element e.g. primary, danger.
   *
   * @return array
   *   The button form element array.
   */
  protected function buildButton($key, $value, $name_prefix, $wrapper, array $validation = [], $style = '') {
    $element = [
      '#type' => 'submit',
      '#value' => $value,
      '#name' => $name_prefix . '--' . $key,
      '#button_type' => $style,
      '#validate' => [],
      '#submit' => [[$this, $key . 'ItemSubmit']],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [$this, $key . 'ItemAjax'],
        'wrapper' => $wrapper,
      ],
    ];
    if (!empty($validation)) {
      $element['#validate'][] = [$this, $key . 'ItemValidate'];
      $element['#limit_validation_errors'][] = $validation;
    }
    return $element;
  }

  /**
   * Build weight form element.
   *
   * @param int $value
   *   The weight of the form element.
   * @param string $class
   *   The class name to be applied.
   *
   * @return array
   *   The weight form element array.
   */
  protected function buildWeight($value, $class) {
    return [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#title_display' => 'invisible',
      '#default_value' => $value,
      '#attributes' => ['class' => [$class]],
      '#delta' => 50,
    ];
  }

  /**
   * Get "#limit_validation_errors" array for base and plugins.
   */
  protected function getValidationLimit($type, $key, $parent_type, $parent_key) {
    $base = [$type, $key, 'item'];
    $plugin = [$parent_type, $parent_key, 'item', $type, 'wrapper'];
    return $parent_type === 'base' ? $base : array_merge($plugin, $base);
  }

  /**
   * Validate handler before saving a plugin(source, processor, destination).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function saveItemValidate(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    [$type, $key, $parent_type, $parent_key] = explode('--', $trigger['#name']);

    $plugin = $form_state->get([$parent_type, $parent_key, $type, $key]);
    $parents = array_slice($trigger['#array_parents'], 0, -1);
    $subform = NestedArray::getValue($form, array_merge($parents, ['subform']));
    $subform_state = SubformState::createForSubform($subform, $form, $form_state);
    $plugin->validateConfigurationForm($subform, $subform_state);
  }

  /**
   * Submit handler for saving a new item(source, processor, destination).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function saveItemSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    [$type, $key, $parent_type, $parent_key] = explode('--', $trigger['#name']);
    // Set the option stage in the form state.
    $form_state->set(['open', $parent_type, $parent_key, $type, $key], FALSE);
    // Set the option stage in the form state.
    $form_state->set(['add', $parent_type, $parent_key, $type], NULL);
    // Rebuild the form state.
    $form_state->setRebuild();

    $config = $this->configFactory->getEditable('convivial_profiler.settings');
    $plugin = $form_state->get([$parent_type, $parent_key, $type, $key]);
    if ($key === 'profiler_add_item') {
      $parents = array_slice($trigger['#parents'], 0, -1);
      $key = NestedArray::getValue($form_state->getValues(), $parents)['name'];
    }
    $parents = array_slice($trigger['#array_parents'], 0, -1);
    $subform = NestedArray::getValue($form, array_merge($parents, ['subform']));
    $subform_state = SubformState::createForSubform($subform, $form, $form_state);
    $plugin->submitConfigurationForm($subform, $subform_state);

    if (is_null($key)) {
      $existing = $config->get($parent_type . 's.' . $parent_key . '.' . $type . 's');
      $key = count($existing);
    }
    $config->set($parent_type . 's.' . $parent_key . '.' . $type . 's.' . $key, $plugin->getConfiguration());
    $config->save();
  }

  /**
   * Ajax callback for saving a new plugin item(source, processor, destination).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function saveItemAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    [$type, $key, $parent_type, $parent_key] = explode('--', $trigger['#name']);

    if ($key === 'profiler_add_item') {
      $config = $this->configFactory->getEditable('convivial_profiler.settings');
      $existing = $config->get($parent_type . 's.' . $parent_key . '.' . $type . 's');
      $key = count($existing) - 1;
      $trigger['#array_parents'][6] = $key;
    }
    return NestedArray::getValue($form, array_slice($trigger['#array_parents'], 0, -1));
  }

  /**
   * Submit handler for updating an item(source, processor, destination).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function editItemSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    [$type, $key, $parent_type, $parent_key] = explode('--', $trigger['#name']);
    $form_state->set(['open', $parent_type, $parent_key, $type, $key], TRUE);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for updating existing item(source, processor, destination).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function editItemAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($trigger['#array_parents'], 0, -1));
  }

  /**
   * Validate handler before deleting an item(source, processor, destination).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function deleteItemValidate(array $form, FormStateInterface $form_state) {
    // Validate handler for deleting an item.
  }

  /**
   * Submit handler for deleting an item(source, processor, destination).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function deleteItemSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    [$type, $key, $parent_type, $parent_key] = explode('--', $trigger['#name']);
    $form_state->setRebuild();
    $config = $this->configFactory->getEditable('convivial_profiler.settings');

    $config->clear($parent_type . 's.' . $parent_key . '.' . $type . 's.' . $key);
    $config->save();
  }

  /**
   * Ajax callback for deleting existing item(source, processor, destination).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function deleteItemAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($trigger['#array_parents'], 0, -4));
  }

  /**
   * Submit handler showing a new plugin(source, processor, destination) form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addItemSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    [$type, $key, $parent_type, $parent_key] = explode('--', $trigger['#name']);
    $form_state->set(['add', $parent_type, $parent_key, $type], $key);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for showing new plugin(source, processor, destination) form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addItemAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($trigger['#array_parents'], 0, -2));
  }

  /**
   * Determines if the profiler already exists.
   *
   * @param string $name
   *   The profiler name.
   *
   * @return bool
   *   TRUE if the profiler exists, FALSE otherwise.
   */
  public function profilerExists($name) {
    $profilers = $this->config('convivial_profiler.settings')->get('profilers');
    return array_key_exists($name, $profilers);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $profiler_id = $form_state->getValue('name');
    $config = $this->configFactory->getEditable('convivial_profiler.settings');
    $profiler = $config->get('profilers.' . $profiler_id);
    $profiler['label'] = $form_state->getValue('label');
    $profiler['status'] = $form_state->getValue('status');
    $profiler['deferred'] = $form_state->getValue('deferred');
    $profiler['description'] = $form_state->getValue('description');
    $config->set('profilers.' . $profiler_id, $profiler);
    $config->save();
    $this->messenger()->addStatus($this->t('Changes to the profiler %name have been saved.', ['%name' => $profiler_id]));
    $form_state->setRedirectUrl(Url::fromRoute('convivial_profiler.list'));
  }

}
