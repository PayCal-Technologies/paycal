<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Attributes\ExtensionCapability;
use PayCal\Domain\Config\Environment;

/**
 * Business-surface capability gate for extension-driven business workspace pages.
 */
final class BusinessSurface
{
  private const CAPABILITY_ENABLED = 'business.surface.enabled';
  private const CAPABILITY_PAGE_PATHS = 'business.page.paths';
  private const CAPABILITY_NAV_TABS = 'business.nav.tabs';

  #[ExtensionCapability(self::CAPABILITY_ENABLED)]
  public static function isEnabled(): bool
  {
    return ExtensionCapabilityBridge::enabled(self::CAPABILITY_ENABLED, false);
  }

  public static function redirectHomeIfPageUnavailable(string $requestPath): void
  {
    if (self::isEnabled() && self::pagePathIsEnabled($requestPath)) {
      return;
    }

    header('Location: ' . Environment::appURL('/'));
    exit;
  }

  /**
   * @return array<int, array{page: string, href: string, label_key: string, min_role?: string}>
   */
  #[ExtensionCapability(self::CAPABILITY_NAV_TABS)]
  public static function navTabs(): array
  {
    $value = ExtensionCapabilityBridge::value(self::CAPABILITY_NAV_TABS, []);
    if (!is_array($value)) {
      return [];
    }

    $tabs = [];
    foreach ($value as $candidate) {
      if (!is_array($candidate)) {
        continue;
      }

      $pageRaw = $candidate['page'] ?? '';
      $page = is_scalar($pageRaw) ? trim((string) $pageRaw) : '';
      $hrefRaw = $candidate['href'] ?? '';
      $href = is_scalar($hrefRaw) ? trim((string) $hrefRaw) : '';
      $labelKeyRaw = $candidate['label_key'] ?? '';
      $labelKey = is_scalar($labelKeyRaw) ? trim((string) $labelKeyRaw) : '';

      if ($page === '' || $href === '' || $labelKey === '' || $href[0] !== '/') {
        continue;
      }

      $tab = [
        'page' => $page,
        'href' => $href,
        'label_key' => $labelKey,
      ];

      $minRoleRaw = $candidate['min_role'] ?? null;
      if (is_scalar($minRoleRaw)) {
        $minRole = trim((string) $minRoleRaw);
        if ($minRole !== '') {
          $tab['min_role'] = $minRole;
        }
      }

      $tabs[] = $tab;
    }

    return $tabs;
  }

  /**
   * @return array{currentPage: string, tabs: array<int, array{page: string, href: string, label_key: string, min_role?: string}>}
   */
  public static function contextHeaderData(string $currentPage): array
  {
    return [
      'currentPage' => $currentPage,
      'tabs' => self::navTabs(),
    ];
  }

  public static function pageTitleKeyFor(string $currentPage): string
  {
    foreach (self::navTabs() as $tab) {
      if ($tab['page'] === $currentPage) {
        return $tab['label_key'];
      }
    }

    return match ($currentPage) {
      'PAGE_BUSINESS_DASHBOARD', 'PAGE_BUSINESSES' => 'BUSINESS_NAV_DASHBOARD',
      default => 'BUSINESSES',
    };
  }

  public static function pagePathIsEnabled(string $requestPath): bool
  {
    if (!self::isEnabled()) {
      return false;
    }

    $normalizedRequest = self::normalizePath($requestPath);
    if (!str_starts_with($normalizedRequest, '/business')) {
      return false;
    }

    $pagePaths = self::pagePaths();
    if ($pagePaths === []) {
      return false;
    }

    foreach ($pagePaths as $pagePath) {
      $item = [
        'href' => $pagePath,
        'label_key' => '',
        'icon' => 'business',
        'match_prefix' => $pagePath,
      ];

      if (self::pathMatches($item['match_prefix'], $normalizedRequest)) {
        return true;
      }
    }

    return false;
  }

  /** @return array<int, string> */
  #[ExtensionCapability(self::CAPABILITY_PAGE_PATHS)]
  public static function pagePaths(): array
  {
    $value = ExtensionCapabilityBridge::value(self::CAPABILITY_PAGE_PATHS, []);
    if (!is_array($value)) {
      return [];
    }

    $paths = [];
    foreach ($value as $candidate) {
      if (!is_scalar($candidate)) {
        continue;
      }

      $path = self::normalizePath((string) $candidate);
      if (!str_starts_with($path, '/business')) {
        continue;
      }

      $paths[$path] = $path;
    }

    return array_values($paths);
  }

  private static function pathMatches(string $matchPrefix, string $requestPath): bool
  {
    $normalizedMatch = self::normalizePath($matchPrefix);
    if ($normalizedMatch === '/') {
      return $requestPath === '/';
    }

    return $requestPath === $normalizedMatch || str_starts_with($requestPath, $normalizedMatch . '/');
  }

  private static function normalizePath(string $path): string
  {
    $pathOnly = parse_url(trim($path), PHP_URL_PATH);
    $normalized = rtrim(is_string($pathOnly) ? $pathOnly : trim($path), '/');
    if ($normalized === '') {
      return '/';
    }

    return $normalized;
  }
}
