<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * SubscriptionStatus.php
 *
 * Purpose: Define the SubscriptionStatus enum for PayCal\Domain\Enums.
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
 * SubscriptionStatus enum.
 *
 * Represents the current state of a user's subscription.
 * - ACTIVE: Subscription is current and paid
 * - PAST_DUE: Payment failed but within grace period
 * - CANCELED: User explicitly canceled
 * - EXPIRED: Subscription lapsed without renewal
 */
enum SubscriptionStatus: string
{
  case ACTIVE = 'active';
  case PAST_DUE = 'past_due';
  case CANCELED = 'canceled';
  case EXPIRED = 'expired';

  /**
   * Does this status allow Premium feature access?
   * 
   * @return bool
   */
  public function grantsAccess(): bool
  {
    return $this === self::ACTIVE;
  }
}
