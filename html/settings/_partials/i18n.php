<?php declare(strict_types=1);

namespace PayCal\Domain;

if (function_exists('settings_index_i18n') === false) {
  function settings_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}
