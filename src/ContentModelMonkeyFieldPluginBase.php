<?php

namespace Drupal\content_model_monkey;

use Drupal\Component\Plugin\PluginBase;
use Drupal\layout_builder\SectionComponent;

/**
 * Base class for content_model_monkey_field plugins.
 */
abstract class ContentModelMonkeyFieldPluginBase extends PluginBase implements ContentModelMonkeyFieldInterface {

  protected $fullViewModeLabelPosition = 'inline';
  protected $fullViewModeType = 'string';
  protected $fullViewModeSettings = [
    'link_to_entity' => FALSE,
  ];
  //@todo move view modes stuff to a sheet in content model.
  protected $fullViewModeLayoutId = 'tga_content_sidebar';
  protected $fullViewModeRegion = 'content';

  protected $searchIndexViewModeLabelPosition = 'hidden';
  protected $searchIndexViewModeType = 'string';
  protected $searchIndexViewModeSettings = [
    'link_to_entity' => FALSE,
  ];
  protected $searchIndexViewModeLayoutId = 'tga_onecol';
  protected $searchIndexViewModeRegion = 'content';

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  public function addToFormViewMode($type_name, $field) {

    $form_view_mode = \Drupal::service('entity_type.manager')->getStorage('entity_form_display')->load("node.{$type_name}.default");
    $field_group_settings = $form_view_mode->getThirdPartySettings('field_group')['group_content'];
    if (!in_array($field['field_name'], $field_group_settings['children'])) {
      $field_group_settings['children'][] = $field['field_name'];
    }

    $form_view_mode->setThirdPartySetting('field_group', 'group_content', $field_group_settings);

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
  public function getViewModeLayoutSectionConfig($view_mode_short_name): array {
    $property_prefix = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $view_mode_short_name))));

    $layout_id_property_name = "{$property_prefix}ViewModeLayoutId";
    $region_property_name = "{$property_prefix}ViewModeRegion";

    $config = [
      'layout_id' => $this->$layout_id_property_name,
      'region'   => $this->$region_property_name,
    ];

    return $config;
  }

  /**
   * @param $type_name
   * @param $field
   *
   * @return array
   */
  public function getViewModeFormatterConfig($type_name, $field, $view_mode_short_name): array {
    $property_prefix = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $view_mode_short_name))));

    $label_property_name = "{$property_prefix}ViewModeLabelPosition";
    $type_property_name = "{$property_prefix}ViewModeType";
    $settings_property_name = "{$property_prefix}ViewModeSettings";

    $config = [
      'label'                => $this->$label_property_name,
      'type'                 => $this->$type_property_name,
      'settings'             => $this->$settings_property_name,
    ];
    return $config;
  }

  public function addToDisplayViewMode($type_name, $field, $view_mode_short_name) {

    $full_display_view_mode = \Drupal::service('entity_type.manager')->getStorage('entity_view_display')->load("node.{$type_name}.$view_mode_short_name");
    $layout_settings = $full_display_view_mode->getThirdPartySettings('layout_builder');
    $layout_region_config = $this->getViewModeLayoutSectionConfig($view_mode_short_name);
    foreach ($layout_settings['sections'] as $section) {
      if ($section->getLayoutId() === $layout_region_config['layout_id'])  {
        break;
      }
    }

    $config = [
      'id'              => "field_block:node:$type_name:{$field['field_name']}",
      'label'           => $field['label'],
      'provider'        => 'layout_builder',
      'label_display'   => '0',
      'context_mapping' =>
        [
          'entity'    => 'layout_builder.entity',
          'view_mode' => 'view_mode',
        ],
    ];

    $config['formatter'] = $this->getViewModeFormatterConfig($type_name, $field, $view_mode_short_name);

    $new_component = TRUE;
    $components = $section->getComponents();
    foreach ($components as $component) {
      if ($component->get('configuration')['id'] === "field_block:node:$type_name:{$field['field_name']}") {
        $component->setConfiguration($config);
        $component->setWeight($field['weight']);
        $component->setRegion($layout_region_config['region']);
        $new_component = FALSE;
        break;
      }
    }

    if ($new_component) {
      $component = (new SectionComponent(\Drupal::service('uuid')->generate(), $layout_region_config['region'], $config));
      $component->setConfiguration($config);
      $component->setWeight($field['weight']);
      $component->setRegion($layout_region_config['region']);
      $section->appendComponent($component);
    }

    $full_display_view_mode->setThirdPartySetting('layout_builder', 'sections', $layout_settings['sections']);
    $full_display_view_mode->save();
  }

}
