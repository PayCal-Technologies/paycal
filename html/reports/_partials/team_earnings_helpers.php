<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Format a float as a locale-neutral hours string (e.g. 123.50).
 */
function earnings_fmt_hours(float $h): string
{
  return Strings::formatLocalizedNumber($h, 2, 2);
}

/**
 * Format a float as CAD currency string (e.g. $1,234.56).
 */
function earnings_fmt_money(float $v): string
{
  return '$' . Strings::formatLocalizedNumber($v, 2, 2);
}

/**
 * Format a float as a localized percent string (e.g. 12.5%).
 */
function earnings_fmt_percent(float $v, int $fractionDigits = 1): string
{
  return Strings::formatLocalizedNumber($v, $fractionDigits, $fractionDigits) . '%';
}

/**
 * Build a localized SVG hover/title string.
 */
function earnings_chart_hover(string $key, string $fallback, array $params = []): string
{
  return htmlspecialchars(earnings_i18n_fmt($key, $fallback, $params), ENT_QUOTES, 'UTF-8');
}

/**
 * Resolve an earnings translation key with a safe fallback when key is missing.
 */
function earnings_i18n(string $key, string $fallback): string
{
  static $cache = [];
  if (isset($cache[$key])) {
    return $cache[$key];
  }

  $value = trim((string) Strings::i18n($key));
  if ($value === '' || $value === $key) {
    $value = $fallback;
  }

  $cache[$key] = $value;
  return $value;
}

/**
 * Resolve a translation string and interpolate {tokens}.
 */
function earnings_i18n_fmt(string $key, string $fallback, array $params = []): string
{
  $label = earnings_i18n($key, $fallback);
  foreach ($params as $paramKey => $paramValue) {
    $label = str_replace('{' . $paramKey . '}', (string) $paramValue, $label);
  }

  return $label;
}
