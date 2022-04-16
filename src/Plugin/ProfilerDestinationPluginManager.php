<?php

namespace Drupal\convivial_profiler\Plugin;

use Drupal\convivial_profiler\Annotation\ProfilerDestination;

/**
 * Provides a profiler destination plugin manager.
 */
class ProfilerDestinationPluginManager extends ProfilerPluginManagerBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginType = 'profiler_destination';

  /**
   * {@inheritdoc}
   */
  protected $pluginInterface = ProfilerDestinationInterface::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginAnnotation = ProfilerDestination::class;

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
    'description' => '',
    'form' => [],
    'class' => ProfilerDestinationDefault::class,
  ];

}
