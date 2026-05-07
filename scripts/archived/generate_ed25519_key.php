<?php
// Ed25519 key generation script for PayCal user
// Usage: Run this script to generate a base64-encoded Ed25519 private key for your user UUID

$userUUID = 'Ub9127d01';
$keyVersion = 1;
$keyDir = __DIR__ . '/../dev/keys/';
$keyFile = $keyDir . $userUUID . "-private-signing-v" . $keyVersion . ".key";

if (!extension_loaded('sodium')) {
    fwrite(STDERR, "Sodium extension required.\n");
    exit(1);
}

if (!is_dir($keyDir)) {
    mkdir($keyDir, 0700, true);
}

$keypair = sodium_crypto_sign_keypair();
$privateKey = sodium_crypto_sign_secretkey($keypair);
$publicKey = sodium_crypto_sign_publickey($keypair);

file_put_contents($keyFile, base64_encode($privateKey) . "\n");

echo "Private key written to: $keyFile\n";
echo "Public key (base64): " . base64_encode($publicKey) . "\n";
