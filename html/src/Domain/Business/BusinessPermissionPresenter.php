<?php declare(strict_types=1);

namespace PayCal\Domain\Business;

use PayCal\Domain\Strings;

/**
 * User-facing labels for business connection permissions.
 */
final class BusinessPermissionPresenter
{
  /** @var array<string, string> */
  private const SCOPE_LABEL_KEYS = [
    'all' => 'BUSINESSES_SCOPE_ALL_ACCESS',
    'work.read' => 'BUSINESSES_SCOPE_WORK_READ',
    'work.write' => 'BUSINESSES_SCOPE_WORK_WRITE',
    'work.scope.self' => 'BUSINESSES_SCOPE_WORK_SCOPE_SELF',
    'work.scope.business' => 'BUSINESSES_SCOPE_WORK_SCOPE_BUSINESS',
    'sites.read' => 'BUSINESSES_SCOPE_SITES_READ',
    'sites.write' => 'BUSINESSES_SCOPE_SITES_WRITE',
    'payperiod.read' => 'BUSINESSES_SCOPE_PAYPERIOD_READ',
    'payperiod.write' => 'BUSINESSES_SCOPE_PAYPERIOD_WRITE',
    'wage.read' => 'BUSINESSES_SCOPE_WAGE_READ',
    'wage.write' => 'BUSINESSES_SCOPE_WAGE_WRITE',
    'business.read' => 'BUSINESSES_SCOPE_BUSINESS_SETTINGS_READ',
    'business.write' => 'BUSINESSES_SCOPE_BUSINESS_SETTINGS_WRITE',
    'business.settings.read' => 'BUSINESSES_SCOPE_BUSINESS_SETTINGS_READ',
    'business.settings.write' => 'BUSINESSES_SCOPE_BUSINESS_SETTINGS_WRITE',
    'access.manage' => 'BUSINESSES_SCOPE_ACCESS_MANAGE',
    'audit.read' => 'BUSINESSES_SCOPE_AUDIT_READ',
  ];

  /** @return array<string, string> */
  public static function scopeLabels(?string $lang = null): array
  {
    $labels = [];
    foreach (self::SCOPE_LABEL_KEYS as $scope => $key) {
      $labels[$scope] = self::i18n($key, $lang);
    }

    return $labels;
  }

  /**
   * Scope label.
   */
  public static function scopeLabel(string $scope, ?string $lang = null): string
  {
    $normalized = strtolower(trim($scope));
    if ($normalized === 'work.self.write') {
      $normalized = 'work.scope.self';
    }

    $key = self::SCOPE_LABEL_KEYS[$normalized] ?? 'BUSINESSES_SCOPE_UNKNOWN_CUSTOM';

    return self::i18n($key, $lang);
  }

  /** @param array<int, mixed>|string $scopes */
  public static function scopeListLabel(array|string $scopes, ?string $lang = null): string
  {
    $tokens = self::scopeTokens($scopes);
    if ($tokens === []) {
      return self::i18n('BUSINESSES_SCOPES_NONE_LISTED', $lang);
    }

    if (in_array('all', $tokens, true)) {
      return self::scopeLabel('all', $lang);
    }

    $labels = [];
    foreach ($tokens as $scope) {
      $label = self::scopeLabel($scope, $lang);
      $labels[$label] = $label;
    }

    return implode('; ', array_values($labels));
  }

  /**
   * @param array<int, mixed>|string $scopes
   * @return array<int, string>
   */
  private static function scopeTokens(array|string $scopes): array
  {
    $rawScopes = is_array($scopes)
      ? $scopes
      : preg_split('/\s*,\s*/', trim($scopes), -1, PREG_SPLIT_NO_EMPTY);

    if (!is_array($rawScopes)) {
      return [];
    }

    $seen = [];
    foreach ($rawScopes as $scopeRaw) {
      if (!is_scalar($scopeRaw)) {
        continue;
      }

      $scope = strtolower(trim((string) $scopeRaw));
      if ($scope === '') {
        continue;
      }

      if ($scope === 'all') {
        return ['all'];
      }

      if ($scope === 'work.self.write') {
        $seen['work.write'] = true;
        $seen['work.scope.self'] = true;
        continue;
      }

      $seen[$scope] = true;
    }

    $tokens = array_keys($seen);
    $order = array_flip(array_keys(self::SCOPE_LABEL_KEYS));
    usort($tokens, static function (string $left, string $right) use ($order): int {
      $leftRank = $order[$left] ?? PHP_INT_MAX;
      $rightRank = $order[$right] ?? PHP_INT_MAX;
      if ($leftRank === $rightRank) {
        return strcmp($left, $right);
      }

      return $leftRank <=> $rightRank;
    });

    return $tokens;
  }

  /**
   * I18n.
   */
  private static function i18n(string $key, ?string $lang): string
  {
    $value = Strings::i18n($key, $lang);

    return $value === $key ? Strings::i18n($key, 'en') : $value;
  }
}
