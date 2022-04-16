<?php

namespace Drupal\convivial_profiler\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\convivial_profiler\Plugin\ProfilerProcessorPluginManager;
use Drupal\convivial_profiler\Plugin\ProfilerDestinationPluginManager;
use Drupal\convivial_profiler\Plugin\ProfilerSourcePluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Convivial Profiler settings form.
 */
class SettingsForm extends ConfigFormBase {

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
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

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
   * Constructs a new SettingsForm instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\convivial_profiler\Plugin\ProfilerSourcePluginManager
   *   The profiler source plugin manager.
   * @param \Drupal\convivial_profiler\Plugin\ProfilerProcessorPluginManager
   *   The profiler processor plugin manager.
   * @param \Drupal\convivial_profiler\Plugin\ProfilerDestinationPluginManager
   *   The profiler destination plugin manager.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $executable_manager
   *   The executable manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ProfilerSourcePluginManager $profiler_source_manager, ProfilerProcessorPluginManager $profiler_processor_manager, ProfilerDestinationPluginManager $profilerr_destination_manager, ExecutableManagerInterface $executable_manager, ContextRepositoryInterface $context_repository, MessengerInterface $messenger) {
    parent::__construct($config_factory);

    $this->sourceManager = $profiler_source_manager;
    $this->processorManager = $profiler_processor_manager;
    $this->destinationManager = $profilerr_destination_manager;
    $this->conditionManager = $executable_manager;
    $this->contextRepository = $context_repository;
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
      $container->get('config.factory'),
      $container->get('plugin.manager.profiler_source'),
      $container->get('plugin.manager.profiler_processor'),
      $container->get('plugin.manager.profiler_destination'),
      $container->get('plugin.manager.condition'),
      $container->get('context.repository'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'convivial_profiler_settings_form';
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

    $form['convivial_profiler']['#markup'] = t('Convivial Profiler is licensed software. It is free to use for community and not for profit uses. However, if you are using it in a commercial setting you need to purchase a license before going live. Details can be found on the Convivial Profiler <a href=":convivial_profiler">pricing page.</a>',  [':convivial_profiler' => 'https://www.morpht.com/convivial-profiler-pricing']);
    $form['site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site ID'),
      '#description' => $this->t('The machine-readable site ID.'),
      '#default_value' => $config->get('site_id'),
      '#required' => TRUE,
    ];

    $form['license_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('License key'),
      '#description' => $this->t('Enter the key sent to you in the email your received when purchasing a license. If your project is a community or not for profit, please enter "community".'),
      '#default_value' => $config->get('license_key'),
    ];
    if (empty($config->get('license_key'))) {
      $this->messenger->addWarning('Please enter a license key below.');
    }

    $form['client_cleanup'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Client cleanup'),
      '#description' => $this->t('Use this checkbox to clear all stored values if client ID was changed.'),
      '#default_value' => $config->get('client_cleanup'),
    ];
    $form['event_tracking'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable event tracking'),
      '#description' => $this->t('Use this checkbox to enable event tracking.'),
      '#default_value' => $config->get('event_tracking'),
    ];

    $form['profiler'] = [
      '#type' => 'details',
      '#title' => $this->t('Profilers'),
    ];
    $this->buildDraggable($form, $form_state, $form['profiler'], 'profiler', $config->get('profilers'));

    $form['visibility'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Visibility'),
      '#attached' => ['library' => ['block/drupal.block']],
      '#tree' => TRUE,
    ];
    // Store the contexts for other objects to use during form building.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());
    $definitions = $this->conditionManager->getDefinitions();
    foreach ($definitions as $condition_id => $definition) {
      // Limit to certain condition types.
      if (!in_array($condition_id, [
        'entity_bundle:node',
        'request_path',
        'user_role',
        'language',
      ])) {
        continue;
      }
      $configuration = $config->get('visibility')[$condition_id] ?? [];
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->conditionManager->createInstance($condition_id, $configuration);
      $form_state->set(['conditions', $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'visibility';
      $form['visibility'][$condition_id] = $condition_form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Build draggable table for profilers, and plugins.
   */
  protected function buildDraggable(array &$form, FormStateInterface $form_state, array &$element, $type, $items, $parent_type = 'base', $parent_key = '0') {
    $definitions = $type === 'profiler' ? [] : $this->{$type . 'Definitions'};
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
      $open = !empty($form_state->get(['open', $parent_type, $parent_key, $type, $key]));
      $validation = $this->getValidationLimit($type, $key, $parent_type, $parent_key);

      $element['wrapper'][$type][$key] = [
        '#attributes' => ['class' => ['draggable']],
      ];
      $row = &$element['wrapper'][$type][$key];

      if ($type === 'profiler') {
        $title = strtoupper($key) . ' - ' . ($item['description'] ?? '');
      }
      else {
        $definition = $definitions[$item['type']];
        if ($type === 'profiler') {
          $title = strtoupper($key) . ' - [' . $definition['label'] . '] - ' . ($item['description'] ?? '');
        }
        else {
          $title = $definition['label'] . ' - ' . $definition['description'];
        }
      }
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
        '#title' => strtoupper($this->t('New ' . $type)),
        '#prefix' => '<div id="' . $item_id . '">',
        '#suffix' => '</div>',
        '#open' => TRUE,
      ];
      if ($type !== 'profiler') {
        $definition = $definitions[$add_item];
        $row['item']['#title'] .= ' - [' . $definition['label'] . ']';
      }

      $item = $type === 'profiler' ? [] : ['type' => $add_item];
      $this->buildItem($form, $form_state, $row['item'], $type, $key, $parent_type, $parent_key, $item);

      $row['item']['save'] = $this->buildButton('save', $this->t('Save'), $item_id, $item_id, $validation, 'primary');

      $row['weight'] = $this->buildWeight($weight++, $wrapper_order_id);
    }

    if ($type === 'profiler') {
      $item_id = implode('--', [$type, '1', $parent_type, $parent_key]);
      $element['wrapper']['add'] = $this->buildButton('add', $this->t('Add new ' . $type), $item_id, $wrapper_id);
    }
    else {
      $element['wrapper']['add'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Add new ' . $type),
      ];
      foreach ($definitions as $definition) {
        $item_id = implode('--', [$type, $definition['id'], $parent_type, $parent_key]);
        $element['wrapper']['add'][$definition['id']] = $this->buildButton('add', $definition['label'], $item_id, $wrapper_id);
        $element['wrapper']['add'][$definition['id']]['#attributes'] = [
          'title' => $definition['description'],
        ];
      }
    }
  }

  /**
   * Build item form.
   */
  protected function buildItem(array &$form, FormStateInterface $form_state, array &$element, $type, $key, $parent_type, $parent_key, $item = []) {
    $config = $this->config('convivial_profiler.settings');

    if ($type === 'profiler') {
      $element['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Machine name'),
        '#description' => $this->t('The machine-readable ' . $type . ' ID.'),
        '#required' => TRUE,
        '#default_value' => $key !== 'profiler_add_item' ? $key : '',
        '#disabled' => $key !== 'profiler_add_item',
      ];
      $element['description'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Admin description'),
        '#default_value' => $item['description'] ?? '',
      ];
      $element['deferred'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Deferred'),
        '#description' => $this->t('Check this if a source fetching is asynchronous.'),
        '#default_value' => !empty($item['deferred']),
      ];

      $element['source'] = [
        '#type' => 'details',
        '#title' => $this->t('Sources'),
      ];
      if ($key !== 'profiler_add_item') {
        $this->buildDraggable($form, $form_state, $element['source'], 'source', $item['sources'] ?? [], $type, $key);
      }
      else {
        $element['source']['info'] = [
          '#markup' => $this->t('Sources can be added after profiler is saved.'),
        ];
      }

      $element['processor'] = [
        '#type' => 'details',
        '#title' => $this->t('Processors'),
      ];
      if ($key !== 'profiler_add_item') {
        $this->buildDraggable($form, $form_state, $element['processor'], 'processor', $item['processors'] ?? [], $type, $key);
      }
      else {
        $element['processor']['info'] = [
          '#markup' => $this->t('Processors can be added after profiler is saved.'),
        ];
      }

      $element['destination'] = [
        '#type' => 'details',
        '#title' => $this->t('Destinations'),
      ];
      if ($key !== 'profiler_add_item') {
        $this->buildDraggable($form, $form_state, $element['destination'], 'destination', $item['destinations'] ?? [], $type, $key);
      }
      else {
        $element['destination']['info'] = [
          '#markup' => $this->t('Destinations can be added after profiler is saved.'),
        ];
      }
    }
    else {
      $subform = [];
      $plugin = $this->{$type . 'Manager'}->createInstance($item['type'], $item);
      $form_state->set([$parent_type, $parent_key, $type, $key], $plugin);
      $subform_state = SubformState::createForSubform($subform, $form, $form_state);
      $subform = $plugin->buildConfigurationForm($subform, $subform_state);
      $element['subform'] = $subform;
    }
  }

  /**
   * Build button.
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
   * Build weight.
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
   * Validate plugin handler for saving an item.
   */
  public function saveItemValidate(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    [$type, $key, $parent_type, $parent_key] = explode('--', $trigger['#name']);

    if ($type !== 'profiler') {
      $plugin = $form_state->get([$parent_type, $parent_key, $type, $key]);
      $parents = array_slice($trigger['#array_parents'], 0, -1);
      $subform = NestedArray::getValue($form, array_merge($parents, ['subform']));
      $subform_state = SubformState::createForSubform($subform, $form, $form_state);
      $plugin->validateConfigurationForm($subform, $subform_state);
    }
  }

  /**
   * Submit process for saving an item.
   */
  public function saveItemSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    [$type, $key, $parent_type, $parent_key] = explode('--', $trigger['#name']);
    $form_state->set(['open', $parent_type, $parent_key, $type, $key], FALSE);
    $form_state->set(['add', $parent_type, $parent_key, $type], NULL);
    $form_state->setRebuild();
    $config = $this->config('convivial_profiler.settings');

    if ($type === 'profiler') {
      $parents = array_slice($trigger['#parents'], 0, -1);
      $values = NestedArray::getValue($form_state->getValues(), $parents);
      $sources = [];
      $processors = [];
      $destinations = [];

      if ($key === 'profiler_add_item') {
        $key = NestedArray::getValue($form_state->getValues(), $parents)['name'];
      }

      if (!empty($values['source']['wrapper']['source'])) {
        foreach ($values['source']['wrapper']['source'] as $source_key => $item) {
          if ($source_key !== 'profiler_add_item') {
            $sources[] = $config->get('profilers.' . $key . '.sources.' . $source_key);
          }
        }
      }
      if (!empty($values['processor']['wrapper']['processor'])) {
        foreach ($values['processor']['wrapper']['processor'] as $processor_key => $item) {
          if ($processor_key !== 'profiler_add_item') {
            $processors[] = $config->get('profilers.' . $key . '.processors.' . $processor_key);
          }
        }
      }
      if (!empty($values['destination']['wrapper']['destination'])) {
        foreach ($values['destination']['wrapper']['destination'] as $destination_key => $item) {
          if ($destination_key !== 'profiler_add_item') {
            $destinations[] = $config->get('profilers.' . $key . '.destinations.' . $destination_key);
          }
        }
      }
      $config->set('profilers.' . $key . '.description', $values['description']);
      $config->set('profilers.' . $key . '.deferred', boolval($values['deferred']));
      $config->set('profilers.' . $key . '.sources', $sources);
      $config->set('profilers.' . $key . '.processors', $processors);
      $config->set('profilers.' . $key . '.destinations', $destinations);
    }
    else {
      $plugin = $form_state->get([$parent_type, $parent_key, $type, $key]);

      $parents = array_slice($trigger['#array_parents'], 0, -1);
      $subform = NestedArray::getValue($form, array_merge($parents, ['subform']));
      $subform_state = SubformState::createForSubform($subform, $form, $form_state);
      $plugin->submitConfigurationForm($subform, $subform_state);

      if ($type === 'profiler') {
        $config->set('profilers.' . $key, $plugin->getConfiguration());
      }
      else {
        $existing = $config->get($parent_type . 's.' . $parent_key . '.' . $type . 's');
        $key = count($existing);
        $config->set($parent_type . 's.' . $parent_key . '.' . $type . 's.' . $key, $plugin->getConfiguration());
      }
    }
    $config->save();
  }

  /**
   * AJAX callback for saving an item.
   */
  public function saveItemAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    [$type, $key, $parent_type, $parent_key] = explode('--', $trigger['#name']);

    if ($key === 'profiler_add_item') {
      if ($parent_type === 'base') {
        $parents = array_slice($trigger['#parents'], 0, -1);
        $key = NestedArray::getValue($form_state->getValues(), $parents)['name'];
        $trigger['#array_parents'][3] = $key;
      }
      else {
        $config = $this->config('convivial_profiler.settings');
        $existing = $config->get($parent_type . 's.' . $parent_key . '.' . $type . 's');
        $key = count($existing) - 1;
        $trigger['#array_parents'][8] = $key;
      }
    }
    return NestedArray::getValue($form, array_slice($trigger['#array_parents'], 0, -1));
  }

  /**
   * Submit handler for editing an item.
   */
  public function editItemSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    [$type, $key, $parent_type, $parent_key] = explode('--', $trigger['#name']);
    $form_state->set(['open', $parent_type, $parent_key, $type, $key], TRUE);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for editing an item.
   */
  public function editItemAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($trigger['#array_parents'], 0, -1));
  }

  /**
   * Validate handler for deleting an item.
   */
  public function deleteItemValidate(array $form, FormStateInterface $form_state) {
    // Validate handler for deleting an item
  }

  /**
   * Submit handler for deleting an item.
   */
  public function deleteItemSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    [$type, $key, $parent_type, $parent_key] = explode('--', $trigger['#name']);
    $form_state->setRebuild();
    $config = $this->config('convivial_profiler.settings');

    if ($parent_type === 'base') {
      $config->clear($type . 's.' . $key);
    }
    else {
      $config->clear($parent_type . 's.' . $parent_key . '.' . $type . 's.' . $key);
    }
    $config->save();
  }

  /**
   * AJAX callback for deleting an item.
   */
  public function deleteItemAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($trigger['#array_parents'], 0, -4));
  }

  /**
   * Submit handler for adding an item.
   */
  public function addItemSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    [$type, $key, $parent_type, $parent_key] = explode('--', $trigger['#name']);
    $form_state->set(['add', $parent_type, $parent_key, $type], $key);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for adding an item.
   */
  public function addItemAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    [$type, $key, $parent_type, $parent_key] = explode('--', $trigger['#name']);

    if ($type === 'profiler') {
      return NestedArray::getValue($form, array_slice($trigger['#array_parents'], 0, -1));
    }
    else {
      return NestedArray::getValue($form, array_slice($trigger['#array_parents'], 0, -2));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Allow the condition to validate the form.
    foreach ($form_state->get('conditions') as $condition_id => $condition) {
      $subform = $form['visibility'][$condition_id];
      $subform_state = SubformState::createForSubform($subform, $form, $form_state);
      $condition->validateConfigurationForm($subform, $subform_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('convivial_profiler.settings');
    $config->set('site_id', trim($form_state->getValue('site_id')));
    $config->set('license_key', trim($form_state->getValue('license_key')));
    $config->set('client_cleanup', $form_state->getValue('client_cleanup'));
    $config->set('event_tracking', $form_state->getValue('event_tracking'));
    $profilers = [];
    foreach ($form_state->getValue('profiler') as $key => $item) {
      $profilers[$key] = $config->get('profilers.' . $key);
    }
    $config->set('profilers', $profilers);

    foreach ($form_state->get('conditions') as $condition_id => $condition) {
      $subform = $form['visibility'][$condition_id];
      $subform_state = SubformState::createForSubform($subform, $form, $form_state);
      $condition->submitConfigurationForm($subform, $subform_state);
      $config->set('visibility.' . $condition_id, $condition->getConfiguration());
    }
    $config->save();
  }

}
