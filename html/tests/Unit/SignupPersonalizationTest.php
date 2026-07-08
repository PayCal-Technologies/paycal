<?php declare(strict_types=1);

use PayCal\Domain\Enums\Subscription;
use PayCal\Domain\SignupPersonalization;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
/**
 * Covers signup personalization normalization behavior.
 */
final class SignupPersonalizationTest extends TestCase
{
  #[Test]
  /**
   * Valid signup personalization maps to persisted user settings.
   */
  public function validSignupPersonalizationMapsToPersistedSettings(): void
  {
    $result = SignupPersonalization::fromSignupPayload([
      'personalization' => [
        'tier' => 'business',
        'themeMode' => 'system',
        'resolvedVariant' => 'light',
        'accentPreset' => 'slate',
        'textSize' => 'larger',
        'spacing' => 'compact',
        'language' => 'fr',
        'payFrequency' => 'monthly',
        'signupIntent' => 'business',
        'dashboardName' => '  Team Payroll  ',
      ],
    ]);

    $this->assertTrue($result['valid']);
    $this->assertSame([], $result['errors']);
    $this->assertSame('business', $result['preferences']['signup_selected_tier']);
    $this->assertSame('system', $result['preferences']['signup_theme_mode']);
    $this->assertSame('light', $result['preferences']['variant']);
    $this->assertSame('slate', $result['preferences']['accent_preset']);
    $this->assertSame('2', $result['preferences']['text']);
    $this->assertSame('-5', $result['preferences']['spacing']);
    $this->assertSame('fr', $result['preferences']['language']);
    $this->assertSame('monthly', $result['preferences']['pay_frequency']);
    $this->assertSame('30', $result['preferences']['pay_period_length']);
    $this->assertSame('business', $result['preferences']['signup_intent']);
    $this->assertSame('Team Payroll', $result['preferences']['dashboard_name']);
    $this->assertSame(Subscription::BUSINESS, SignupPersonalization::selectedTier($result['preferences']));

    $settings = SignupPersonalization::settingsForPersistence($result['preferences'], 1_772_582_400);
    $this->assertSame('2026-03-04T00:00:00+00:00', $settings['onboarding_completed_at']);
    $this->assertSame($result['preferences']['signup_selected_tier'], $settings['signup_selected_tier']);
  }

  #[Test]
  /**
   * Invalid signup personalization reports every invalid preference.
   */
  public function invalidSignupPersonalizationReportsErrors(): void
  {
    $result = SignupPersonalization::fromSignupPayload([
      'personalization' => [
        'tier' => 'enterprise',
        'themeMode' => 'sepia',
        'resolvedVariant' => 'high-contrast',
        'accentPreset' => 'neon',
        'textSize' => 'huge',
        'spacing' => 'crowded',
        'language' => 'zz',
        'payFrequency' => 'quarterly',
        'signupIntent' => 'auditor',
      ],
    ]);

    $this->assertFalse($result['valid']);
    $this->assertSame([
      'tier',
      'theme_mode',
      'variant',
      'accent_preset',
      'text_size',
      'spacing',
      'language',
      'pay_frequency',
      'signup_intent',
    ], array_keys($result['errors']));
  }

  #[Test]
  /**
   * Stored challenge JSON round trips normalized preferences.
   */
  public function storedChallengeJsonRoundTripsNormalizedPreferences(): void
  {
    $stored = [
      'signup_selected_tier' => 'premium',
      'signup_theme_mode' => 'system',
      'variant' => 'light',
      'accent_preset' => 'amber',
      'text' => '2',
      'spacing' => '-5',
      'language' => 'de',
      'pay_frequency' => 'weekly',
      'signup_intent' => 'manager',
      'dashboard_name' => 'Work Calendar',
    ];

    $preferences = SignupPersonalization::fromStoredJson((string) json_encode($stored, JSON_THROW_ON_ERROR));

    $this->assertSame('premium', $preferences['signup_selected_tier']);
    $this->assertSame('system', $preferences['signup_theme_mode']);
    $this->assertSame('light', $preferences['variant']);
    $this->assertSame('amber', $preferences['accent_preset']);
    $this->assertSame('2', $preferences['text']);
    $this->assertSame('-5', $preferences['spacing']);
    $this->assertSame('de', $preferences['language']);
    $this->assertSame('weekly', $preferences['pay_frequency']);
    $this->assertSame('7', $preferences['pay_period_length']);
    $this->assertSame('manager', $preferences['signup_intent']);
    $this->assertSame('Work Calendar', $preferences['dashboard_name']);
    $this->assertSame(Subscription::PREMIUM, SignupPersonalization::selectedTier($preferences));
  }

  #[Test]
  /**
   * Dashboard names are normalized and length limited.
   */
  public function dashboardNamesAreNormalizedAndLengthLimited(): void
  {
    $result = SignupPersonalization::fromSignupPayload([
      'dashboardName' => str_repeat('Payroll ', 20),
    ]);

    $this->assertTrue($result['valid']);
    $this->assertSame(64, strlen($result['preferences']['dashboard_name']));
    $this->assertStringNotContainsString('  ', $result['preferences']['dashboard_name']);
  }
}
