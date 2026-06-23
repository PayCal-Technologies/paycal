<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * Environment-aware expiry rules for Argus runtime capture toggles.
 */
final class ArgusExpiryPolicy
{
  /**
   * @return array<int, array{id: string, label: string, minutes: int}>
   */
  public static function durationOptions(): array
  {
    $env = TraceGatePolicy::activeEnvironment();
    $options = [
      ['id' => '5', 'label' => '5 min', 'minutes' => 5],
      ['id' => '15', 'label' => '15 min', 'minutes' => 15],
      ['id' => '60', 'label' => '1 hour', 'minutes' => 60],
    ];

    if (in_array($env, ['mac', 'dev'], true)) {
      $options[] = ['id' => '0', 'label' => 'Until disabled', 'minutes' => 0];
    }

    return $options;
  }

  /**
   * Return the maximum allowed capture duration for the current environment.
   */
  public static function maxDurationMinutes(bool $adminOverride = false): ?int
  {
    if ($adminOverride) {
      return null;
    }

    return match (TraceGatePolicy::activeEnvironment()) {
      'mac' => null,
      'dev' => 24 * 60,
      default => 60,
    };
  }

  /**
   * Return whether capture toggles must have an expiry.
   */
  public static function requiresExpiry(): bool
  {
    return TraceGatePolicy::activeEnvironment() === 'prod';
  }

  /**
   * Resolve a duration into an absolute expiry timestamp.
   */
  public static function resolveExpiresAt(int $durationMinutes, bool $adminOverride = false): ?int
  {
    if ($durationMinutes === 0) {
      if (TraceGatePolicy::activeEnvironment() === 'prod' && !$adminOverride) {
        return time() + 3600;
      }

      return null;
    }

    $max = self::maxDurationMinutes($adminOverride);
    if ($max !== null && $durationMinutes > $max) {
      $durationMinutes = $max;
    }

    return time() + ($durationMinutes * 60);
  }

  /**
   * Return whether an expiry timestamp has passed.
   */
  public static function isExpired(?int $expiresAt): bool
  {
    if ($expiresAt === null || $expiresAt <= 0) {
      return false;
    }

    return time() >= $expiresAt;
  }

  /**
   * Format the remaining capture time for compact display.
   */
  public static function formatRemaining(?int $expiresAt): string
  {
    if ($expiresAt === null || $expiresAt <= 0) {
      return '';
    }

    $remaining = $expiresAt - time();
    if ($remaining <= 0) {
      return 'expired';
    }

    if ($remaining < 3600) {
      return (int) ceil($remaining / 60) . 'm';
    }

    return (int) ceil($remaining / 3600) . 'h';
  }
}
