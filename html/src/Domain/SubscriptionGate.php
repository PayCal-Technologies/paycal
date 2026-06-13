<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * SubscriptionGate
 *
 * Convenience layer for checking subscription-based feature access.
 */
final class SubscriptionGate
{
  /**
   * TODO: Document canCreateSharedBusinesses.
   */
  public static function canCreateSharedBusinesses(string $userUUID): bool
  {
    if (User::isAdmin()) {
      return true;
    }

    if (User::isManager()) {
      return true;
    }

    return SubscriptionRepository::isBusinessActive($userUUID);
  }

  /**
   * Premium reporting features (Premium or Business tier).
   */
  public static function hasActivePremium(string $userUUID): bool
  {
    return SubscriptionRepository::isPremiumActive($userUUID);
  }

  /**
   * Shared business / org management features (Business tier only).
   */
  public static function hasActiveBusiness(string $userUUID): bool
  {
    return SubscriptionRepository::isBusinessActive($userUUID);
  }
}
