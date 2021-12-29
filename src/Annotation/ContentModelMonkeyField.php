<?php

namespace Drupal\content_model_monkey\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines content_model_monkey_field annotation object.
 *
 * @Annotation
 */
class ContentModelMonkeyField extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
