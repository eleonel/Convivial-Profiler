<?php

namespace Drupal\convivial_profiler\Plugin;

use Drupal\convivial_profiler\Annotation\ProfilerProcessor;

/**
 * Provides a profiler processor plugin manager.
 */
class ProfilerProcessorPluginManager extends ProfilerPluginManagerBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginType = 'profiler_processor';

  /**
   * {@inheritdoc}
   */
  protected $pluginInterface = ProfilerProcessorInterface::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginAnnotation = ProfilerProcessor::class;

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
    'description' => '',
    'form' => [],
    'class' => ProfilerProcessorDefault::class,
  ];

}
