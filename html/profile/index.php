<?php declare(strict_types=1);

/**
 * Legacy /profile/ route — consolidated into Settings → Account.
 */
$target = '/settings/account/';
$query = (string) ($_SERVER['QUERY_STRING'] ?? '');
if ($query !== '') {
  $target .= '?' . $query;
}

header('Location: ' . $target, true, 301);
exit;
