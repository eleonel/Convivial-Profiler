<?php

namespace Drupal\convivial_profiler\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a base profiler plugin annotation.
 */
abstract class ProfilerPluginBase extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable plugin name.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The plugin description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The plugin configuration form.
   *
   * @var array
   */
  public $form;

}
