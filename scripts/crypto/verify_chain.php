<?php declare(strict_types=1);
/**
 * verify_chain.php — Validates the integrity of a cryptographic audit-chain JSON file.
 *
 * Usage: php verify_chain.php <path-to-chain.json>
 *
 * Each entry in the chain must have the fields:
 *   seq, timestamp, event, payload, hmac
 *
 * The HMAC for entry N is computed as:
 *   hash_hmac('sha256', json_encode(entry_without_hmac_field), prev_entry_hmac)
 * where prev_entry_hmac is an empty string for the first entry.
 *
 * Exits 0 if the chain is intact, 1 if any HMAC fails verification or the file is
 * malformed.
 *
 * Why this lives here: used by CryptoTamperDetectionTest (PHPUnit integration) as well
 * as any ops scripts that audit key-rotation logs.
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: php verify_chain.php <chain.json>\n");
    exit(2);
}

$file = $argv[1];
if (!file_exists($file)) {
    fwrite(STDERR, "File not found: {$file}\n");
    exit(2);
}

$raw = file_get_contents($file);
if ($raw === false) {
    fwrite(STDERR, "Cannot read file: {$file}\n");
    exit(2);
}

$chain = json_decode($raw, true);
if (!is_array($chain) || $chain === []) {
    fwrite(STDERR, "Invalid or empty JSON in {$file}\n");
    exit(1);
}

$required = ['seq', 'timestamp', 'event', 'payload', 'hmac'];
$prevHmac = '';

foreach ($chain as $i => $entry) {
    // Validate required fields
    foreach ($required as $field) {
        if (!array_key_exists($field, $entry)) {
            fwrite(STDERR, "Entry {$i}: missing required field '{$field}'\n");
            exit(1);
        }
    }

    $storedHmac = (string) $entry['hmac'];

    // Re-compute expected HMAC: sign all non-hmac fields, keyed by prev entry's HMAC.
    $payload = $entry;
    unset($payload['hmac']);
    // json_encode with stable key ordering for reproducibility.
    ksort($payload);
    $expected = hash_hmac('sha256', (string) json_encode($payload), $prevHmac);

    if (!hash_equals($expected, $storedHmac)) {
        fwrite(STDERR, "Chain integrity failure at entry {$i} (seq={$entry['seq']})\n");
        fwrite(STDERR, "  expected: {$expected}\n");
        fwrite(STDERR, "  stored:   {$storedHmac}\n");
        exit(1);
    }

    $prevHmac = $storedHmac;
}

echo "Chain OK: " . count($chain) . " entries verified.\n";
exit(0);
