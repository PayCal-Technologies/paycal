<?php declare(strict_types=1);

require __DIR__ . '/../html/config.php';

use PayCal\Domain\Database;
use PayCal\Domain\Keys;
use PayCal\Domain\WorkEntry;

/**
 * Usage:
 *   php scripts/crypto-census.php [--json]
 */

$asJson = in_array('--json', $argv, true);

$plaintextFields = [
    'hours', 'h',
    'regular_hours', 'r',
    'overtime_hours', 'o',
    'living_out_allowance', 'l',
    'travel_hours', 't',
    'wage', 'w',
    'gross', 'g',
    'tax', 'tx',
    'net',
    'site_name', 'n',
    'site_id', 's',
];

$keys = Database::scanKeys(Keys::WORK . ':*', 1000);

$stats = [
    'all_work_keys' => 0,
    'active_keys' => 0,
    'archived_keys' => 0,
    'with_blob' => 0,
    'with_valid_blob' => 0,
    'with_invalid_blob' => 0,
    'without_blob' => 0,
    'keys_with_plaintext_fields' => 0,
    'encrypted_only' => 0,
    'dual_write' => 0,
    'plaintext_only' => 0,
    'other' => 0,
];

foreach ($keys as $key) {
  $stats['all_work_keys']++;

  if (str_contains($key, ':archived:')) {
    $stats['archived_keys']++;
  } else {
    $stats['active_keys']++;
  }

  $entry = Database::hgetall($key);
  if (empty($entry)) {
    $stats['other']++;
    continue;
  }

  $blob = (string) ($entry['encrypted_blob'] ?? '');
  $hasBlob = $blob !== '';
  $blobValid = false;

  if ($hasBlob) {
    $stats['with_blob']++;
    $blobValidation = WorkEntry::validateEncryptedBlob($blob);
    $blobValid = $blobValidation['valid'] === true;
    if ($blobValid) {
      $stats['with_valid_blob']++;
    } else {
      $stats['with_invalid_blob']++;
    }
  } else {
    $stats['without_blob']++;
  }

  $hasPlaintext = false;
  foreach ($plaintextFields as $field) {
    if (isset($entry[$field]) && (string) $entry[$field] !== '') {
      $hasPlaintext = true;
      break;
    }
  }

  if ($hasPlaintext) {
    $stats['keys_with_plaintext_fields']++;
  }

  if ($blobValid && !$hasPlaintext) {
    $stats['encrypted_only']++;
  } elseif ($blobValid && $hasPlaintext) {
    $stats['dual_write']++;
  } elseif (!$hasBlob && $hasPlaintext) {
    $stats['plaintext_only']++;
  } else {
    $stats['other']++;
  }
}

if ($asJson) {
  echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit(0);
}

echo "PayCal Crypto Census" . PHP_EOL;
echo "====================" . PHP_EOL;
foreach ($stats as $label => $value) {
  echo str_pad($label, 28, ' ', STR_PAD_RIGHT) . ': ' . $value . PHP_EOL;
}

echo PHP_EOL;
echo "Interpretation:" . PHP_EOL;
echo "- plaintext_only > 0 means migration still required." . PHP_EOL;
echo "- dual_write > 0 means strip-plaintext migration is eligible." . PHP_EOL;
echo "- encrypted_only is target state for encrypted-at-rest." . PHP_EOL;
