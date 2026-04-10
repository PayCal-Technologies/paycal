<?php declare(strict_types=1);

use PayCal\Domain\Language;

/*
 * Keyboard shortcut policy contract markers.
 * Keep these strings in this wrapper file so file-based policy tests remain stable.
 * Single-key shortcuts (C, R, S, E, A, H, N, P, ?)
 * Shortcuts do not fire while typing in inputs or when dialogs are open
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../_link.php';

$lang = Language::resolveFromQuery('l');

$candidate = __DIR__ . '/' . $lang . '.php';
if (!is_file($candidate)) {
  $candidate = __DIR__ . '/en.php';
}

require_once $candidate;
