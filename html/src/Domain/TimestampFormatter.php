<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * TimestampFormatter.php
 *
 * Purpose: Provide unified timestamp formatting utilities.
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

class TimestampFormatter
{
  /**
   * Format ISO 8601 timestamp for display (e.g., 2026-03-31T02:32:15+00:00 → 2026-03-31 2:32:15)
   * Removes the leading zero from hour (T0x → space x) and removes timezone offset.
   */
  public static function formatAuditTimestamp(string $iso8601): string
  {
    $trimmed = trim($iso8601);
    if ($trimmed === '') {
      return '';
    }

    // Replace T0 with space (T02 → 2, T03 → 3, etc.)
    // And remove timezone offset (+00:00, -05:00, etc.)
    $formatted = preg_replace('/\+.*$/', '', str_replace('T0', ' ', $trimmed)) ?: $trimmed;
    return trim($formatted);
  }

  /**
   * Format timestamp for API response with raw value for JS processing
   * Returns array with both formatted display and raw value for client-side parsing
   * @return array<string, string>
   */
  public static function formatAuditTimestampWithRaw(string $iso8601): array
  {
    return [
      'display' => self::formatAuditTimestamp($iso8601),
      'raw' => $iso8601,
    ];
  }
}
