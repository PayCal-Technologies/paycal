<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Enums\PayFrequency;
use PayCal\Domain\Enums\Subscription;

/**
 * Normalizes low-risk signup personalization choices before account creation.
 */
final class SignupPersonalization
{
  private const DEFAULT_TIER = 'free';
  private const DEFAULT_THEME_MODE = 'system';
  private const DEFAULT_TEXT_SIZE = 'standard';
  private const DEFAULT_SPACING = 'comfortable';
  private const DEFAULT_INTENT = 'worker';
  private const MAX_DASHBOARD_NAME_LENGTH = 64;

  /** @var array<int, string> */
  private const ALLOWED_THEME_MODES = ['light', 'dark', 'system'];

  /** @var array<int, string> */
  private const ALLOWED_TEXT_SIZES = ['standard', 'larger'];

  /** @var array<int, string> */
  private const ALLOWED_SPACING = ['compact', 'comfortable'];

  /** @var array<int, string> */
  private const ALLOWED_INTENTS = ['worker', 'manager', 'business'];

  /** @var array<int, string> */
  private const SIGNUP_ACCENTS = ['blue', 'green', 'purple', 'amber', 'red', 'slate'];

  /**
   * @param array<string, mixed> $payload
   * @return array{valid: bool, errors: array<string, string>, preferences: array<string, string>}
   */
  public static function fromSignupPayload(array $payload): array
  {
    $raw = is_array($payload['personalization'] ?? null)
      ? $payload['personalization']
      : $payload;

    $errors = [];

    $tier = self::enumValue($raw, ['tier', 'selectedTier', 'selected_tier'], self::DEFAULT_TIER);
    if (!in_array($tier, array_map(static fn (Subscription $case): string => $case->value, Subscription::cases()), true)) {
      $errors['tier'] = 'Invalid signup tier.';
      $tier = self::DEFAULT_TIER;
    }

    $themeMode = self::enumValue($raw, ['themeMode', 'theme_mode', 'signup_theme_mode'], self::DEFAULT_THEME_MODE);
    if (!in_array($themeMode, self::ALLOWED_THEME_MODES, true)) {
      $errors['theme_mode'] = 'Invalid theme mode.';
      $themeMode = self::DEFAULT_THEME_MODE;
    }

    $variant = self::enumValue($raw, ['variant', 'resolvedVariant', 'resolved_variant'], '');
    if ($variant === '') {
      $variant = $themeMode === 'light' ? 'light' : 'dark';
    }
    if (!in_array($variant, ['light', 'dark'], true)) {
      $errors['variant'] = 'Invalid resolved theme variant.';
      $variant = 'dark';
    }

    $accent = self::enumValue($raw, ['accentPreset', 'accent_preset', 'accent_color'], UserPreferenceDefaults::DEFAULT_ACCENT_PRESET);
    if ($accent === 'paycal-blue') {
      $accent = 'blue';
    }
    $allowedAccents = array_values(array_intersect(self::SIGNUP_ACCENTS, array_keys(UserPreferenceDefaults::accentPresets())));
    if (!in_array($accent, $allowedAccents, true)) {
      $errors['accent_preset'] = 'Invalid accent preset.';
      $accent = UserPreferenceDefaults::DEFAULT_ACCENT_PRESET;
    }

    $textSize = self::enumValue($raw, ['textSize', 'text_size', 'text_sizing'], self::DEFAULT_TEXT_SIZE);
    if (!in_array($textSize, self::ALLOWED_TEXT_SIZES, true)) {
      $errors['text_size'] = 'Invalid text size.';
      $textSize = self::DEFAULT_TEXT_SIZE;
    }

    $spacing = self::enumValue($raw, ['spacing', 'spacingDensity', 'spacing_density'], self::DEFAULT_SPACING);
    if (!in_array($spacing, self::ALLOWED_SPACING, true)) {
      $errors['spacing'] = 'Invalid spacing preference.';
      $spacing = self::DEFAULT_SPACING;
    }

    $language = self::enumValue($raw, ['language'], Language::DEFAULT);
    if (!Language::isSupported($language)) {
      $errors['language'] = 'Invalid language.';
      $language = Language::DEFAULT;
    }

    $payFrequency = self::enumValue($raw, ['payFrequency', 'pay_frequency', 'payRhythm', 'pay_rhythm'], PayFrequency::BIWEEKLY->value);
    if (!in_array($payFrequency, array_map(static fn (PayFrequency $case): string => $case->value, PayFrequency::cases()), true)) {
      $errors['pay_frequency'] = 'Invalid pay rhythm.';
      $payFrequency = PayFrequency::BIWEEKLY->value;
    }

    $intent = self::enumValue($raw, ['signupIntent', 'signup_intent', 'firstUse', 'first_use'], self::DEFAULT_INTENT);
    if (!in_array($intent, self::ALLOWED_INTENTS, true)) {
      $errors['signup_intent'] = 'Invalid signup intent.';
      $intent = self::DEFAULT_INTENT;
    }

    $dashboardName = self::dashboardName($raw['dashboardName'] ?? $raw['dashboard_name'] ?? '');

    return [
      'valid' => $errors === [],
      'errors' => $errors,
      'preferences' => [
        'signup_selected_tier' => $tier,
        'signup_theme_mode' => $themeMode,
        'theme' => UserPreferenceDefaults::DEFAULT_THEME,
        'variant' => $variant,
        'accent_preset' => $accent,
        'text' => $textSize === 'larger' ? '2' : '0',
        'spacing' => $spacing === 'compact' ? '-5' : '0',
        'language' => $language,
        'pay_frequency' => $payFrequency,
        'pay_period_length' => self::payPeriodLength($payFrequency),
        'signup_intent' => $intent,
        'dashboard_name' => $dashboardName,
      ],
    ];
  }

  /**
   * @return array<string, string>
   */
  public static function defaults(): array
  {
    return self::fromSignupPayload([])['preferences'];
  }

  /**
   * @return array<string, string>
   */
  public static function fromStoredJson(string $json): array
  {
    if ($json === '') {
      return self::defaults();
    }

    try {
      $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
      return self::defaults();
    }

    if (!is_array($decoded)) {
      return self::defaults();
    }

    $normalized = self::normalizedPayloadFromStoredMap($decoded);
    $result = self::fromSignupPayload($normalized);

    return $result['preferences'];
  }

  /**
   * @param array<string, mixed> $preferences
   * @return array<string, string>
   */
  public static function settingsForPersistence(array $preferences, ?int $completedAt = null): array
  {
    $defaults = self::defaults();
    $settings = [];
    foreach ($defaults as $field => $default) {
      $value = $preferences[$field] ?? $default;
      $settings[$field] = is_scalar($value) ? (string) $value : $default;
    }
    $settings['onboarding_completed_at'] = gmdate('c', $completedAt ?? time());

    return $settings;
  }

  /**
   * @param array<string, mixed> $preferences
   */
  public static function selectedTier(array $preferences): Subscription
  {
    $rawTier = $preferences['signup_selected_tier'] ?? self::DEFAULT_TIER;
    $tier = is_scalar($rawTier) ? strtolower(trim((string) $rawTier)) : self::DEFAULT_TIER;

    return Subscription::tryFrom($tier) ?? Subscription::FREE;
  }

  /**
   * @param array<string, mixed> $raw
   * @param array<int, string> $keys
   */
  private static function enumValue(array $raw, array $keys, string $default): string
  {
    foreach ($keys as $key) {
      if (!array_key_exists($key, $raw)) {
        continue;
      }

      $value = $raw[$key];
      if (!is_scalar($value)) {
        return '';
      }

      return strtolower(trim((string) $value));
    }

    return $default;
  }

  /**
   * Normalize an optional signup dashboard/workspace label.
   */
  private static function dashboardName(mixed $value): string
  {
    if (!is_scalar($value)) {
      return '';
    }

    $name = trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');
    if ($name === '') {
      return '';
    }

    return mb_substr($name, 0, self::MAX_DASHBOARD_NAME_LENGTH);
  }

  /**
   * Map pay rhythm to the stored pay-period length.
   */
  private static function payPeriodLength(string $payFrequency): string
  {
    return match ($payFrequency) {
      PayFrequency::WEEKLY->value => '7',
      PayFrequency::SEMIMONTHLY->value => '15',
      PayFrequency::MONTHLY->value => '30',
      default => '14',
    };
  }

  /**
   * @param array<mixed, mixed> $stored
   * @return array<string, mixed>
   */
  private static function normalizedPayloadFromStoredMap(array $stored): array
  {
    $text = is_scalar($stored['text'] ?? null) ? (string) $stored['text'] : '0';
    $spacing = is_scalar($stored['spacing'] ?? null) ? (string) $stored['spacing'] : '0';

    return [
      'selected_tier' => $stored['signup_selected_tier'] ?? self::DEFAULT_TIER,
      'signup_theme_mode' => $stored['signup_theme_mode'] ?? self::DEFAULT_THEME_MODE,
      'variant' => $stored['variant'] ?? '',
      'accent_preset' => $stored['accent_preset'] ?? UserPreferenceDefaults::DEFAULT_ACCENT_PRESET,
      'text_size' => ((int) $text) > 0 ? 'larger' : 'standard',
      'spacing' => ((int) $spacing) < 0 ? 'compact' : 'comfortable',
      'language' => $stored['language'] ?? Language::DEFAULT,
      'pay_frequency' => $stored['pay_frequency'] ?? PayFrequency::BIWEEKLY->value,
      'signup_intent' => $stored['signup_intent'] ?? self::DEFAULT_INTENT,
      'dashboard_name' => $stored['dashboard_name'] ?? '',
    ];
  }
}
