<?php

declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\Strings;

/**
 * Premium landing page.
 *
 * Purpose: Conversion-oriented upgrade page explaining Premium value, team
 * features, pricing, and FAQ. Accessible to both authenticated and guest users.
 *
 * The CTA buttons route authenticated users to /profile#panel-billing and
 * unauthenticated users to /auth to create an account first.
 */
$currentPage  = 'PAGE_PREMIUM';
$pageTitle    = 'Premium — PayCal';
$pageLanguage = 'en-CA';

require_once '../config.php';

if (function_exists('premium_i18n') === false) {
  function premium_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }
    return $cache[$key];
  }
}

$isAuthenticated = Authentication::getCookie() !== '';
$ctaHref         = $isAuthenticated ? '/profile#panel-billing' : '/auth';

\PayCal\Observability\Lens::boot('premium');

require_once HTML.'/header.php';

$lang = defined('USER_LANGUAGE') ? (string) USER_LANGUAGE : 'en';
$langTemplate = __DIR__.'/'.$lang.'/index.php';
if (!is_file($langTemplate)) {
  $langTemplate = __DIR__.'/en/index.php';
}
require_once $langTemplate;

require_once HTML.'/footer.php';
