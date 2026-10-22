<?php declare(strict_types=1);

/** Check the executing SAPI, not a potentially different CLI installation. */
$minimums = ['8.2' => '8.2.35', '8.3' => '8.3.36', '8.4' => '8.4.27', '8.5' => '8.5.12'];
$branch = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
$patched = isset($minimums[$branch]) && version_compare(PHP_VERSION, $minimums[$branch], '>=');
$settings = [];
foreach (['session.upload_progress.enabled', 'file_uploads', 'memory_limit', 'post_max_size', 'max_input_nesting_level', 'max_input_vars', 'max_multipart_body_parts'] as $key) {
  $settings[$key] = ini_get($key);
}
$checks = [
  'supported_patched_branch' => $patched,
  'upload_progress_disabled' => !filter_var($settings['session.upload_progress.enabled'], FILTER_VALIDATE_BOOLEAN),
  'file_uploads_disabled' => !filter_var($settings['file_uploads'], FILTER_VALIDATE_BOOLEAN),
];
$ok = !in_array(false, $checks, true);
if (PHP_SAPI !== 'cli') {
  header('Content-Type: application/json');
  http_response_code($ok ? 200 : 503);
}
echo json_encode(['ok' => $ok, 'php' => PHP_VERSION, 'sapi' => PHP_SAPI, 'checks' => $checks, 'settings' => $settings], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
// Keep this script outside the web root. Query FPM through its local socket.
if (PHP_SAPI === 'cli') {
  exit($ok ? 0 : 1);
}
