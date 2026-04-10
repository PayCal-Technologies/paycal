<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Attributes\ExtensionCapability;

/**
 * AdminSurface.php
 *
 * Purpose: Admin-surface capability gate that centralizes admin availability,
 * visibility, and access checks for extension-driven admin features.
 *
 * Developer notes:
 * - Core should default to admin disabled unless extension capability says
 *   otherwise.
 * - Route/nav/page checks here intentionally enforce least exposure for admin
 *   endpoints and links.
 */
/**
 * Admin surface policy adapter.
 *
 * Responsibilities:
 * - Resolve whether admin functionality is enabled at runtime.
 * - Guard admin route access and redirect unavailable requests.
 * - Normalize and validate admin nav/page capability payloads.
 */
final class AdminSurface
{
  private const CAPABILITY_ENABLED = 'admin.surface.enabled';
  private const CAPABILITY_NAV_LINKS = 'admin.nav.links';
  private const CAPABILITY_PAGE_PATHS = 'admin.page.paths';

  #[ExtensionCapability(self::CAPABILITY_ENABLED)]
  /**
   * Handles isEnabled operation.
   */
  public static function isEnabled(): bool
  {
    return ExtensionCapabilityBridge::enabled(self::CAPABILITY_ENABLED, false);
  }

  /**
   * Handles userCanAccess operation.
   */
  public static function userCanAccess(): bool
  {
    return self::isEnabled() && User::isAdmin();
  }

  /**
   * Handles redirectHomeIfUnavailable operation.
   */
  public static function redirectHomeIfUnavailable(): void
  {
    if (self::userCanAccess()) {
      return;
    }

    header('Location: ' . Environment::appURL('/'));
    exit;
  }

  /**
   * Handles redirectHomeIfPageUnavailable operation.
   */
  public static function redirectHomeIfPageUnavailable(string $requestPath): void
  {
    if (self::userCanAccess() && self::pagePathIsEnabled($requestPath)) {
      return;
    }

    header('Location: ' . Environment::appURL('/'));
    exit;
  }

  /**
   * @return array<int, array{href:string, label_key:string, icon:string, match_prefix:string}>
   */
  #[ExtensionCapability(self::CAPABILITY_NAV_LINKS)]
  /**
   * Handles navLinks operation.
   */
  public static function navLinks(): array
  {
    $value = ExtensionCapabilityBridge::value(self::CAPABILITY_NAV_LINKS, []);
    if (!is_array($value)) {
      return [];
    }

    $links = [];
    foreach ($value as $candidate) {
      if (!is_array($candidate)) {
        continue;
      }

      $hrefRaw = $candidate['href'] ?? '';
      $href = is_scalar($hrefRaw) ? trim((string) $hrefRaw) : '';
      if ($href === '' || $href[0] !== '/') {
        continue;
      }

      $labelKeyRaw = $candidate['label_key'] ?? '';
      $labelKey = is_scalar($labelKeyRaw) ? trim((string) $labelKeyRaw) : '';
      if ($labelKey === '') {
        continue;
      }

      $iconRaw = $candidate['icon'] ?? 'admin';
      $icon = is_scalar($iconRaw) ? trim((string) $iconRaw) : 'admin';
      if ($icon === '') {
        $icon = 'admin';
      }

      $matchPrefixRaw = $candidate['match_prefix'] ?? $href;
      $matchPrefix = is_scalar($matchPrefixRaw) ? trim((string) $matchPrefixRaw) : $href;
      if ($matchPrefix === '') {
        $matchPrefix = $href;
      }

      $links[] = [
        'href' => $href,
        'label_key' => $labelKey,
        'icon' => $icon,
        'match_prefix' => $matchPrefix,
      ];
    }

    return $links;
  }

  /**
   * @param array{href:string, label_key:string, icon:string, match_prefix:string} $item
   */
  public static function navItemIsActive(array $item, string $requestPath): bool
  {
    $normalizedRequest = self::normalizePath($requestPath);
    $normalizedMatch = self::normalizePath($item['match_prefix']);

    if ($normalizedMatch === '/') {
      return $normalizedRequest === '/';
    }

    return $normalizedRequest === $normalizedMatch || str_starts_with($normalizedRequest, $normalizedMatch . '/');
  }

  /**
   * Handles pagePathIsEnabled operation.
   */
  public static function pagePathIsEnabled(string $requestPath): bool
  {
    if (!self::isEnabled()) {
      return false;
    }

    $normalizedRequest = self::normalizePath($requestPath);
    if (!str_starts_with($normalizedRequest, '/admin')) {
      return false;
    }

    $pagePaths = self::pagePaths();
    if ($pagePaths !== []) {
      foreach ($pagePaths as $pagePath) {
        $item = [
          'href' => $pagePath,
          'label_key' => '',
          'icon' => 'admin',
          'match_prefix' => $pagePath,
        ];

        if (self::navItemIsActive($item, $normalizedRequest)) {
          return true;
        }
      }

      return false;
    }

    foreach (self::navLinks() as $item) {
      if (self::navItemIsActive($item, $normalizedRequest)) {
        return true;
      }
    }

    return false;
  }

  /** @return array<int, string> */
  #[ExtensionCapability(self::CAPABILITY_PAGE_PATHS)]
  /**
   * Handles pagePaths operation.
   */
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
      if (!str_starts_with($path, '/admin')) {
        continue;
      }

      $paths[$path] = $path;
    }

    return array_values($paths);
  }

  /**
   * Handles normalizePath operation.
   */
  private static function normalizePath(string $path): string
  {
    $normalized = rtrim(trim($path), '/');
    if ($normalized === '') {
      return '/';
    }

    return $normalized;
  }

}

