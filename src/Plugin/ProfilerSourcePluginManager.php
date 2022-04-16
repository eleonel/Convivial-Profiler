<?php

namespace Drupal\convivial_profiler\Plugin;

use Drupal\convivial_profiler\Annotation\ProfilerSource;

/**
 * Provides a profiler source plugin manager.
 */
class ProfilerSourcePluginManager extends ProfilerPluginManagerBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginType = 'profiler_source';

  /**
   * {@inheritdoc}
   */
  protected $pluginInterface = ProfilerSourceInterface::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginAnnotation = ProfilerSource::class;

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
    'description' => '',
    'form' => [],
    'class' => ProfilerSourceDefault::class,
  ];

}
