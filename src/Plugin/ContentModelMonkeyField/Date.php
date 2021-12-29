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

  protected $fullViewModeType = 'datetime_default';
  // @todo add view mode settings to content model for each field type.
  protected $fullViewModeSettings = [
    'timezone_override' => '',
    'format_type' => 'h_day_month_year',
  ];

  protected $searchIndexViewModeType = 'datetime_default';
  protected $searchIndexViewModeSettings = [
    'timezone_override' => '',
    'format_type' => 'h_medium_am_pm_zone',
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
