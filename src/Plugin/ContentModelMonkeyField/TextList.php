<?php

namespace Drupal\content_model_monkey\Plugin\ContentModelMonkeyField;

use Drupal\content_model_monkey\ContentModelMonkeyFieldPluginBase;

/**
 * Plugin implementation of the content_model_monkey_field.
 *
 * @ContentModelMonkeyField(
 *   id = "List (text)",
 *   label = @Translation("Select list of text."),
 *   description = @Translation("Select list text")
 * )
 */
class TextList extends ContentModelMonkeyFieldPluginBase {

  public function getFieldType() {
    return 'list_string';
  }

  public function getFieldWidgetOptions() {
    return [
      'type' => 'options_select',
    ];
  }

  public function getFieldStorageSettings() {
    return [
      'allowed_values' =>  [
        'biological' => 'Biological',
        'blood-tissues-bio' => 'Blood, tissues, and biologicals',
        'listed-comp-medicines' => 'Listed complementary medicines',
        'medical-device' => 'Medical Device',
        'medicine' => 'Medicine',
        'other-therapeutic-good' => 'Other Therapeutic Good',
        'other-therapeutic-good-listed' => 'Other therapeutic goods listed',
        'over-the-counter-medicines' => 'Over-the-counter medicines',
        'prescription-medicines' => 'Prescription medicines',
      ]
    ];
  }

}
