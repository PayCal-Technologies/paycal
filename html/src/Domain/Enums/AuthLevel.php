<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * AuthLevel.php
 *
 * Purpose: Ranked authentication level enum used throughout request gating, route access,
 *          and permission checks. Higher numeric rank = higher privilege.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Enums
 * @package    PayCal\Domain\Enums
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

enum AuthLevel: string
{
  case PUBLIC     = 'public';
  case GUEST      = 'guest';
  case UNVERIFIED = 'unverified';
  case VERIFIED   = 'verified';
  case USER       = 'user';
  case MANAGER    = 'manager';
  case ADMIN      = 'admin';
  case SUPERADMIN = 'superadmin';

  /**
   * Numeric rank for hierarchical comparisons.
   * Higher number = higher privilege.
   * Admin intentionally far above others.
   */
  public function rank(): int
  {
    return match ($this) {
      self::PUBLIC     => 0,
      self::GUEST      => 10,
      self::UNVERIFIED => 20,
      self::VERIFIED   => 30,
      self::USER       => 40,
      self::MANAGER    => 60,
      self::ADMIN      => 1000,
      self::SUPERADMIN => 2000, // absolute ceiling
    };
  }

  /**
   * True if current level >= given level.
   */
  public function atLeast(self $level): bool
  {
    return $this->rank() >= $level->rank();
  }

  /**
   * True if current level strictly higher than given level.
   */
  public function higherThan(self $level): bool
  {
    return $this->rank() > $level->rank();
  }

  /**
   * True if current level strictly lower than given level.
   */
  public function lowerThan(self $level): bool
  {
    return $this->rank() < $level->rank();
  }
}
