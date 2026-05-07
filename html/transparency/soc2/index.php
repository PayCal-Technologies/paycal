<?php declare(strict_types=1);

use PayCal\Domain\Language;

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../_link.php';

$lang = Language::resolveFromQuery('l');
$candidate = __DIR__ . '/' . $lang . '.php';
if (!is_file($candidate)) {
  $candidate = __DIR__ . '/en.php';
}

require_once $candidate;
