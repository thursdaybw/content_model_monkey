<?php

namespace Drupal\content_model_monkey\Plugin\ContentModelMonkeyField;

use Drupal\content_model_monkey\ContentModelMonkeyFieldPluginBase;

/**
 * Plugin implementation of the content_model_monkey_field.
 *
 * @ContentModelMonkeyField(
 *   id = "Text (255)",
 *   label = @Translation("Text"),
 *   description = @Translation("Text (255)")
 * )
 */
class Text extends ContentModelMonkeyFieldPluginBase {

  public function getFieldType() {
    return 'string';
  }

  public function getFieldWidgetOptions() {
    return [
      'type' => 'string_textfield',
    ];
  }

}
