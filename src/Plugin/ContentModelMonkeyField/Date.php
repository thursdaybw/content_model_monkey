<?php

namespace Drupal\content_model_monkey\Plugin\ContentModelMonkeyField;

use Drupal\content_model_monkey\ContentModelMonkeyFieldPluginBase;

/**
 * Plugin implementation of the content_model_monkey_field.
 *
 * @ContentModelMonkeyField(
 *   id = "Date",
 *   label = @Translation("Date"),
 *   description = @Translation("Date")
 * )
 */
class Date extends ContentModelMonkeyFieldPluginBase {

  protected $defaultViewModeFieldFormatterSettings = [
    'label' => 'inline',
    'type' => 'datetime_default',
    'settings' => [
      'timezone_override' => '',
      'format_type' => 'h_day_month_year',
    ],
  ];

  protected $secondaryViewModeFieldFormatterSettings = [
    'label' => 'hidden',
    'type' => 'datetime_default',
    'settings' => [
      'timezone_override' => '',
      'format_type' => 'h_day_month_year',
    ],
  ];

  protected $searchIndexViewModeFieldFormatterSettings = [
    'label' => 'hidden',
    'type' => 'datetime_default',
    'settings' => [
      'timezone_override' => '',
      'format_type' => 'h_day_month_year',
    ],
  ];

  public function getFieldType() {
    return 'datetime';
  }

  public function getFieldWidgetOptions() {
    return [
      'type' => 'datetime_default',
    ];
  }

  public function getFieldStorageSettings() {
    return [
      'datetime_type' => 'date',
    ];
  }

}
