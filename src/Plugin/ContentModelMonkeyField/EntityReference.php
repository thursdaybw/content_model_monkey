<?php

namespace Drupal\content_model_monkey\Plugin\ContentModelMonkeyField;

use Drupal\content_model_monkey\ContentModelMonkeyFieldPluginBase;
use Drupal\content_model_monkey\StringUtils;

/**
 * Plugin implementation of the content_model_monkey_field.
 *
 * @ContentModelMonkeyField(
 *   id = "Entity reference",
 *   label = @Translation("Entity reference"),
 *   description = @Translation("Entity reference")
 * )
 */
class EntityReference extends ContentModelMonkeyFieldPluginBase {

  protected $defaultViewModeFieldFormatterSettings = [
    'label' => 'inline',
    'type' => 'entity_reference_label',
    'settings' => [
      'link' => TRUE,
    ],
  ];

  protected $secondaryViewModeFieldFormatterSettings = [
    'label' => 'hidden',
    'type' => 'entity_reference_label',
    'settings' => [
      'link' => TRUE,
    ],
  ];

  protected $searchIndexViewModeFieldFormatterSettings = [
    'label' => 'hidden',
    'type' => 'entity_reference_label',
    'settings' => [
      'link' => FALSE,
    ],
  ];

  public function getFieldType() {
    return 'entity_reference';
  }

  public function getFieldWidgetOptions() {
    return [
      'type' => 'entity_reference_autocomplete',
    ];

  }

  public function getFieldConfigSettings() {

    $cm_config = $this->getFieldSettingsFromContentModel();

    $settings = [
      'handler' => "default:{$cm_config['entity_type']}",
      'handler_settings' => [
        'target_bundles' => [
          $cm_config['bundle'] => $cm_config['bundle'],
        ],
        'sort' => [
          'field' => '_none',
        ],
        'auto_create' => FALSE,
      ],
    ];

    if ($cm_config['entity_type'] === 'media') {
      $settings['handler_settings']['auto_create_bundle'] = $cm_config['bundle'];
    }

    return $settings;
  }


  public function getFieldStorageSettings() {
    $cm_config = $this->getFieldSettingsFromContentModel();
    return [
      'target_type' => $cm_config['entity_type'],
    ];
  }

  private function getFieldSettingsFromContentModel() {
    $string_utils = new StringUtils();
    $definition = $string_utils->trimPrefixFromString('ref@', $this->configuration['cmField']['type']);
    $parts = explode('/', $definition);
    return  [
      'entity_type' => $parts[0],
      'bundle'      => $parts[1],
    ];
  }

}
