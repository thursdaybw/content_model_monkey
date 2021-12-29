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

  protected $fullViewModeType = 'entity_reference_label';
  protected $fullViewModeSettings = [
    'link' => TRUE
  ];

  protected $searchIndexViewModeType = 'entity_reference_label';
  protected $searchIndexViewModeSettings = [
    'link' => FALSE,
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

    $string_utils = new StringUtils();
    $definition = $string_utils->trimPrefixFromString('ref@', $this->configuration['cmField']['type']);
    $parts = explode('/', $definition);
    $entity_type = $parts[0];
    $bundle      = $parts[1];

    return [
      'handler' => "default:$entity_type",
      'handler_settings' => [
        'target_bundles' => [
          $bundle => $bundle,
        ],
        'sort' => [
          'field' => '_none',
          'direction' => 'ASC',
        ],
        'auto_create' => FALSE,
      ],
    ];
  }

}
