<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

/** @var User $user */
$user = User::current();
$csrfNonce = $user->generateFormNonce('settings');
$payFrequency = $user->pay_frequency ?? '';
if ('' === $payFrequency) {
  $payFrequency = ((int) $user->pay_period_length === 7) ? 'weekly' : 'biweekly';
}
$payAnchor = trim((string) ($user->pay_anchor ?? ''));
if ($payAnchor === '') {
  $payAnchor = 'Monday';
}
$graceDaysMin = (int) SystemLimits::get('editing_grace_days_min');
$graceDaysMax = (int) SystemLimits::get('editing_grace_days_max');
$currentGraceDays = (int) ($user->editing_grace_days ?? UserPreferenceDefaults::DEFAULT_EDITING_GRACE_DAYS);
$currentGraceDays = max($graceDaysMin, min($graceDaysMax, $currentGraceDays));

$normalizeSliderPreference = static function (mixed $raw, array $legacyMap): int {
  $value = is_scalar($raw) ? strtolower(trim((string) $raw)) : '';
  if ($value === '') {
    return 0;
  }

  if (isset($legacyMap[$value])) {
    return $legacyMap[$value];
  }

  if (preg_match('/^-?\d+$/', $value) === 1) {
    return max(-5, min(5, (int) $value));
  }

  return 0;
};

$textSliderValue = $normalizeSliderPreference($user->text ?? UserPreferenceDefaults::DEFAULT_TEXT, [
  'small' => -2,
  'medium' => 0,
  'large' => 2,
  'x-large' => 5,
]);

$spacingSliderValue = $normalizeSliderPreference($user->spacing ?? UserPreferenceDefaults::DEFAULT_SPACING, [
  'tight' => -5,
  'compact' => -5,
  'comfy' => 0,
  'spacious' => 5,
  'zen' => 5,
]);

$depthPreset = strtolower(trim((string) $user->depth));
if (!in_array($depthPreset, ['flat', 'low', 'standard', 'high'], true)) {
  $depthPreset = UserPreferenceDefaults::DEFAULT_DEPTH;
}

// Display px per slider step (11 unique values); center matches baseline. CSS still applies rem + clamp.
$formatSliderStepDisplayPx = static function (int $raw, float $baseRem, float $clampMinRem, float $rootFontSizePx = 16.0): string {
  $normalized = max(-5, min(5, $raw));
  $minPx = (int) round($clampMinRem * $rootFontSizePx);
  $baselinePx = (int) round($baseRem * $rootFontSizePx);
  $maxPx = (2 * $baselinePx) - $minPx;
  $stepIndex = $normalized + 5;
  $px = (int) round($minPx + ($stepIndex * ($maxPx - $minPx) / 10));

  return $px . 'px';
};

// Match --text-base / --text clamp in html/css/common/index.php
$textSliderLabel = $formatSliderStepDisplayPx($textSliderValue, 1.125, 0.75);
// Match --spacing-base / --spacing clamp in html/css/common/index.php
$spacingSliderLabel = $formatSliderStepDisplayPx($spacingSliderValue, 1.0, 0.60);

$settingsShowAdvancedDiagnostics = SettingsNav::canViewAdvancedDiagnostics();

$voiceVolumeStored = is_numeric((string) ($user->voice_volume ?? ''))
  ? (float) $user->voice_volume
  : (float) UserPreferenceDefaults::DEFAULT_VOICE_VOLUME;
$voiceVolumeStored = max(0.0, min(1.0, $voiceVolumeStored));
$voiceVolumePercent = (int) round($voiceVolumeStored * 100);

$accountTimezones = [
  'America/St_Johns',
  'America/Halifax',
  'America/Toronto',
  'America/New_York',
  'America/Chicago',
  'America/Winnipeg',
  'America/Edmonton',
  'America/Denver',
  'America/Vancouver',
  'America/Los_Angeles',
  'UTC',
];
$currentTimezone = trim((string) ($user->timezone ?? 'America/Edmonton'));
if ($currentTimezone !== '' && !in_array($currentTimezone, $accountTimezones, true)) {
  array_unshift($accountTimezones, $currentTimezone);
}

$accountCurrencyOptions = ['CAD', 'USD', 'EUR', 'GBP', 'MXN', 'AUD'];
$currentCurrency = strtoupper(trim((string) ($user->currency ?? 'CAD')));
if ($currentCurrency !== '' && !in_array($currentCurrency, $accountCurrencyOptions, true)) {
  array_unshift($accountCurrencyOptions, $currentCurrency);
}

$calendarWeekStart = trim((string) ($user->calendar_week_start ?? UserPreferenceDefaults::DEFAULT_CALENDAR_WEEK_START));
if ($calendarWeekStart !== '1') {
  $calendarWeekStart = '0';
}
$calendarDefaultView = strtolower(trim((string) ($user->calendar_default_view ?? UserPreferenceDefaults::DEFAULT_CALENDAR_DEFAULT_VIEW)));
if (!in_array($calendarDefaultView, ['month', 'week', 'pay_period'], true)) {
  $calendarDefaultView = UserPreferenceDefaults::DEFAULT_CALENDAR_DEFAULT_VIEW;
}

Lens::boot('settings');

if (InputSanitizer::getString('lens') === '1') {
  Lens::add('Settings Backend Snapshot', [
    'page' => $currentPage ?? 'PAGE_SETTINGS',
    'language' => (string) ($user->language ?? 'en'),
    'theme' => (string) ($user->theme ?? 'default'),
    'variant' => (string) ($user->variant ?? 'default'),
    'text' => (string) ($user->text ?? '0'),
    'spacing' => (string) ($user->spacing ?? '0'),
    'depth' => (string) $user->depth,
    'dyslexia_typography' => (string) ($user->dyslexia_typography ?? UserPreferenceDefaults::DEFAULT_DYSLEXIA_TYPOGRAPHY),
    'audio_feedback' => (string) ($user->audio_feedback ?? 'all'),
    'default_site_set' => !empty((string) ($user->default_site_id ?? '')),
  ]);
}
