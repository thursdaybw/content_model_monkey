<?php

namespace Drupal\content_model_monkey\Plugin\ContentModelMonkeyField;

use Drupal\content_model_monkey\ContentModelMonkeyFieldPluginBase;

/**
 * Plugin implementation of the content_model_monkey_field.
 *
 * @ContentModelMonkeyField(
 *   id = "HTML",
 *   label = @Translation("HTML"),
 *   description = @Translation("HTML")
 * )
 */
class Html extends ContentModelMonkeyFieldPluginBase {

  protected $fullViewModeLabelPosition = 'above';
  protected $fullViewModeType = 'text_default';
  protected $fullViewModeSettings = [];

  protected $searchIndexViewModeType = 'text_default';
  protected $searchIndexViewModeSettings = [];

  public function getFieldType() {
    return 'text_long';
  }

  public function getFieldWidgetOptions() {
    return [
      'type' => 'string_textarea',
    ];
  }

}
