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

  protected $fullViewModeLabelPosition = 'above';
  protected $fullViewModeType = 'basic_string';
  protected $fullViewModeSettings = [];

  protected $searchIndexViewModeType = 'basic_string';
  protected $searchIndexViewModeSettings = [];

  public function getFieldType() {
    return 'string_long';
  }

  public function getFieldWidgetOptions() {
    return [
      'type' => 'string_textarea',
    ];
  }

}
