<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * UserSettings.php
 *
 * Purpose: User settings schema and Redis accessor: maps field names to typed
 *          properties with grouped validation rules and feature-area scoping.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Class UserSettings.
 *
 * Central schema for all user settings and preferences.
 * Responsibilities include:
 * - Mapping Redis fields to User object properties
 * - Defining validation rules for each setting
 * - Grouping settings by feature area (theme, calendar, notifications, etc.)
 * - Providing type-safe access to all user-configurable preferences
 */
final class UserSettings
{
  private string $userUUID;

  /** @var array<string, mixed> */
  private array $cache = [];

  /**
   * Get a UserSettings instance for a user UUID.
   */
  public static function getInstance(string $userUUID): self
  {
    $instance = new self();
    $instance->userUUID = $userUUID;

    return $instance;
  }

  /**
   * Get a user setting value by key.
   */
  public function get(string $key): mixed
  {
    // Try cache first
    if (array_key_exists($key, $this->cache)) {
      return $this->cache[$key];
    }
    // Fetch from Redis
    $redis = Database::getInstance();
    $value = $redis->hGet('user:settings:' . $this->userUUID, $key);
    $this->cache[$key] = $value;

    return $value;
  }

  /**
   * Set a user setting value by key.
   */
  public function set(string $key, mixed $value): void
  {
    $redis = Database::getInstance();
    $redisValue = is_scalar($value) ? (string) $value : '';
    $redis->hSet('user:settings:' . $this->userUUID, $key, $redisValue);
    $this->cache[$key] = $value;
  }

  /**
   * Allowed POST string fields for RequestGuard.
   *
   * @return array<string>
   */
  public static function allowedStrings(): array
  {
    return array_map(
      static fn (UserFields $s) => $s->value,
      UserFields::cases()
    );
  }
}
