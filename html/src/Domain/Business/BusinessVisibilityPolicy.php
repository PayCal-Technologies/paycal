<?php declare(strict_types=1);

namespace PayCal\Domain\Business;

/**
 * Central gate for private vs public business visibility.
 */
final class BusinessVisibilityPolicy
{
  public const VISIBILITY_PRIVATE = 'private';
  public const VISIBILITY_LISTED = 'listed';
  public const VISIBILITY_HIDDEN = 'hidden';
  public const VISIBILITY_SUSPENDED = 'suspended';

  public const MODERATION_NOT_REQUIRED = 'not_required';
  public const MODERATION_PENDING = 'pending';
  public const MODERATION_APPROVED = 'approved';
  public const MODERATION_REJECTED = 'rejected';
  public const MODERATION_NEEDS_REVIEW = 'needs_review';

  /**
   * @param array<string, string> $business
   */
  public static function resolveListingSponsorUUID(array $business): string
  {
    $sponsor = trim((string) ($business['listing_sponsor_uuid'] ?? ''));
    if ($sponsor !== '') {
      return $sponsor;
    }

    return trim((string) ($business['owner_uuid'] ?? ''));
  }

  /**
   * @param array<string, string> $business
   */
  public static function resolveApprovedDisplayName(array $business): string
  {
    $approved = trim((string) ($business['approved_display_name'] ?? ''));
    if ($approved !== '') {
      return $approved;
    }

    $legacy = trim((string) ($business['display_name'] ?? ''));
    if ($legacy !== '') {
      return $legacy;
    }

    return trim((string) ($business['name'] ?? ''));
  }

  /**
   * @param array<string, string> $business
   */
  public static function isPubliclyDiscoverable(array $business): bool
  {
    $status = strtolower(trim((string) ($business['status'] ?? 'active')));
    if ($status !== 'active') {
      return false;
    }

    $paidStatus = strtolower(trim((string) ($business['paid_status'] ?? BusinessTrustPolicy::PAID_NONE)));
    if (!in_array($paidStatus, [BusinessTrustPolicy::PAID_ACTIVE, BusinessTrustPolicy::PAID_TRIALING], true)) {
      return false;
    }

    $visibility = strtolower(trim((string) ($business['visibility'] ?? self::VISIBILITY_PRIVATE)));
    if ($visibility !== self::VISIBILITY_LISTED) {
      return false;
    }

    if (trim((string) ($business['suspended_reason'] ?? '')) !== '') {
      return false;
    }

    if (trim((string) ($business['rename_abuse_lock'] ?? '')) === '1') {
      return false;
    }

    $approvedName = self::resolveApprovedDisplayName($business);
    if ($approvedName === '') {
      return false;
    }

    $moderation = strtolower(trim((string) ($business['moderation_status'] ?? self::MODERATION_PENDING)));
    $pendingName = trim((string) ($business['pending_display_name'] ?? ''));
    $moderationAllowsListing = $moderation === self::MODERATION_APPROVED
      || $moderation === self::MODERATION_NOT_REQUIRED
      || ($moderation === self::MODERATION_NEEDS_REVIEW && $pendingName !== '');

    if (!$moderationAllowsListing) {
      return false;
    }

    $trustLevel = strtolower(trim((string) ($business['trust_level'] ?? BusinessTrustPolicy::TRUST_NEW)));
    if (!self::trustLevelMeetsMinimum($trustLevel, BusinessTrustPolicy::TRUST_VERIFIED_EMAIL)) {
      return false;
    }

    $businessType = strtolower(trim((string) ($business['business_type'] ?? 'shared')));
    if ($businessType === 'personal') {
      return false;
    }

    return true;
  }

  /**
   * TODO: Document trustLevelMeetsMinimum.
   */
  public static function trustLevelMeetsMinimum(string $actual, string $minimum): bool
  {
    $order = [
      BusinessTrustPolicy::TRUST_NEW => 0,
      BusinessTrustPolicy::TRUST_VERIFIED_EMAIL => 1,
      BusinessTrustPolicy::TRUST_PAID => 2,
      BusinessTrustPolicy::TRUST_BUSINESS_VERIFIED => 3,
      BusinessTrustPolicy::TRUST_MANUAL_TRUSTED => 4,
    ];

    return ($order[$actual] ?? 0) >= ($order[$minimum] ?? 0);
  }

  /**
   * @param array<string, string> $fields
   * @return array<string, string>
   */
  public static function applyListingEnabled(array $fields): array
  {
    $fields['visibility'] = self::VISIBILITY_LISTED;
    $fields['moderation_status'] = self::MODERATION_APPROVED;
    $fields['last_reviewed_at'] = date('c');
    $fields['rename_abuse_lock'] = '';

    return $fields;
  }

  /**
   * @param array<string, string> $fields
   * @return array<string, string>
   */
  public static function applyListingHidden(array $fields, string $reason = ''): array
  {
    $fields['visibility'] = self::VISIBILITY_PRIVATE;
    if ($reason !== '') {
      $fields['moderation_reasons'] = $reason;
    }

    return $fields;
  }

  /**
   * @param array<string, string> $fields
   * @return array<string, string>
   */
  public static function applySuspended(array $fields, string $reason): array
  {
    $fields['visibility'] = self::VISIBILITY_SUSPENDED;
    $fields['suspended_reason'] = $reason;

    return $fields;
  }
}
