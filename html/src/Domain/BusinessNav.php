<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Business navigation helpers: sub-nav tabs, context header data, premium gate.
 */
final class BusinessNav
{
  /**
   * @param array<string, mixed> $business
   */
  private static function stringField(array $business, string $key, string $default = ''): string
  {
    if (!isset($business[$key]) || !is_scalar($business[$key])) {
      return $default;
    }

    return trim((string) $business[$key]);
  }

  /**
   * @return array<int, array{page: string, href: string, label_key: string, min_role?: string}>
   */
  public static function subNavTabs(): array
  {
    return [
      ['page' => 'PAGE_BUSINESS_DASHBOARD', 'href' => '/business/', 'label_key' => 'BUSINESS_NAV_DASHBOARD'],
      ['page' => 'PAGE_BUSINESS_DETAILS', 'href' => '/business/details/', 'label_key' => 'BUSINESS_NAV_DETAILS'],
      ['page' => 'PAGE_BUSINESS_MEMBERS', 'href' => '/business/members/', 'label_key' => 'BUSINESS_NAV_MEMBERS'],
      ['page' => 'PAGE_BUSINESS_GROUPS', 'href' => '/business/groups/', 'label_key' => 'BUSINESS_NAV_GROUPS'],
      ['page' => 'PAGE_BUSINESS_SITES', 'href' => '/business/sites/', 'label_key' => 'BUSINESS_NAV_SITES'],
      ['page' => 'PAGE_BUSINESS_PAYROLL', 'href' => '/business/payroll/', 'label_key' => 'BUSINESS_NAV_PAYROLL'],
      ['page' => 'PAGE_BUSINESS_REPORTS', 'href' => '/business/reports/', 'label_key' => 'BUSINESS_NAV_REPORTS'],
      ['page' => 'PAGE_BUSINESS_AUDIT', 'href' => '/business/audit/', 'label_key' => 'BUSINESS_NAV_AUDIT', 'min_role' => 'coordinator'],
    ];
  }

  /**
   * @return array{currentPage: string, tabs: array<int, array{page: string, href: string, label_key: string}>}
   */
  public static function contextHeaderData(string $currentPage): array
  {
    return [
      'currentPage' => $currentPage,
      'tabs' => self::subNavTabs(),
    ];
  }

  /**
   * Page title key for.
   */
  public static function pageTitleKeyFor(string $currentPage): string
  {
    return match ($currentPage) {
      'PAGE_BUSINESS_DASHBOARD', 'PAGE_BUSINESSES' => 'BUSINESS_NAV_DASHBOARD',
      'PAGE_BUSINESS_DETAILS' => 'BUSINESS_NAV_DETAILS',
      'PAGE_BUSINESS_MEMBERS' => 'BUSINESS_NAV_MEMBERS',
      'PAGE_BUSINESS_GROUPS' => 'BUSINESS_NAV_GROUPS',
      'PAGE_BUSINESS_SITES' => 'BUSINESS_NAV_SITES',
      'PAGE_BUSINESS_PAYROLL' => 'BUSINESS_NAV_PAYROLL',
      'PAGE_BUSINESS_AUDIT' => 'BUSINESS_NAV_AUDIT',
      'PAGE_BUSINESS_REPORTS' => 'BUSINESS_NAV_REPORTS',
      default => 'BUSINESSES',
    };
  }

  /**
   * Mirrors JS resolveControlCenterBusiness(): owned shared business first, then any active shared membership.
   *
   * @param array<int, array<string, mixed>> $businesses
   *
   * @return array<string, mixed>|null
   */
  public static function resolvePrimaryWorkspaceBusiness(array $businesses, string $userUUID): ?array
  {
    $userUUID = trim($userUUID);
    if ($userUUID === '') {
      return null;
    }

    foreach ($businesses as $business) {
      $ownerUUID = self::stringField($business, 'owner_uuid');
      $type = strtolower(self::stringField($business, 'business_type', 'shared'));
      $status = strtolower(self::stringField($business, 'status', 'active'));

      if ($ownerUUID !== $userUUID || $type !== 'shared') {
        continue;
      }

      if (in_array($status, ['archived', 'deleted', 'disabled'], true)) {
        continue;
      }

      return $business;
    }

    foreach ($businesses as $business) {
      $type = strtolower(self::stringField($business, 'business_type', 'shared'));
      $connectionStatus = strtolower(self::stringField($business, 'connection_status', self::stringField($business, 'status', 'active')));

      if ($type === 'shared' && ($connectionStatus === 'active' || $connectionStatus === 'pending')) {
        return $business;
      }
    }

    return null;
  }

  /**
   * @return array<string, mixed>|null
   */
  public static function primaryWorkspaceBusinessForUser(string $userUUID): ?array
  {
    $userUUID = trim($userUUID);
    if ($userUUID === '') {
      return null;
    }

    $result = (new BusinessDiscoveryService())->listForUser($userUUID);
    /** @var array<int, array<string, mixed>> $businesses */
    $businesses = is_array($result['data']['businesses'] ?? null) ? $result['data']['businesses'] : [];

    return self::resolvePrimaryWorkspaceBusiness($businesses, $userUUID);
  }

  /**
   * Short business label for calendar site prefixes, e.g. "Acme Builders" -> "AB".
   */
  public static function abbreviationForName(string $name): string
  {
    $name = trim($name);
    if ($name === '') {
      return 'BIZ';
    }

    $words = preg_split('/\s+/', $name) ?: [];
    $letters = '';
    foreach ($words as $word) {
      $clean = preg_replace('/[^A-Za-z0-9]/', '', (string) $word) ?? '';
      if ($clean === '') {
        continue;
      }

      $letters .= strtoupper($clean[0]);
      if (strlen($letters) >= 3) {
        break;
      }
    }

    if ($letters === '') {
      $fallback = preg_replace('/[^A-Za-z0-9]/', '', $name) ?? '';
      $letters = strtoupper(substr($fallback, 0, 3));
    }

    if ($letters === '') {
      return 'BIZ';
    }

    return substr($letters, 0, 3);
  }

  /**
   * Role display label.
   */
  public static function roleDisplayLabel(string $role): string
  {
    $role = strtolower(trim($role));
    if ($role === '') {
      return '—';
    }

    $roleKey = 'BUSINESSES_ROLE_' . strtoupper($role);
    $translated = Strings::i18n($roleKey);
    if ($translated !== $roleKey) {
      return $translated;
    }

    return ucfirst($role);
  }

  /**
   * Redirect non-Business-tier visitors away from /business/* (admins retain access).
   */
  public static function requirePremiumAccess(): void
  {
    if (User::isAdmin()) {
      return;
    }

    $userUUID = User::currentUUID();
    $hasBusiness = $userUUID !== '' && SubscriptionGate::hasActiveBusiness($userUUID);
    if ($hasBusiness) {
      return;
    }

    header('Location: /pricing/', true, 302);
    exit;
  }

  /**
   * Mirrors JS canViewCoordinatorBusinessTabs(): owner or coordinator on the workspace business.
   *
   * @param array<string, mixed>|null $business
   */
  public static function userCanAccessCoordinatorTabs(?array $business): bool
  {
    if (!is_array($business)) {
      return false;
    }

    $userUUID = User::currentUUID();
    $ownerUUID = self::stringField($business, 'owner_uuid');
    if ($userUUID !== '' && $ownerUUID === $userUUID) {
      return true;
    }

    $role = strtolower(self::stringField($business, 'role'));

    return in_array($role, ['owner', 'coordinator'], true);
  }

  /**
   * Redirect non-coordinator members away from coordinator-only routes (e.g. Audit).
   */
  public static function requireCoordinatorAccess(): void
  {
    if (User::isAdmin()) {
      return;
    }

    $userUUID = User::currentUUID();
    $business = self::primaryWorkspaceBusinessForUser($userUUID);
    if (self::userCanAccessCoordinatorTabs($business)) {
      return;
    }

    header('Location: /business/members/', true, 302);
    exit;
  }

  /**
   * Pending membership access requests for the workspace business (coordinator view).
   */
  public static function workspacePendingAccessRequestCount(string $userUUID, string $businessId): int
  {
    $userUUID = trim($userUUID);
    $businessId = trim($businessId);
    if ($userUUID === '' || $businessId === '') {
      return 0;
    }

    $result = (new BusinessDiscoveryService())->listAccessRequests($userUUID, $businessId);
    if (!$result['success']) {
      return 0;
    }

    $requests = is_array($result['data']['requests'] ?? null) ? $result['data']['requests'] : [];
    $count = 0;
    foreach ($requests as $request) {
      if (!is_array($request)) {
        continue;
      }

      $statusRaw = $request['status'] ?? 'pending';
      $status = is_scalar($statusRaw) ? strtolower(trim((string) $statusRaw)) : 'pending';
      if ($status === 'pending') {
        $count++;
      }
    }

    return $count;
  }
}
