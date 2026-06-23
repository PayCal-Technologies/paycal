<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Settings navigation: sub-page tabs and titles.
 */
final class SettingsNav
{
  public const PAGE_ACCOUNT = 'PAGE_SETTINGS_ACCOUNT';
  public const PAGE_SUBSCRIPTION = 'PAGE_SETTINGS_SUBSCRIPTION';
  public const PAGE_CALENDAR = 'PAGE_SETTINGS_CALENDAR';
  public const PAGE_APPEARANCE = 'PAGE_SETTINGS_APPEARANCE';
  public const PAGE_ACCESSIBILITY = 'PAGE_SETTINGS_ACCESSIBILITY';
  public const PAGE_SECURITY = 'PAGE_SETTINGS_SECURITY';
  public const PAGE_EARLY_ACCESS = 'PAGE_SETTINGS_EARLY_ACCESS';
  public const PAGE_DATA = 'PAGE_SETTINGS_DATA';
  public const PAGE_DIAGNOSTICS = 'PAGE_SETTINGS_DIAGNOSTICS';

  /**
   * @return array<int, array{page: string, slug: string, href: string, label_key: string, title_key: string, desc_key: string}>
   */
  public static function subNavTabs(): array
  {
    $tabs = [
      [
        'page' => self::PAGE_ACCESSIBILITY,
        'slug' => 'accessibility',
        'href' => '/settings/accessibility/',
        'label_key' => 'SETTINGS_NAV_ACCESSIBILITY',
        'title_key' => 'SETTINGS_PAGE_ACCESSIBILITY_TITLE',
        'desc_key' => 'SETTINGS_PAGE_ACCESSIBILITY_DESC',
      ],
      [
        'page' => self::PAGE_ACCOUNT,
        'slug' => 'account',
        'href' => '/settings/account/',
        'label_key' => 'SETTINGS_NAV_ACCOUNT',
        'title_key' => 'SETTINGS_PAGE_ACCOUNT_TITLE',
        'desc_key' => 'SETTINGS_PAGE_ACCOUNT_DESC',
      ],
      [
        'page' => self::PAGE_SUBSCRIPTION,
        'slug' => 'subscription',
        'href' => '/settings/subscription/',
        'label_key' => 'SETTINGS_NAV_SUBSCRIPTION',
        'title_key' => 'SETTINGS_PAGE_SUBSCRIPTION_TITLE',
        'desc_key' => 'SETTINGS_PAGE_SUBSCRIPTION_DESC',
      ],
      [
        'page' => self::PAGE_DATA,
        'slug' => 'data',
        'href' => '/settings/data/',
        'label_key' => 'SETTINGS_NAV_DATA',
        'title_key' => 'SETTINGS_PAGE_DATA_TITLE',
        'desc_key' => 'SETTINGS_PAGE_DATA_DESC',
      ],
      [
        'page' => self::PAGE_APPEARANCE,
        'slug' => 'appearance',
        'href' => '/settings/appearance/',
        'label_key' => 'SETTINGS_NAV_APPEARANCE',
        'title_key' => 'SETTINGS_PAGE_APPEARANCE_TITLE',
        'desc_key' => 'SETTINGS_PAGE_APPEARANCE_DESC',
      ],
      [
        'page' => self::PAGE_CALENDAR,
        'slug' => 'calendar',
        'href' => '/settings/calendar/',
        'label_key' => 'SETTINGS_NAV_CALENDAR',
        'title_key' => 'SETTINGS_PAGE_CALENDAR_TITLE',
        'desc_key' => 'SETTINGS_PAGE_CALENDAR_DESC',
      ],
      [
        'page' => self::PAGE_SECURITY,
        'slug' => 'security',
        'href' => '/settings/security/',
        'label_key' => 'SETTINGS_NAV_SECURITY',
        'title_key' => 'SETTINGS_PAGE_SECURITY_TITLE',
        'desc_key' => 'SETTINGS_PAGE_SECURITY_DESC',
      ],
    ];

    if (self::canViewEarlyAccess()) {
      $tabs[] = [
        'page' => self::PAGE_EARLY_ACCESS,
        'slug' => 'early-access',
        'href' => '/settings/early-access/',
        'label_key' => 'SETTINGS_NAV_EARLY_ACCESS',
        'title_key' => 'SETTINGS_PAGE_EARLY_ACCESS_TITLE',
        'desc_key' => 'SETTINGS_PAGE_EARLY_ACCESS_DESC',
      ];
    }

    $tabs[] = [
        'page' => self::PAGE_DIAGNOSTICS,
        'slug' => 'diagnostics',
        'href' => '/settings/diagnostics/',
        'label_key' => 'SETTINGS_NAV_DIAGNOSTICS',
        'title_key' => 'SETTINGS_PAGE_DIAGNOSTICS_TITLE',
        'desc_key' => 'SETTINGS_PAGE_DIAGNOSTICS_DESC',
      ];

    return $tabs;
  }

  /**
   * @return array{currentPage: string, tabs: array<int, array{page: string, slug: string, href: string, label_key: string, title_key: string, desc_key: string}>}
   */
  public static function contextHeaderData(string $currentPage): array
  {
    return [
      'currentPage' => $currentPage,
      'tabs' => self::subNavTabs(),
    ];
  }

  /**
   * @return array{page: string, slug: string, href: string, label_key: string, title_key: string, desc_key: string}|null
   */
  public static function tabForPage(string $currentPage): ?array
  {
    foreach (self::subNavTabs() as $tab) {
      if ($tab['page'] === $currentPage) {
        return $tab;
      }
    }

    return null;
  }

  /**
   * Default sub page href.
   */
  public static function defaultSubPageHref(): string
  {
    return '/settings/accessibility/';
  }

  /**
   * Can view advanced diagnostics.
   */
  public static function canViewAdvancedDiagnostics(): bool
  {
    return User::isAdmin() || User::isManager();
  }

  public static function canViewEarlyAccess(): bool
  {
    $user = User::current();
    return EarlyAccessImmediateUi::settingsState($user)['visible'];
  }
}
