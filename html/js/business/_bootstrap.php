<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

if (function_exists('org_js_index_i18n') === false) {
  function org_js_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');
Javascript::renderDocBlock();

?>
