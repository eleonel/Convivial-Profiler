<?php

namespace Drupal\convivial_profiler\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;

/**
 * Provides a base implementation of profiler plugin manager.
 */
abstract class ProfilerPluginManagerBase extends DefaultPluginManager {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The plugin type.
   *
   * @var string
   */
  protected $pluginType;

  /**
   * The plugin interface.
   *
   * @var string
   */
  protected $pluginInterface;

  /**
   * The plugin annotation.
   *
   * @var string
   */
  protected $pluginAnnotation;

  /**
   * Constructs a new ProfilerPluginManagerBase instance.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    parent::__construct('Plugin/' . $this->pluginType, $namespaces, $module_handler, $this->pluginInterface, $this->pluginAnnotation);

    $this->themeHandler = $theme_handler;
    $this->setCacheBackend($cache_backend, 'convivial_profiler_' . $this->pluginType . '_plugins');
    $this->alterInfo('convivial_profiler_' . $this->pluginType . '_info');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      $discovery = new AnnotatedClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName, $this->additionalAnnotationNamespaces);
      $discovery = new YamlDiscoveryDecorator($discovery, $this->pluginType, $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories());
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

}
