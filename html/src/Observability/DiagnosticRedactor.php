<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * Central privacy redaction for Argus diagnostic context.
 *
 * Returns null when context contains blocked secret material (fail closed).
 */
final class DiagnosticRedactor
{
  /** @var array<string, true> */
  private const BLOCKED_KEYS = [
    'user_uuid' => true,
    'uuid' => true,
    'userid' => true,
    'user_id' => true,
    'session_id' => true,
    'sessionid' => true,
    'cookie' => true,
    'csrf_token' => true,
    'token' => true,
    'access_token' => true,
    'refresh_token' => true,
    'api_key' => true,
    'secret' => true,
    'password' => true,
    'passkey' => true,
    'credential' => true,
    'encrypted' => true,
    'ciphertext' => true,
    'blob' => true,
    'ip' => true,
    'ip_address' => true,
    'email' => true,
    'phone' => true,
    'address' => true,
    'full_name' => true,
    'name' => true,
    'wage' => true,
    'wages' => true,
    'salary' => true,
    'gross' => true,
    'net' => true,
    'tax' => true,
    'taxes' => true,
    'deduction' => true,
    'deductions' => true,
    'cpp' => true,
    'ei' => true,
    'payroll' => true,
    'earnings' => true,
    'stripe_payload' => true,
    'stripe_event' => true,
    'stripe_customer' => true,
    'card_number' => true,
    'cvv' => true,
    'authorization' => true,
  ];

  /**
   * @param array<string, mixed> $context
   * @return array<string, scalar|null>|null
   */
  public static function redact(array $context): ?array
  {
    $limits = TraceGatePolicy::captureLimits();
    $maxKeys = ConfigValue::int($limits['max_context_keys'] ?? null, 32);
    $maxBytes = ConfigValue::int($limits['max_payload_bytes'] ?? null, 8192);

    $out = [];
    $count = 0;

    foreach ($context as $key => $value) {
      if ($count >= $maxKeys) {
        break;
      }

      $normalizedKey = strtolower(trim($key));
      if ($normalizedKey === '' || self::isBlockedKey($normalizedKey)) {
        return null;
      }

      if (!is_scalar($value) && $value !== null) {
        $out[$normalizedKey] = '[unsupported]';
        $count++;
        continue;
      }

      if ($value === null) {
        $out[$normalizedKey] = null;
        $count++;
        continue;
      }

      $scalar = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
      $redacted = self::redactScalar($scalar);
      if ($redacted === null) {
        return null;
      }

      $out[$normalizedKey] = $redacted;
      $count++;
    }

    $encoded = json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded !== false && strlen($encoded) > $maxBytes) {
      return null;
    }

    return $out;
  }

  /**
   * Return whether a context key is too sensitive to capture.
   */
  private static function isBlockedKey(string $key): bool
  {
    if ($key === 'email_token') {
      return false;
    }

    if (isset(self::BLOCKED_KEYS[$key])) {
      return true;
    }

    return 1 === preg_match(
      '/(?:uuid|session|token|passkey|encrypt|wage|salary|tax|payroll|earning|deduction|email|phone|stripe|secret|password)/',
      $key
    );
  }

  /**
   * Redact or truncate a scalar diagnostic context value.
   */
  private static function redactScalar(string $value): ?string
  {
    $trimmed = trim($value);
    if ($trimmed === '') {
      return '';
    }

    $maxLen = ConfigValue::int(TraceGatePolicy::captureLimits()['max_string_length'] ?? null, 256);
    if (strlen($trimmed) > $maxLen) {
      return substr($trimmed, 0, $maxLen) . '…';
    }

    if (filter_var($trimmed, FILTER_VALIDATE_EMAIL) !== false) {
      return null;
    }

    if (1 === preg_match('/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', $trimmed)) {
      return null;
    }

    if (1 === preg_match('/^(eyJ[a-zA-Z0-9_-]{8,}\.[a-zA-Z0-9._-]{8,}\.[a-zA-Z0-9._-]{8,})$/', $trimmed)) {
      return null;
    }

    if (1 === preg_match('/^\$2[aby]\$\d{2}\$/', $trimmed)) {
      return null;
    }

    if (1 === preg_match('/^sk_(live|test)_/', $trimmed)) {
      return null;
    }

    if (1 === preg_match('/^whsec_/', $trimmed)) {
      return null;
    }

    if (strlen($trimmed) >= 48 && 1 === preg_match('/^[A-Za-z0-9+\/=_-]+$/', $trimmed)) {
      return null;
    }

    return $trimmed;
  }
}
