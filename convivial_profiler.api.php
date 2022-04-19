<?php

/**
 * @file
 * Hooks provided by the Convivial Profiler module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the available profiler sources.
 *
 * Modules may implement this hook to alter the information that defines
 * plugins. All properties that are available in
 * \Drupal\convivial_profiler\Annotation\ProfilerSource can be altered here,
 * with the addition of the "class" and "provider" keys.
 *
 * @param array $plugins
 *   The plugin information to be altered, keyed by plugin ID.
 *
 * @see \Drupal\convivial_profiler\Annotation\ProfilerSource
 */
function hook_convivial_profiler_profiler_source_info_alter(array &$plugins) {
  if (isset($plugins['example_plugin'])) {
    $plugins['example_plugin']['class'] = '\Drupal\my_module\MuchBetterPlugin';
    $plugins['example_plugin']['label'] = t('Much Better Plugin');
  }
}

/**
 * Alter the available profiler processors.
 *
 * Modules may implement this hook to alter the information that defines
 * plugins. All properties that are available in
 * \Drupal\convivial_profiler\Annotation\ProfilerProcessor can be altered here,
 * with the addition of the "class" and "provider" keys.
 *
 * @param array $plugins
 *   The plugin information to be altered, keyed by plugin ID.
 *
 * @see \Drupal\convivial_profiler\Annotation\ProfilerProcessor
 */
function hook_convivial_profiler_profiler_processor_info_alter(array &$plugins) {
  if (isset($plugins['example_plugin'])) {
    $plugins['example_plugin']['class'] = '\Drupal\my_module\MuchBetterPlugin';
    $plugins['example_plugin']['label'] = t('Much Better Plugin');
  }
}

/**
 * Alter the available profiler destinations.
 *
 * Modules may implement this hook to alter the information that defines
 * plugins. All properties that are available in
 * \Drupal\convivial_profiler\Annotation\ProfilerDestination can be altered
 * here, with the addition of the "class" and "provider" keys.
 *
 * @param array $plugins
 *   The plugin information to be altered, keyed by plugin ID.
 *
 * @see \Drupal\convivial_profiler\Annotation\ProfilerDestination
 */
function hook_convivial_profiler_profiler_destination_info_alter(array &$plugins) {
  if (isset($plugins['example_plugin'])) {
    $plugins['example_plugin']['class'] = '\Drupal\my_module\MuchBetterPlugin';
    $plugins['example_plugin']['label'] = t('Much Better Plugin');
  }
}

/**
 * @} End of "addtogroup hooks".
 */
