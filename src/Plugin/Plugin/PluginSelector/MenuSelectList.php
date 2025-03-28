<?php

namespace Drupal\openy_prgf_sidebar_menu\Plugin\Plugin\PluginSelector;

use Drupal\Core\Form\FormStateInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\AdvancedPluginSelectorBase;
use Drupal\plugin\PluginDefinition\PluginLabelDefinitionInterface;
use Drupal\plugin\PluginHierarchyTrait;

/**
 * Provides a plugin selector using a <select> element.
 *
 * @PluginSelector(
 *   id = "plugin_menu_select_list",
 *   label = @Translation("Menu drop-down selection list")
 * )
 */
class MenuSelectList extends AdvancedPluginSelectorBase {

  use PluginHierarchyTrait;

  /**
   * {@inheritdoc}
   */
  protected function buildSelector(array $root_element, FormStateInterface $form_state, array $plugins) {
    $element = parent::buildSelector($root_element, $form_state, $plugins);
    /** @var \Drupal\Component\Plugin\PluginInspectionInterface[] $plugins */

    $element['container']['plugin_id'] = array(
      '#ajax' => [
        'callback' => [$this, 'ajaxRebuildForm'],
        'effect' => 'fade',
        'event' => 'change',
        'trigger_as' => [
          'name' => $element['container']['change']['#name'],
        ],
      ],
      '#default_value' => $this->getSelectedPlugin() ? $this->getSelectedPlugin()->getPluginId() : NULL,
      '#empty_value' => '',
      '#options' => $this->buildOptionsLevel($this->buildPluginHierarchy($this->selectablePluginDiscovery)),
      '#required' => $this->isRequired(),
      '#title' => $this->getLabel(),
      '#description' => $this->getDescription(),
      '#type' => 'select',
    );

    return $element;
  }

  /**
   * Helper function for self::options().
   *
   * @param array $hierarchy
   *   A plugin ID hierarchy as returned by self::hierarchy().
   * @param integer $depth
   *   The depth of $hierarchy's top-level items as seen from the original
   *   hierarchy's root (this function is recursive), starting with 0.
   *
   * @return string[]
   *   Keys are plugin IDs.
   */
  protected function buildOptionsLevel(array $hierarchy, $depth = 0) {
    $plugin_definitions = $this->selectablePluginDiscovery->getDefinitions();
    $options = [];
    $prefix = $depth ? str_repeat('-', $depth) . ' ' : '';
    foreach ($hierarchy as $plugin_id => $child_plugin_ids) {
      $plugin_definition = $plugin_definitions[$plugin_id];

      $category = $plugin_definition->getCategory();
      if (!$category || is_string($category)) {
        continue;
      }
      $category_name = $category->getUntranslatedString();
      if (strpos($category_name, 'Menus') === FALSE) {
        continue;
      }

      $label = $plugin_definition instanceof PluginLabelDefinitionInterface ? $plugin_definition->getLabel() : $plugin_definition->getId();

      $options[$plugin_id] = $prefix . $label;
      $options += $this->buildOptionsLevel($child_plugin_ids, $depth + 1);
    }

    return $options;
  }

}
