<?php declare(strict_types=1);

/**
 * Legacy /earnings/ route — redirects to /reports/ (query string preserved).
 */
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$target = '/reports/';
if (is_string($queryString) && $queryString !== '') {
  $target .= '?' . $queryString;
}

header('Location: ' . $target, true, 302);
exit;
