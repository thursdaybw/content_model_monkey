<?php

namespace Drupal\content_model_monkey\Plugin\ContentModelMonkeyField;

use Drupal\content_model_monkey\ContentModelMonkeyFieldPluginBase;

/**
 * Plugin implementation of the content_model_monkey_field.
 *
 * @ContentModelMonkeyField(
 *   id = "Link: Title and URL",
 *   label = @Translation("Link"),
 *   description = @Translation("Link")
 * )
 */
class Link extends ContentModelMonkeyFieldPluginBase {

  protected $defaultViewModeFieldFormatterSettings = [
    'label' => 'inline',
    'type' => 'link',
    'settings' => [
      'trim_length' => NULL,
      'url_only'    => FALSE,
      'url_plain'   => FALSE,
      'rel'         => 0,
      'target'      => 0,
    ],
  ];

  protected $searchIndexViewModeFieldFormatterSettings = [
    'label' => 'hidden',
    'type' => 'link_separate',
    'settings' => [
      'trim_length' => NULL,
      'url_only'    => FALSE,
      'url_plain'   => FALSE,
      'rel'         => 0,
      'target'      => 0,
    ],
  ];

  public function getFieldType() {
    return 'link';
  }

  public function getFieldWidgetOptions() {
    return [
      'type' => 'link_default',
      'placeholder_url' => '',
      'placeholder_title' => '',
    ];
  }

}
