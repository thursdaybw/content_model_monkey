<?php

namespace Drupal\content_model_monkey\Plugin\ContentModelMonkeyField;

use Drupal\content_model_monkey\ContentModelMonkeyFieldPluginBase;

/**
 * Plugin implementation of the content_model_monkey_field.
 *
 * @ContentModelMonkeyField(
 *   id = "Text (long)",
 *   label = @Translation("Text (long)"),
 *   description = @Translation("Text (long)")
 * )
 */
class TextLong extends ContentModelMonkeyFieldPluginBase {

  protected $defaultViewModeFieldFormatterSettings = [
    'label' => 'above',
    'type' => 'basic_string',
    'settings' => [
      'link_to_entity' => FALSE,
    ],
  ];

  protected $secondaryViewModeFieldFormatterSettings = [
    'label' => 'hidden',
    'type' => 'basic_string',
    'settings' => [
      'link_to_entity' => FALSE,
    ],
  ];

  protected $searchIndexViewModeFieldFormatterSettings = [
    'label' => 'hidden',
    'type' => 'basic_string',
    'settings' => [],
  ];

  public function getFieldType() {
    return 'string_long';
  }

  public function getFieldWidgetOptions() {
    return [
      'type' => 'string_textarea',
    ];
  }

}
