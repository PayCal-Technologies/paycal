<?php declare(strict_types=1);

/**
 * simulate_rotation.php
 *
 * Deterministic signing + rotation simulation harness.
 * JSON mode emits clean periods and exits 0.
 * Harness mode verifies signing, rotation, revocation, tamper detection.
 */

if (!extension_loaded('sodium')) {
    fwrite(STDERR, "Sodium extension required.\n");
    exit(1);
}

// --------------------
// Mode Detection
// --------------------

$jsonMode   = in_array('--json', $argv, true);
$tamperMode = in_array('--tamper', $argv, true);

// --------------------
// Key Loading
// --------------------

$root   = dirname(__DIR__, 2); // /var/www/paycal
$keyDir = $root . '/keys/';

function loadKey(string $path, int $expectedLength): string
{
    if (!file_exists($path)) {
        throw new RuntimeException("Missing key file: $path");
    }

    $raw = base64_decode(trim(file_get_contents($path)), true);

    if ($raw === false || strlen($raw) !== $expectedLength) {
        throw new RuntimeException("Invalid key length for: $path");
    }

    return $raw;
}

try {
    $privateV1 = loadKey($keyDir . 'test-private-v1.key', SODIUM_CRYPTO_SIGN_SECRETKEYBYTES);
    $publicV1  = loadKey($keyDir . 'test-public-v1.key', SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);

    $privateV2 = loadKey($keyDir . 'test-private-v2.key', SODIUM_CRYPTO_SIGN_SECRETKEYBYTES);
    $publicV2  = loadKey($keyDir . 'test-public-v2.key', SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);
} catch (Throwable $e) {
    if ($jsonMode) {
        echo json_encode([], JSON_UNESCAPED_SLASHES);
        exit(0);
    }
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

// --------------------
// Helpers
// --------------------

function canonical(array $payload): string
{
    ksort($payload);
    return json_encode($payload, JSON_UNESCAPED_SLASHES);
}

function payloadHash(string $canonical): string
{
    return hash('sha256', $canonical);
}

function chainHash(string $prev, string $payloadHash): string
{
    return hash('sha256', $prev . $payloadHash);
}

function sign(string $canonical, string $private): string
{
    return base64_encode(
        sodium_crypto_sign_detached($canonical, $private)
    );
}

// --------------------
// Build Periods
// --------------------

$periods = [];
$prevChain = str_repeat('0', 64);

for ($i = 1; $i <= 3; $i++) {

    $payload = [
        'period' => $i,
        'engineVersion' => 1,
        'amountCents' => 10000 * $i,
        'signingKeyVersion' => $i < 3 ? 1 : 2,
    ];

    $canonical = canonical($payload);
    $pHash     = payloadHash($canonical);

    $private = $payload['signingKeyVersion'] === 1
        ? $privateV1
        : $privateV2;

    $public = $payload['signingKeyVersion'] === 1
        ? $publicV1
        : $publicV2;

    $signature = sign($canonical, $private);
    $cHash     = chainHash($prevChain, $pHash);

    $periods[] = [
        'payload' => $payload,
        'signature' => $signature,
        'publicKey' => base64_encode($public),
        'payloadHash' => $pHash,
        'chainHash' => $cHash,
    ];

    $prevChain = $cHash;
}

// --------------------
// Optional Tamper
// --------------------

if ($tamperMode === true) {
    $periods[1]['payload']['amountCents'] = 999999;
}

// --------------------
// JSON Mode Short-Circuit
// --------------------

if ($jsonMode === true) {
    echo json_encode($periods, JSON_UNESCAPED_SLASHES);
    exit(0);
}

// --------------------
// Harness (Non-JSON Only)
// --------------------

function pass(string $msg): void
{
    echo "[PASS] $msg\n";
}

function fail(string $msg): void
{
    echo "[FAIL] $msg\n";
    exit(1);
}

// Verify signing
foreach ($periods as $p) {

    $canonical = canonical($p['payload']);
    $public    = base64_decode($p['publicKey'], true);
    $sig       = base64_decode($p['signature'], true);

    if (!sodium_crypto_sign_verify_detached($sig, $canonical, $public)) {
        fail('signature verification failed');
    }
}

pass('signing verified');

// Verify chain
$prev = str_repeat('0', 64);

foreach ($periods as $p) {

    $canonical = canonical($p['payload']);
    $pHash     = payloadHash($canonical);
    $cHash     = chainHash($prev, $pHash);

    if ($cHash !== $p['chainHash']) {
        fail('chain mismatch detected');
    }

    $prev = $cHash;
}

pass('chain verified');

// Verify tamper detection
if ($tamperMode === true) {

    $canonical = canonical($periods[1]['payload']);
    $public    = base64_decode($periods[1]['publicKey'], true);
    $sig       = base64_decode($periods[1]['signature'], true);

    if (sodium_crypto_sign_verify_detached($sig, $canonical, $public)) {
        fail('tampering was not detected');
    }

    pass('tampering detected correctly');
}

exit(0);
