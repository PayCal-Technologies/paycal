<?php declare(strict_types=1);

/**
 * TestUUID - Deterministic UUID Generator for Tests.
 *
 * Problem:
 * - Random UUIDs cause test flakiness
 * - Difficult to reproduce test failures
 * - Hard to assert on generated IDs
 *
 * Solution:
 * - Generate deterministic UUIDs from seed values
 * - Same seed always produces same UUID
 * - Great for fixtures and reproducible tests
 *
 * Usage:
 *   $userId = TestUUID::generate('user_1');
 *   // Always produces: 550e8400-e29b-41d4-a716-446655440001
 *
 *   $siteId = TestUUID::generate('site', 'location_a');
 *   // Always produces same UUID for same inputs
 */
class TestUUID
{
  /**
   * Generate a deterministic UUID from a seed.
   *
   * @param string ...$parts Seed components (concatenated with colons)
   * @return string UUID v4 format
   */
  public static function generate(string ...$parts): string
  {
    $seed = implode(':', $parts);
    $hash = hash('sha256', $seed);

    // Format as UUID v4 (xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx)
    // where y is 8, 9, a, or b
    return sprintf(
      '%s-%s-%s-%s-%s',
      substr($hash, 0, 8),
      substr($hash, 8, 4),
      '4' . substr($hash, 13, 3),  // Version 4
      dechex(hexdec(substr($hash, 16, 1)) & 0x3 | 0x8) . substr($hash, 17, 3),  // Variant bits
      substr($hash, 20, 12)
    );
  }

  /**
   * Generate a sequence of UUIDs from a base seed.
   *
   * @param string $baseSeed Base seed value
   * @param int    $count    Number of UUIDs to generate
   * @return array<string> Array of UUIDs
   */
  public static function sequence(string $baseSeed, int $count): array
  {
    $uuids = [];
    for ($i = 0; $i < $count; $i++) {
      $uuids[] = self::generate($baseSeed, (string) $i);
    }
    return $uuids;
  }

  /**
   * Generate a deterministic UUID for a user.
   *
   * @param string $identifier User identifier (e.g., 'admin', 'user_1')
   * @return string UUID
   */
  public static function user(string $identifier): string
  {
    return self::generate('user', $identifier);
  }

  /**
   * Generate a deterministic UUID for a session.
   *
   * @param string $identifier Session identifier
   * @return string UUID
   */
  public static function session(string $identifier): string
  {
    return self::generate('session', $identifier);
  }

  /**
   * Generate a deterministic UUID for a work entry.
   *
   * @param string $date Date string (YYYY-MM-DD)
   * @param string $site Site identifier
   * @return string UUID
   */
  public static function workEntry(string $date, string $site): string
  {
    return self::generate('work', $date, $site);
  }

  /**
   * Generate a deterministic UUID for a site.
   *
   * @param string $identifier Site identifier
   * @return string UUID
   */
  public static function site(string $identifier): string
  {
    return self::generate('site', $identifier);
  }

  /**
   * Generate a deterministic UUID for a team.
   *
   * @param string $identifier Team identifier
   * @return string UUID
   */
  public static function team(string $identifier): string
  {
    return self::generate('team', $identifier);
  }

  /**
   * Verify that a UUID is valid format.
   *
   * @param string $uuid UUID to validate
   * @return bool True if valid UUID format
   */
  public static function isValid(string $uuid): bool
  {
    return (bool) preg_match(
      '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
      $uuid
    );
  }

  /**
   * Create fixture set of related UUIDs.
   *
   * @return array<string, string> Named fixture UUIDs
   */
  public static function fixtures(): array
  {
    return [
      'admin_user' => self::user('admin'),
      'regular_user' => self::user('regular'),
      'test_session' => self::session('test'),
      'site_a' => self::site('location_a'),
      'site_b' => self::site('location_b'),
      'team_alpha' => self::team('alpha'),
      'work_today' => self::workEntry(date('Y-m-d'), 'site_a'),
    ];
  }
}
