<?php declare(strict_types=1);

namespace PayCal\Domain;

/** Server managed protection for key material in Redis, independent of user DEKs. */
final class KeyMaterialVault
{
  public const PREFIX = 'pc-km:v1:';

  public static function isSensitiveField(string $key, string $field): bool
  {
    if (preg_match('/\Auser:[^:]+:passkey_wrapped_deks\z/', $key) === 1) {
      return true;
    }
    return preg_match('/\Auser:[^:]+\z/', $key) === 1
      && in_array($field, ['wrapped_dek_passkey', 'wrapped_dek_recovery', 'recovery_proof_key'], true);
  }

  /** @param array<string, string> $fields @return array<string, string> */
  public static function protectHash(string $key, array $fields): array
  {
    foreach ($fields as $field => $value) {
      if ($value !== '' && self::isSensitiveField($key, (string) $field)) {
        $fields[$field] = self::seal($key, (string) $field, $value);
      }
    }
    return $fields;
  }

  /** @param array<string, string> $fields @return array<string, string> */
  public static function openHash(string $key, array $fields): array
  {
    foreach ($fields as $field => $value) {
      if ($value !== '' && self::isSensitiveField($key, (string) $field)) {
        $fields[$field] = self::open($key, (string) $field, $value);
      }
    }
    return $fields;
  }

  public static function seal(string $key, string $field, string $value): string
  {
    if (str_starts_with($value, 'pc-km:')) {
      self::open($key, $field, $value);
      return $value;
    }
    $ring = self::keyring();
    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($value, 'aes-256-gcm', $ring['keys'][$ring['active']], OPENSSL_RAW_DATA, $nonce, $tag, self::aad($key, $field));
    if (!is_string($ciphertext) || strlen($tag) !== 16) {
      throw new \RuntimeException('Key material encryption failed.');
    }
    return self::PREFIX . base64_encode(json_encode([
      'kid' => $ring['active'], 'nonce' => base64_encode($nonce),
      'ciphertext' => base64_encode($ciphertext . $tag),
    ], JSON_THROW_ON_ERROR));
  }

  public static function open(string $key, string $field, string $value): string
  {
    if (!str_starts_with($value, 'pc-km:')) {
      return $value; // Legacy reader retained throughout migration and recovery.
    }
    if (!str_starts_with($value, self::PREFIX) || strlen($value) > 1048576) {
      throw new \RuntimeException('Unsupported key material envelope.');
    }
    $json = base64_decode(substr($value, strlen(self::PREFIX)), true);
    $envelope = is_string($json) ? json_decode($json, true, 8) : null;
    if (!is_array($envelope) || !is_string($envelope['kid'] ?? null)
      || !is_string($envelope['nonce'] ?? null) || !is_string($envelope['ciphertext'] ?? null)) {
      throw new \RuntimeException('Invalid key material envelope.');
    }
    $ring = self::keyring();
    $secret = $ring['keys'][$envelope['kid']] ?? null;
    $nonce = base64_decode($envelope['nonce'], true);
    $ciphertext = base64_decode($envelope['ciphertext'], true);
    if (!is_string($secret) || !is_string($nonce) || strlen($nonce) !== 12 || !is_string($ciphertext) || strlen($ciphertext) < 17) {
      throw new \RuntimeException('Key material key or envelope unavailable.');
    }
    $plaintext = openssl_decrypt(substr($ciphertext, 0, -16), 'aes-256-gcm', $secret, OPENSSL_RAW_DATA, $nonce, substr($ciphertext, -16), self::aad($key, $field));
    if (!is_string($plaintext)) {
      throw new \RuntimeException('Key material authentication failed.');
    }
    return $plaintext;
  }

  /** Keep previous key ids in this file when selecting a new active key. */
  private static function keyring(): array
  {
    $path = $_ENV['PAYCAL_KEY_MATERIAL_KEYRING_FILE'] ?? getenv('PAYCAL_KEY_MATERIAL_KEYRING_FILE') ?: '/etc/paycal/key-material-keyring.json';
    if (!is_string($path) || !is_file($path)) {
      throw new \RuntimeException('Key material keyring unavailable.');
    }
    $json = file_get_contents($path, false, null, 0, 65537);
    $ring = is_string($json) && strlen($json) <= 65536 ? json_decode($json, true, 8) : null;
    if (!is_array($ring) || !is_string($ring['active'] ?? null) || !is_array($ring['keys'] ?? null)) {
      throw new \RuntimeException('Invalid key material keyring.');
    }
    $keys = [];
    foreach ($ring['keys'] as $id => $encoded) {
      $raw = is_string($encoded) ? base64_decode($encoded, true) : false;
      if (!is_string($id) || preg_match('/\A[a-zA-Z0-9_-]{1,64}\z/', $id) !== 1 || !is_string($raw) || strlen($raw) !== 32) {
        throw new \RuntimeException('Invalid key material keyring entry.');
      }
      $keys[$id] = $raw;
    }
    if (!isset($keys[$ring['active']])) {
      throw new \RuntimeException('Active key material key unavailable.');
    }
    return ['active' => $ring['active'], 'keys' => $keys];
  }

  private static function aad(string $key, string $field): string
  {
    return "paycal-key-material-v1\0" . $key . "\0" . $field;
  }
}
