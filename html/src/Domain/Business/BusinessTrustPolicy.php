<?php declare(strict_types=1);

namespace PayCal\Domain\Business;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\Subscription;
use PayCal\Domain\Enums\SubscriptionStatus;
use PayCal\Domain\SubscriptionRepository;
use PayCal\Domain\User;
use PayCal\Domain\UserRepository;

/**
 * Owner/business trust and paid-status resolution.
 */
final class BusinessTrustPolicy
{
  public const PAID_NONE = 'none';
  public const PAID_TRIALING = 'trialing';
  public const PAID_ACTIVE = 'active';
  public const PAID_PAST_DUE = 'past_due';
  public const PAID_CANCELED = 'canceled';
  public const PAID_DISPUTED = 'disputed';

  public const TRUST_NEW = 'new';
  public const TRUST_VERIFIED_EMAIL = 'verified_email';
  public const TRUST_PAID = 'paid';
  public const TRUST_BUSINESS_VERIFIED = 'business_verified';
  public const TRUST_MANUAL_TRUSTED = 'manual_trusted';

  /**
   * Public business visibility requires PayCal Business tier (not Premium alone).
   */
  public static function resolvePaidStatus(string $userUUID): string
  {
    $subscription = SubscriptionRepository::get($userUUID);
    $tier = $subscription['tier'];
    $status = $subscription['status'];

    if ($tier !== Subscription::BUSINESS) {
      return self::PAID_NONE;
    }

    return match ($status) {
      SubscriptionStatus::ACTIVE => self::PAID_ACTIVE,
      SubscriptionStatus::PAST_DUE => self::PAID_PAST_DUE,
      SubscriptionStatus::CANCELED => self::PAID_CANCELED,
      SubscriptionStatus::EXPIRED => self::PAID_CANCELED,
    };
  }

  public static function resolveTrustLevel(string $userUUID): string
  {
    $user = UserRepository::getByUUID($userUUID);
    if (!$user instanceof User) {
      return self::TRUST_NEW;
    }

    if ($user->auth_level->value === 'superadmin' || $user->auth_level->value === 'admin') {
      return self::TRUST_MANUAL_TRUSTED;
    }

    $manual = trim(Database::hget(Keys::USER . ':' . $userUUID, 'business_trust_level'));
    if ($manual === self::TRUST_MANUAL_TRUSTED) {
      return self::TRUST_MANUAL_TRUSTED;
    }

    if (SubscriptionRepository::isBusinessActive($userUUID)) {
      return self::TRUST_BUSINESS_VERIFIED;
    }

    if (SubscriptionRepository::isPremiumActive($userUUID)) {
      return self::TRUST_PAID;
    }

    if ($user->email_verified) {
      return self::TRUST_VERIFIED_EMAIL;
    }

    return self::TRUST_NEW;
  }

  /** @return array{paid_status: string, trust_level: string} */
  public static function ownerTrustSnapshot(string $ownerUUID): array
  {
    return [
      'paid_status' => self::resolvePaidStatus($ownerUUID),
      'trust_level' => self::resolveTrustLevel($ownerUUID),
    ];
  }

  /** @return array<string, string> */
  public static function defaultTrustFields(string $ownerUUID, string $businessType): array
  {
    return self::ownerTrustSnapshot($ownerUUID) + [
      'listing_sponsor_uuid' => $ownerUUID,
      'approved_display_name' => '',
      'pending_display_name' => '',
      'visibility' => BusinessVisibilityPolicy::VISIBILITY_PRIVATE,
      'moderation_status' => $businessType === 'personal'
        ? BusinessVisibilityPolicy::MODERATION_NOT_REQUIRED
        : BusinessVisibilityPolicy::MODERATION_PENDING,
      'moderation_score' => '0',
      'moderation_reasons' => '',
      'approved_by_uuid' => '',
      'approved_at' => '',
      'renamed_at' => '',
      'last_public_rename_submitted_at' => '',
      'rename_abuse_lock' => '',
      'suspended_reason' => '',
      'last_reviewed_at' => '',
      'created_by_uuid' => $ownerUUID,
    ];
  }

  /**
   * @param array<string, string> $business
   * @return array{paid_status: string, trust_level: string}
   */
  public static function listingSponsorTrustSnapshot(array $business): array
  {
    $sponsorUUID = BusinessVisibilityPolicy::resolveListingSponsorUUID($business);
    if ($sponsorUUID === '') {
      return [
        'paid_status' => self::PAID_NONE,
        'trust_level' => self::TRUST_NEW,
      ];
    }

    return self::ownerTrustSnapshot($sponsorUUID);
  }
}
