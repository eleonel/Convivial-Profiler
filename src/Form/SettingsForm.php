<?php

namespace Drupal\convivial_profiler\Form;

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
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $executable_manager
   *   The executable manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ExecutableManagerInterface $executable_manager, ContextRepositoryInterface $context_repository, MessengerInterface $messenger) {
    parent::__construct($config_factory);

    $this->conditionManager = $executable_manager;
    $this->contextRepository = $context_repository;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
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

    $form['convivial_profiler']['#markup'] = $this->t('Convivial Profiler is licensed software. It is free to use for community and not for profit uses. However, if you are using it in a commercial setting you need to purchase a license before going live. Details can be found on the Convivial Profiler <a href=":convivial_profiler">pricing page.</a>', [':convivial_profiler' => 'https://www.morpht.com/convivial-profiler-pricing']);
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

    foreach ($form_state->get('conditions') as $condition_id => $condition) {
      $subform = $form['visibility'][$condition_id];
      $subform_state = SubformState::createForSubform($subform, $form, $form_state);
      $condition->submitConfigurationForm($subform, $subform_state);
      $config->set('visibility.' . $condition_id, $condition->getConfiguration());
    }
    $config->save();
  }

}
