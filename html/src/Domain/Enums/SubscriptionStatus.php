<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * SubscriptionStatus.php
 *
 * Purpose: Subscription payment state enum (active, past_due, canceled, expired)
 *          used by billing reconciliation and feature-gate checks.
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
