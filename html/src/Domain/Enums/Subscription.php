<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * Subscription.php
 *
 * Purpose: Define the Subscription tier enum for PayCal\Domain\Enums.
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

/**
 * Subscription tier enum.
 *
 * Represents the subscription level for a user.
 * - FREE: Personal organization only, no team collaboration
 * - PREMIUM: Unlimited shared organizations, up to 1000 members per org
 */
enum Subscription: string
{
  case FREE = 'free';
  case PREMIUM = 'premium';

  /**
   * Get the monthly price in cents for this subscription tier.
   * 
   * @return int Price in cents (e.g., 499 = $4.99)
   */
  public function priceInCents(): int
  {
    return match ($this) {
      self::FREE    => 0,
      self::PREMIUM => 499,  // $4.99/month
    };
  }

  /**
   * Get the annual price in cents for this subscription tier.
   * 
   * @return int Price in cents (e.g., 4799 = $47.99/year)
   */
  public function annualPriceInCents(): int
  {
    return match ($this) {
      self::FREE    => 0,
      self::PREMIUM => 4799,  // $47.99/year (~$4/month equivalent)
    };
  }

  /**
   * Maximum members allowed per organization for this tier.
   * 
   * @return int Maximum members
   */
  public function maxMembersPerOrg(): int
  {
    return match ($this) {
      self::FREE    => 1,      // Personal org only
      self::PREMIUM => 1000,
    };
  }

  /**
   * Can this tier create shared (non-personal) organizations?
   * 
   * @return bool
   */
  public function canCreateSharedOrgs(): bool
  {
    return $this === self::PREMIUM;
  }
}
