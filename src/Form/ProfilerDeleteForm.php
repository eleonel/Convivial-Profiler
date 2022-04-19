<?php

namespace Drupal\convivial_profiler\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Form for deleting a profiler.
 *
 * @internal
 */
class ProfilerDeleteForm extends ConfirmFormBase {

  /**
   * The profiler ID.
   *
   * @var string
   */
  protected $profiler;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the profiler <em>@profiler</em>?', [
      '@profiler' => $this->profiler,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('convivial_profiler.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'convivial_profiler_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $profiler_id = NULL) {
    $profilers = $this->config('convivial_profiler.settings')->get('profilers');
    if (!isset($profilers[$profiler_id])) {
      throw new NotFoundHttpException();
    }
    $this->profiler = $profiler_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('convivial_profiler.settings');
    $config->clear('profilers.' . $this->profiler);
    $config->save();
    $this->messenger()->addStatus($this->t('The profiler %name has been deleted.', ['%name' => $this->profiler]));
    $form_state->setRedirectUrl(Url::fromRoute('convivial_profiler.list'));
  }

}
