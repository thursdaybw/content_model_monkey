<?php

namespace Drupal\content_model_monkey\Plugin\ContentModelMonkeyField;

use Drupal\content_model_monkey\ContentModelMonkeyFieldPluginBase;

/**
 * Plugin implementation of the content_model_monkey_field.
 *
 * @ContentModelMonkeyField(
 *   id = "Boolean",
 *   label = @Translation("Boolean"),
 *   description = @Translation("Boolean")
 * )
 */
class Boolean extends ContentModelMonkeyFieldPluginBase {

  protected $defaultViewModeFieldFormatterSettings = [
    'label' => 'inline',
    'type' => 'boolean',
    'settings' => [
      'link_to_entity' => FALSE,
    ],
  ];

  protected $searchIndexViewModeFieldFormatterSettings = [
    'label_position' => 'hidden',
    'field_type' => 'datetime_default',
    'settings' => [
      'link_to_entity' => FALSE,
    ],
  ];

  protected $fullViewModeType = 'boolean';
  protected $searchIndexViewModeType = 'boolean';

  public function getFieldType() {
    return 'boolean';
  }

  public function getFieldWidgetOptions() {
    return [
      'type' => 'boolean_checkbox',
    ];
  }

}
