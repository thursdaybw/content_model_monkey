<?php

namespace Drupal\content_model_monkey;

/**
 * Interface for content_model_monkey_field plugins.
 */
interface ContentModelMonkeyFieldInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

}
