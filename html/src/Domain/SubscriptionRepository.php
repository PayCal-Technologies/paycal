<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\Subscription;
use PayCal\Domain\Enums\SubscriptionStatus;

/**
 * SubscriptionRepository.php
 *
 * Purpose: Data layer for subscription management and state transitions.
 *
 * Developer notes:
 * - This repository is the source of truth for persisted subscription tier and
 *   status state used by premium gating across the app.
 * - Payment-provider identifiers are persistence details; keep provider logic
 *   separated from feature-gating consumers.
 * - Status transitions here affect organizations, billing UI, and premium-only
 *   capabilities, so write changes should be treated as behavior changes.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Subscription persistence gateway.
 *
 * Responsibilities:
 * - Read and normalize stored subscription state.
 * - Apply tier/status transitions from billing events and app flows.
 * - Expose simple helpers for premium entitlement checks.
 */
final class SubscriptionRepository
{
  /**
   * Get subscription data for a user.
   *
   * @param string $userUUID
    * @return array{tier: Subscription, status: SubscriptionStatus, id: ?string, customer_id: ?string, start_date: ?string, renewal_date: ?string, cancel_date: ?string}
   */
  public static function get(string $userUUID): array
  {
    $key = Keys::USER_SUBSCRIPTION . ':' . $userUUID;
    $data = Database::hgetall($key) ?: [];

    return [
      'tier'         => self::parseSubscription($data['tier'] ?? 'free'),
      'status'       => self::parseSubscriptionStatus($data['status'] ?? 'active'),
      'id'           => $data['subscription_id'] ?? null,
      'customer_id'  => $data['stripe_customer_id'] ?? null,
      'start_date'   => $data['subscription_start_date'] ?? null,
      'renewal_date' => $data['subscription_renewal_date'] ?? null,
      'cancel_date'  => $data['subscription_cancel_date'] ?? null,
    ];
  }

  /**
   * Upgrade user to Premium subscription (flat-rate, no expiry initially).
   *
   * @param string $userUUID
   * @param ?string $externalSubscriptionId Payment provider subscription ID
   * @param ?string $stripeCustomerId Payment provider customer ID
   * @return void
   */
  public static function upgradeToPremium(
    string $userUUID,
    ?string $externalSubscriptionId = null,
    ?string $stripeCustomerId = null,
    ?int $renewalAtUnixTime = null
  ): void
  {
    $now = date('c');
    $key = Keys::USER_SUBSCRIPTION . ':' . $userUUID;

    $renewalDate = $renewalAtUnixTime !== null && $renewalAtUnixTime > 0
      ? date('c', $renewalAtUnixTime)
      : '';

    $fields = [
      'tier'                     => Subscription::PREMIUM->value,
      'status'                   => SubscriptionStatus::ACTIVE->value,
      'subscription_id'          => $externalSubscriptionId ?? '',
      'subscription_start_date'  => $now,
      'subscription_renewal_date' => $renewalDate,
      'subscription_cancel_date' => '',
    ];

    if ($stripeCustomerId !== null) {
      $fields['stripe_customer_id'] = $stripeCustomerId;
    }

    Database::hset($key, $fields);

    // Also update User model in-memory cache
    $user = User::current();
    if ($user->user_uuid === $userUUID) {
      $user->subscription_tier = Subscription::PREMIUM;
      $user->subscription_status = SubscriptionStatus::ACTIVE;
      $user->subscription_id = $externalSubscriptionId;
      $user->subscription_start_date = $now;
      $user->subscription_renewal_date = $renewalDate;
    }
  }

  /**
   * Downgrade user to Free tier.
   *
   * @param string $userUUID
   * @return void
   */
  public static function downgradeToFree(string $userUUID): void
  {
    $now = date('c');
    $key = Keys::USER_SUBSCRIPTION . ':' . $userUUID;

    Database::hset($key, [
      'tier'                    => Subscription::FREE->value,
      'status'                  => SubscriptionStatus::CANCELED->value,
      'subscription_id'         => '',
      'stripe_customer_id'      => '',
      'subscription_start_date' => '',
      'subscription_renewal_date' => '',
      'subscription_cancel_date' => $now,
    ]);

    // Also update User model in-memory cache
    $user = User::current();
    if ($user->user_uuid === $userUUID) {
      $user->subscription_tier = Subscription::FREE;
      $user->subscription_status = SubscriptionStatus::CANCELED;
      $user->subscription_cancel_date = $now;
    }
  }

  /**
   * Mark subscription as past due (payment failed).
   *
   * @param string $userUUID
   * @return void
   */
  public static function markPastDue(string $userUUID): void
  {
    $key = Keys::USER_SUBSCRIPTION . ':' . $userUUID;
    Database::hset($key, [
      'status' => SubscriptionStatus::PAST_DUE->value,
    ]);
  }

  /**
   * Mark subscription as expired (no renewal after cancellation or lapsed payment).
   *
   * @param string $userUUID
   * @return void
   */
  public static function markExpired(string $userUUID): void
  {
    $key = Keys::USER_SUBSCRIPTION . ':' . $userUUID;
    Database::hset($key, [
      'status'                   => SubscriptionStatus::EXPIRED->value,
      'subscription_renewal_date' => '',
    ]);
  }

  /**
   * Mark subscription as pending cancellation (will downgrade at period end).
   * Keeps Premium tier active until cancel_at date is reached.
   *
   * @param string $userUUID
   * @param int|null $cancelAtUnixTime Unix timestamp when cancellation takes effect
   * @param ?string $subscriptionId Stripe subscription ID
   * @param ?string $customerId Stripe customer ID
   * @return void
   */
  public static function markPendingCancellation(string $userUUID, ?int $cancelAtUnixTime = null, ?string $subscriptionId = null, ?string $customerId = null): void
  {
    $key = Keys::USER_SUBSCRIPTION . ':' . $userUUID;
    $cancelDate = $cancelAtUnixTime !== null ? date('c', $cancelAtUnixTime) : '';

    $fields = [
      'status'                   => SubscriptionStatus::ACTIVE->value,
      'tier'                     => Subscription::PREMIUM->value,
      'subscription_cancel_date' => $cancelDate,
    ];

    if ($subscriptionId !== null && $subscriptionId !== '') {
      $fields['subscription_id'] = $subscriptionId;
    }

    if ($customerId !== null && $customerId !== '') {
      $fields['stripe_customer_id'] = $customerId;
    }

    Database::hset($key, $fields);
  }

  /**
   * Handles storeStripeCustomerId operation.
   */
  public static function storeStripeCustomerId(string $userUUID, string $stripeCustomerId): void
  {
    $normalized = trim($stripeCustomerId);
    if ($userUUID === '' || $normalized === '') {
      return;
    }

    $key = Keys::USER_SUBSCRIPTION . ':' . $userUUID;
    Database::hset($key, [
      'stripe_customer_id' => $normalized,
    ]);
  }

  /**
   * Check if user has an active Premium subscription.
   *
   * @param string $userUUID
   * @return bool
   */
  public static function isPremiumActive(string $userUUID): bool
  {
    $subscription = self::get($userUUID);
    return $subscription['tier'] === Subscription::PREMIUM
      && $subscription['status']->grantsAccess();
  }

  /**
   * Parse subscription tier string to enum.
   *
   * @param string $value
   * @return Subscription
   */
  private static function parseSubscription(string $value): Subscription
  {
    return match (strtolower(trim($value))) {
      'premium' => Subscription::PREMIUM,
      default   => Subscription::FREE,
    };
  }

  /**
   * Parse subscription status string to enum.
   *
   * @param string $value
   * @return SubscriptionStatus
   */
  private static function parseSubscriptionStatus(string $value): SubscriptionStatus
  {
    return match (strtolower(trim($value))) {
      'active'    => SubscriptionStatus::ACTIVE,
      'past_due'  => SubscriptionStatus::PAST_DUE,
      'canceled'  => SubscriptionStatus::CANCELED,
      'expired'   => SubscriptionStatus::EXPIRED,
      default     => SubscriptionStatus::ACTIVE,
    };
  }
}

