<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * Subscription.php
 *
 * Purpose: User subscription tiers — Public (free), Premium (reporting), Business (orgs).
 */
enum Subscription: string
{
  case FREE = 'free';
  case PREMIUM = 'premium';
  case BUSINESS = 'business';

  public function priceInCents(): int
  {
    return match ($this) {
      self::FREE     => 0,
      self::PREMIUM  => 499,
      self::BUSINESS => 2999,
    };
  }

  public function annualPriceInCents(): int
  {
    return match ($this) {
      self::FREE     => 0,
      self::PREMIUM  => 4799,
      self::BUSINESS => 28799,
    };
  }

  public function maxMembersPerOrg(): int
  {
    return match ($this) {
      self::FREE     => 1,
      self::PREMIUM  => 1,
      self::BUSINESS => 1000,
    };
  }

  public function canCreateSharedOrgs(): bool
  {
    return $this === self::BUSINESS;
  }

  public function includesPremiumReporting(): bool
  {
    return $this === self::PREMIUM || $this === self::BUSINESS;
  }

  public function includesBusinessFeatures(): bool
  {
    return $this === self::BUSINESS;
  }

  public function displayLabel(): string
  {
    return match ($this) {
      self::FREE     => 'PayCal Public',
      self::PREMIUM  => 'PayCal Premium',
      self::BUSINESS => 'PayCal Business',
    };
  }
}
