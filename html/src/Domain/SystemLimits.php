<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\SystemConfig;

/**
 * SystemLimits.php
 *
 * Purpose: Read-only facade over configured system limit metadata and limit
 * retrieval used across validation, pagination, and operational guardrails.
 *
 * Developer notes:
 * - This class exposes hard limits and schema used in multiple flows, so limit
 *   changes can surface as behavioral changes across the app.
 * - Keep it as a thin read-only facade over SystemConfig rather than layering
 *   unrelated policy here.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * System-limit facade.
 *
 * Responsibilities:
 * - Expose configured limit schema and lookup helpers.
 * - Provide stable, centralized access to application guardrail values.
 */
class SystemLimits
{
  public const MAX_USER_RESULTS = SystemConfig::MAX_USER_RESULTS;
  public const DEFAULT_PAGE_SIZE = SystemConfig::DEFAULT_PAGE_SIZE;

  /**
   * @return array<string, array<string, mixed>>
   */
  public static function getSchema(): array
  {
    return SystemConfig::getSchema();
  }

  /**
   * @return array<string, array<string, array<string, mixed>>>
   */
  public static function getByCategory(): array
  {
    return SystemConfig::getByCategory();
  }

  /**
   * @return array<string, string>
   */
  public static function getCategoryLabels(): array
  {
    return SystemConfig::getCategoryLabels();
  }

  /**
   * @return array<int, string>
   */
  public static function getKeys(): array
  {
    return SystemConfig::getKeys();
  }

  /**
   * Handles get operation.
   */
  public static function get(string $key): bool|float|int|string
  {
    return SystemConfig::get($key);
  }

  /**
   * @return array<string, bool|float|int|string>
   */
  public static function getAll(): array
  {
    return SystemConfig::getAll();
  }

  /**
   * @return array<string, mixed>
   */
  public static function validate(string $key, mixed $value): array
  {
    return SystemConfig::validate($key, $value);
  }

  /**
   * @return array<string, mixed>
   */
  public static function set(string $key, mixed $value): array
  {
    return SystemConfig::set($key, $value);
  }

  /**
   * Handles remove operation.
   */
  public static function remove(string $key): bool
  {
    return SystemConfig::remove($key);
  }

  /**
   * Handles resetAll operation.
   */
  public static function resetAll(): void
  {
    SystemConfig::resetAll();
  }

  /**
   * Handles clearCache operation.
   */
  public static function clearCache(): void
  {
    SystemConfig::clearCache();
  }
}

