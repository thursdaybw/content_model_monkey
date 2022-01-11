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

  protected $defaultViewModeFieldFormatterSettings = [
    'label' => 'above',
    'type' => 'text_default',
    'settings' => [],
  ];

  protected $secondaryViewModeFieldFormatterSettings = [
    'label' => 'inline',
    'type' => 'text_default',
    'settings' => [],
  ];

  protected $searchIndexViewModeFieldFormatterSettings = [
    'label' => 'hidden',
    'type' => 'text_default',
    'settings' => [],
  ];

  public function getFieldType() {
    return 'text_long';
  }

  public function getFieldWidgetOptions() {
    return [
      'type' => 'string_textarea',
    ];
  }

}
