<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Enums\Subscription;

/**
 * SubscriptionGate.php
 *
 * Purpose: Feature gate checking for subscription-based access control.
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
 * SubscriptionGate
 *
 * Convenience layer for checking subscription-based feature access.
 * Handles both Admin (system role) and Premium (subscription-based) access.
 */
final class SubscriptionGate
{
  /**
   * Check if user can create shared organizations.
   * Admins have system-level access; Premium subscribers have tier-based access.
   *
   * @param string $userUUID
   * @return bool
   */
  public static function canCreateSharedOrganizations(string $userUUID): bool
  {
    // Admins always have access
    if (User::isAdmin()) {
      return true;
    }

    // Managers are trusted to operate within org permission frameworks
    if (User::isManager()) {
      return true;
    }

    // Check Premium subscription
    return SubscriptionRepository::isPremiumActive($userUUID);
  }

  /**
   * Check if user can invite members to organizations.
   * Currently tied to shared org access (Premium only).
   *
   * @param string $userUUID
   * @return bool
   */
  public static function canInviteMembers(string $userUUID): bool
  {
    // Admins can invite; Premium users can invite to their orgs
    return self::canCreateSharedOrganizations($userUUID);
  }

  /**
   * Get the maximum members allowed for user's subscription tier.
   *
   * @param string $userUUID
   * @return int
   */
  public static function getMaxMembersPerOrganization(string $userUUID): int
  {
    $subscription = SubscriptionRepository::get($userUUID);
    return $subscription['tier']->maxMembersPerOrg();
  }

  /**
   * Get subscription tier for user.
   *
   * @param string $userUUID
   * @return Subscription
   */
  public static function getTier(string $userUUID): Subscription
  {
    $subscription = SubscriptionRepository::get($userUUID);
    return $subscription['tier'];
  }

  /**
   * Check if user has an active subscription (Premium).
   *
   * @param string $userUUID
   * @return bool
   */
  public static function hasActivePremium(string $userUUID): bool
  {
    return SubscriptionRepository::isPremiumActive($userUUID);
  }

  /**
   * Get descriptive status for user's subscription.
   * Used for UI display and debugging.
   *
   * @param string $userUUID
   * @return string
   */
  public static function getStatusDescription(string $userUUID): string
  {
    $subscription = SubscriptionRepository::get($userUUID);

    if ($subscription['tier'] === Subscription::FREE) {
      return 'Free ◆ Personal organization only';
    }

    if ($subscription['status']->grantsAccess()) {
      $renewalDate = $subscription['renewal_date'] ?? 'Unknown';
      return "Premium ◆ Active (Renews: $renewalDate)";
    }

    return 'Premium ◆ Inactive';
  }
}
