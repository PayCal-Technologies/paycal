<?php declare(strict_types=1);

namespace PayCal\Observability;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

/**
 * Runtime Argus capture state (Redis-backed): toggles, expiry, scope.
 */
final class ArgusPackageStore
{
  private const STATE_KEY = Keys::SYSTEM . ':argus:runtime';
  private const FIELD_MASTER = 'master_enabled';
  private const FIELD_MASTER_EXPIRES = 'master_expires_at';
  private const FIELD_UPDATED_AT = 'updated_at';
  private const FIELD_UPDATED_BY = 'updated_by';
  private const FIELD_SCOPE_USER = 'scope_user_uuid';
  private const FIELD_SCOPE_BUSINESS = 'scope_business_id';
  private const FIELD_SCOPE_SESSION = 'scope_session_hash';
  private const FIELD_SCOPE_REQUEST = 'scope_request_id';
  private const FIELD_SCOPE_ROUTE = 'scope_route';
  private const PACKAGE_PREFIX = 'pkg:';
  private const PACKAGE_EXPIRES_SUFFIX = ':expires_at';

  /** @var array<string, string>|null */
  private static ?array $cache = null;

  /**
   * Reset for tests.
   */
  public static function resetForTests(): void
  {
    self::$cache = null;
    Database::del(self::STATE_KEY);
  }

  /**
   * Purge expired.
   */
  public static function purgeExpired(): void
  {
    $state = self::loadState();
    $now = time();
    $changed = false;

    $masterExpires = (int) ($state[self::FIELD_MASTER_EXPIRES] ?? 0);
    if ($masterExpires > 0 && $now >= $masterExpires) {
      self::writeFields([
        self::FIELD_MASTER => '',
        self::FIELD_MASTER_EXPIRES => '',
      ], 'system');
      $changed = true;
    }

    foreach ($state as $field => $value) {
      if (!str_starts_with($field, self::PACKAGE_PREFIX)) {
        continue;
      }
      if (str_ends_with($field, self::PACKAGE_EXPIRES_SUFFIX)) {
        continue;
      }
      if ($value !== '0' && $value !== '1') {
        continue;
      }

      $moduleId = substr($field, strlen(self::PACKAGE_PREFIX));
      if ($moduleId === '') {
        continue;
      }

      $expiresAt = (int) ($state[$field . self::PACKAGE_EXPIRES_SUFFIX] ?? 0);
      if ($expiresAt > 0 && $now >= $expiresAt) {
        self::writeFields([
          $field => '',
          $field . self::PACKAGE_EXPIRES_SUFFIX => '',
        ], 'system');
        $changed = true;
      }
    }

    if ($changed) {
      self::$cache = null;
    }
  }

  /**
   * Master enabled.
   */
  public static function masterEnabled(): ?bool
  {
    self::purgeExpired();
    $raw = self::readField(self::FIELD_MASTER);
    if ($raw === null || $raw === '') {
      return null;
    }

    return $raw === '1';
  }

  /**
   * Master expires at.
   */
  public static function masterExpiresAt(): ?int
  {
    $raw = self::readField(self::FIELD_MASTER_EXPIRES);
    if ($raw === null || $raw === '') {
      return null;
    }

    $expires = (int) $raw;

    return $expires > 0 ? $expires : null;
  }

  /**
   * @return array<string, mixed>|null
   */
  public static function packageRecord(string $moduleId): ?array
  {
    self::purgeExpired();
    $normalized = self::normalizeModuleId($moduleId);
    if ($normalized === '') {
      return null;
    }

    $enabledRaw = self::readField(self::PACKAGE_PREFIX . $normalized);
    if ($enabledRaw === null || $enabledRaw === '') {
      return null;
    }

    $expiresRaw = self::readField(self::PACKAGE_PREFIX . $normalized . self::PACKAGE_EXPIRES_SUFFIX);
    $expiresAt = ($expiresRaw !== null && $expiresRaw !== '') ? (int) $expiresRaw : null;

    return [
      'module' => $normalized,
      'enabled' => $enabledRaw === '1',
      'expires_at' => $expiresAt,
    ];
  }

  /**
   * Package override.
   */
  public static function packageOverride(string $moduleId): ?bool
  {
    $record = self::packageRecord($moduleId);

    return $record === null ? null : (bool) $record['enabled'];
  }

  /**
   * Package expires at.
   */
  public static function packageExpiresAt(string $moduleId): ?int
  {
    $record = self::packageRecord($moduleId);
    if ($record === null) {
      return null;
    }

    $expires = $record['expires_at'] ?? null;

    return is_int($expires) && $expires > 0 ? $expires : null;
  }

  /**
   * Capture scope.
   */
  public static function captureScope(): ArgusCaptureScope
  {
    return new ArgusCaptureScope(
      (string) (self::readField(self::FIELD_SCOPE_USER) ?? ''),
      (string) (self::readField(self::FIELD_SCOPE_BUSINESS) ?? ''),
      (string) (self::readField(self::FIELD_SCOPE_SESSION) ?? ''),
      (string) (self::readField(self::FIELD_SCOPE_REQUEST) ?? ''),
      (string) (self::readField(self::FIELD_SCOPE_ROUTE) ?? ''),
    );
  }

  /**
   * Set capture scope.
   */
  public static function setCaptureScope(ArgusCaptureScope $scope, string $adminUuid): void
  {
    self::writeFields([
      self::FIELD_SCOPE_USER => $scope->userUuid,
      self::FIELD_SCOPE_BUSINESS => $scope->businessId,
      self::FIELD_SCOPE_SESSION => $scope->sessionHash,
      self::FIELD_SCOPE_REQUEST => $scope->requestId,
      self::FIELD_SCOPE_ROUTE => $scope->route,
    ], $adminUuid);
  }

  /**
   * Set master enabled.
   */
  public static function setMasterEnabled(
    bool $enabled,
    string $adminUuid,
    ?int $expiresAt = null,
  ): void {
    self::writeFields([
      self::FIELD_MASTER => $enabled ? '1' : '0',
      self::FIELD_MASTER_EXPIRES => ($enabled && $expiresAt !== null && $expiresAt > 0)
        ? (string) $expiresAt
        : '',
    ], $adminUuid);
  }

  /**
   * Set package enabled.
   */
  public static function setPackageEnabled(
    string $moduleId,
    bool $enabled,
    string $adminUuid,
    ?int $expiresAt = null,
  ): void {
    $normalized = self::normalizeModuleId($moduleId);
    if ($normalized === '' || !TraceGatePolicy::isKnownModule($normalized)) {
      return;
    }

    if (!$enabled) {
      self::writeFields([
        self::PACKAGE_PREFIX . $normalized => '0',
        self::PACKAGE_PREFIX . $normalized . self::PACKAGE_EXPIRES_SUFFIX => '',
      ], $adminUuid);

      return;
    }

    self::writeFields([
      self::PACKAGE_PREFIX . $normalized => '1',
      self::PACKAGE_PREFIX . $normalized . self::PACKAGE_EXPIRES_SUFFIX => ($expiresAt !== null && $expiresAt > 0)
        ? (string) $expiresAt
        : '',
    ], $adminUuid);
  }

  /**
   * Clear package override.
   */
  public static function clearPackageOverride(string $moduleId, string $adminUuid): void
  {
    $normalized = self::normalizeModuleId($moduleId);
    if ($normalized === '') {
      return;
    }

    self::writeFields([
      self::PACKAGE_PREFIX . $normalized => '',
      self::PACKAGE_PREFIX . $normalized . self::PACKAGE_EXPIRES_SUFFIX => '',
    ], $adminUuid);
  }

  /**
   * @param array<int, string> $moduleIds
   */
  public static function enablePreset(
    array $moduleIds,
    string $adminUuid,
    ?int $expiresAt = null,
  ): void {
    foreach ($moduleIds as $moduleId) {
      self::setPackageEnabled($moduleId, true, $adminUuid, $expiresAt);
    }
  }

  /**
   * @return array<string, mixed>
   */
  public static function packageOverridesDetailed(): array
  {
    self::purgeExpired();
    $state = self::loadState();
    $out = [];

    foreach ($state as $field => $value) {
      if (!str_starts_with($field, self::PACKAGE_PREFIX)) {
        continue;
      }
      if (str_ends_with($field, self::PACKAGE_EXPIRES_SUFFIX)) {
        continue;
      }
      if ($value !== '0' && $value !== '1') {
        continue;
      }

      $moduleId = substr($field, strlen(self::PACKAGE_PREFIX));
      if ($moduleId === '') {
        continue;
      }

      $expiresAt = (int) ($state[$field . self::PACKAGE_EXPIRES_SUFFIX] ?? 0);
      $out[$moduleId] = [
        'enabled' => $value === '1',
        'expires_at' => $expiresAt > 0 ? $expiresAt : null,
      ];
    }

    ksort($out);

    return $out;
  }

  /**
   * @return array<string, mixed>
   */
  public static function snapshot(): array
  {
    self::purgeExpired();
    $state = self::loadState();
    $scope = self::captureScope();

    return [
      'master_override' => self::masterEnabled(),
      'master_expires_at' => self::masterExpiresAt(),
      'master_effective' => TraceGatePolicy::masterEffectiveStatus(),
      'master_gate_open' => TraceGatePolicy::isMasterEnabled(),
      'capture_scope' => $scope->toArray(),
      'capture_scope_active' => $scope->isActive(),
      'package_overrides' => self::packageOverridesDetailed(),
      'duration_options' => ArgusExpiryPolicy::durationOptions(),
      'updated_at' => (string) ($state[self::FIELD_UPDATED_AT] ?? ''),
      'updated_by' => (string) ($state[self::FIELD_UPDATED_BY] ?? ''),
    ];
  }

  /**
   * Normalize module ID.
   */
  private static function normalizeModuleId(string $moduleId): string
  {
    return strtolower(trim($moduleId));
  }

  /**
   * Read field.
   */
  private static function readField(string $field): ?string
  {
    $state = self::loadState();
    if (!array_key_exists($field, $state)) {
      return null;
    }

    return (string) $state[$field];
  }

  /**
   * @param array<string, string> $fields
   */
  private static function writeFields(array $fields, string $adminUuid): void
  {
    $payload = array_merge($fields, [
      self::FIELD_UPDATED_AT => (string) time(),
      self::FIELD_UPDATED_BY => trim($adminUuid),
    ]);

    Database::hset(self::STATE_KEY, $payload);

    if (self::$cache === null) {
      self::$cache = [];
    }
    foreach ($payload as $cacheField => $cacheValue) {
      self::$cache[(string) $cacheField] = (string) $cacheValue;
    }
  }

  /**
   * @return array<string, string>
   */
  private static function loadState(): array
  {
    if (self::$cache !== null) {
      return self::$cache;
    }

    $raw = Database::hgetall(self::STATE_KEY);
    if ($raw === []) {
      self::$cache = [];

      return self::$cache;
    }

    $normalized = [];
    foreach ($raw as $field => $value) {
      $normalized[$field] = $value;
    }

    self::$cache = $normalized;

    return self::$cache;
  }
}
