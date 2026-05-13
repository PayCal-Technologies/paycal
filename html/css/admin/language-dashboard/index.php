<?php declare(strict_types=1);
/**
 * css/admin/language-dashboard/index.php
 *
 * Purpose: CSS endpoint for the admin language translation dashboard.
 * Served at: /css/admin/language-dashboard/?v=...
 * Linked from: html/admin/language-dashboard/index.php via Render::cssURL('admin/language-dashboard')
 */

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$cssFile = __DIR__ . '/../language-dashboard.css';
$content = @file_get_contents($cssFile);
if ($content !== false) {
  echo $content;
}
