<?php

namespace Drupal\content_model_monkey;

class StringUtils {

  /**
   * Test if a string starts with another string.
   *
   * @param string $haystack
   *   The string to check has the start string.
   * @param string $needle
   *   The start string to check for.
   *
   * @return bool
   *   TRUE if $needle starts with $haystack.
   */
  public function strStartsWith(string $haystack, string $needle) {
    return strpos($haystack, $needle) === 0;
  }

  /**
   * Remove a string from the start of a string.
   *
   * @param string $prefix
   *   The string to be removed.
   * @param string $string
   *   The string to be trimmed.
   *
   * @return string
   *   The string with its prefix removed.
   */
  public function trimPrefixFromString(string $prefix, string $string) {
    if (substr($string, 0, strlen($prefix)) == $prefix) {
      $content_type_name = substr($string, strlen($prefix));
    }

    return $content_type_name;
  }

}
