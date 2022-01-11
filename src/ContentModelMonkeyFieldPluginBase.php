<?php

namespace Drupal\content_model_monkey;

use Drupal\Component\Plugin\PluginBase;
use Drupal\layout_builder\SectionComponent;

/**
 * Base class for content_model_monkey_field plugins.
 */
abstract class ContentModelMonkeyFieldPluginBase extends PluginBase implements ContentModelMonkeyFieldInterface {

  protected $defaultViewModeFieldFormatterSettings = [
    'label' => 'inline',
    'type' => 'string',
    'settings' => [
      'link_to_entity' => FALSE,
    ],
  ];

  protected $searchIndexViewModeFieldFormatterSettings = [
    'label' => 'hidden',
    'type' => 'string',
    'settings' => [
      'link_to_entity' => FALSE,
    ],
  ];

  protected $secondaryViewModeFieldFormatterSettings = [
    'label' => 'hidden',
    'type' => 'string',
    'settings' => [
      'link_to_entity' => FALSE,
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  public function addToFormViewMode($type_name, $field) {

    $form_view_mode = \Drupal::service('entity_type.manager')->getStorage('entity_form_display')->load("node.{$type_name}.default");
    if (!empty($field['form_view_mode_group'])) {
      $field_group_settings = $form_view_mode->getThirdPartySettings('field_group')['group_' . $field['form_view_mode_group']];
      if (is_null($field_group_settings)) {
        $field_group_settings = ['children' => []];
      }
      if (!in_array($field['field_name'], $field_group_settings['children'])) {
        $field_group_settings['children'][] = $field['field_name'];
      }
      $form_view_mode->setThirdPartySetting('field_group', 'group_' . $field['form_view_mode_group'], $field_group_settings);
    }


    $options = $this->getFieldWidgetOptions();
    $options['weight'] = $field['weight'];

    // See default settings above. but we probably wanna be explicit yeah. yeah.
    $form_view_mode->setComponent($field['field_name'], $options);

    $form_view_mode->save();

  }

  public function getFieldStorageSettings() {
    return [];
  }

  public function getFieldConfigSettings() {
    return [];
  }

  /**
   * @param $type_name
   * @param $field
   *
   * @return array
   */
  public function getViewModeFormatterConfig($type_name, $field, $view_mode_short_name): array {

    // A list of view modes that are visible by a users. Ie, rendered.
    // These all use the default formatter config for the field.
    // exceptions like search index, have to supply an override property.
    // see below for how the prefix is added toa porperty of the plugin.
    $default_view_modes = [
      'full',
    ];
    $secondary_view_modes = [
      'search',
      'summary',
      'teaser',
      'teaser_inline',
    ];
    if (!in_array($view_mode_short_name, $default_view_modes) && !in_array($view_mode_short_name, $secondary_view_modes)) {
      $prefix = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $view_mode_short_name)))) . 'ViewModeFieldFormatterSettings';
      $config = $this->$prefix;
    }
    else if (in_array($view_mode_short_name, $default_view_modes)) {
      $config = $this->defaultViewModeFieldFormatterSettings;
    }
    else if (in_array($view_mode_short_name, $secondary_view_modes)) {
      $config = $this->secondaryViewModeFieldFormatterSettings;
    }

    return $config;
  }

  public function addToDisplayViewMode($bundle_name, $field, $view_mode_short_name, $section_number = 1, $region_name = 'content') {

    $full_display_view_mode = \Drupal::service('entity_type.manager')->getStorage('entity_view_display')->load("node.{$bundle_name}.$view_mode_short_name");
    $layout_settings = $full_display_view_mode->getThirdPartySettings('layout_builder');

    $section = $layout_settings['sections'][$section_number];

    $config = [
      'id'              => "field_block:node:$bundle_name:{$field['field_name']}",
      'label'           => $field['label'],
      'provider'        => 'layout_builder',
      'label_display'   => '0',
      'context_mapping' =>
        [
          'entity'    => 'layout_builder.entity',
          'view_mode' => 'view_mode',
        ],
    ];

    $config['formatter'] = $this->getViewModeFormatterConfig($bundle_name, $field, $view_mode_short_name);

    $new_component = TRUE;
    $components = $section->getComponents();
    foreach ($components as $component) {
      if ($component->get('configuration')['id'] === "field_block:node:$bundle_name:{$field['field_name']}") {
        $component->setConfiguration($config);

        if ($field['required']) {
          $component->setWeight(1);
        }
        else {
          $component->setWeight($field['weight']);
        }

        $component->setRegion($region_name);
        $new_component = FALSE;
        break;
      }
    }

    if ($new_component) {
      $component = (new SectionComponent(\Drupal::service('uuid')->generate(), $region_name, $config));
      $component->setConfiguration($config);
      $component->setWeight($field['weight']);
      $component->setRegion($region_name);
      $section->appendComponent($component);
    }

    $full_display_view_mode->setThirdPartySetting('layout_builder', 'sections', $layout_settings['sections']);
    $full_display_view_mode->save();
  }

}
