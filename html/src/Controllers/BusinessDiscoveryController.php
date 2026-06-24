<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\ArrayPager;
use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\Business\BusinessPermissionPresenter;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\DataGrid;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\InputSanitizer;
use PayCal\Infrastructure\Audit\BusinessAuditControlTestService;
use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Domain\BusinessGroupService;
use PayCal\Domain\BusinessMemberReportExportService;
use PayCal\Domain\BusinessMemberReportsService;
use PayCal\Domain\BusinessMembersGridRenderer;
use PayCal\Domain\BusinessGroupsGridRenderer;
use PayCal\Domain\BusinessSitesGridRenderer;
use PayCal\Domain\BusinessWorkspaceWarmer;
use PayCal\Domain\ForecastProjectionService;
use PayCal\Domain\ForecastScenario;
use PayCal\Domain\Enums\SiteStatus;
use PayCal\Domain\RequestGuard;
use PayCal\Domain\Response;
use PayCal\Domain\Strings;
use PayCal\Domain\SubscriptionGate;
use PayCal\Domain\TimestampFormatter;
use PayCal\Domain\User;
use PayCal\Domain\UserConnectionService;
use PayCal\Domain\UserRepository;

/**
 * BusinessDiscoveryController.php
 *
 * Purpose: HTTP adapter for business discovery, invite workflows, access
 * requests, membership management, settings, and audit-grid endpoints.
 *
 * Developer notes:
 * - This controller should stay thin and delegate policy decisions to
 *   BusinessDiscoveryService.
 * - Keep request parsing and response shaping here; keep role/scope rules in
 *   the domain layer so controller endpoints remain consistent.
 * - Grid endpoints are contract-sensitive because the businesses UI depends
 *   on stable column keys, sorting, and paging metadata.
 * - Permission failures should continue to map through the shared status helper
 *   so endpoint behavior stays uniform.
 *
 * Architectural role:
 * - Entry-point controller for request handling, authorization enforcement,
 *   and response or render shaping at the web boundary.
 * - Domain policy, persistence rules, and side-effect orchestration should
 *   stay in collaborators rather than expanding controller state.
 *
 * @category   Controllers
 * @package    PayCal\Controllers
 * @subpackage HTTP
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * Business discovery API surface.
 *
 * Responsibilities:
 * - Translate request input into service calls for org-related workflows.
 * - Convert service outcomes into HTTP status codes and response payloads.
 * - Serve both action endpoints and datagrid-oriented listing endpoints.
 */
final class BusinessDiscoveryController
{
  private const CSRF_FORM_TYPE = 'businesses';

  /**
   * Validate and filter browser-authenticated business mutations.
   *
   * @param array<int,string> $allowedStrings
   * @param array<int,string> $allowedArrays
   * @param array<int,string> $droppedKeys
   * @param array<int,string> $base64ImageStrings
   * @param array<int,string> $rawStrings
   *
   * @return array<string, null|array<mixed>|bool|float|int|string>|false
   */
  private static function filterBusinessPost(
    array $allowedStrings = [],
    array $allowedArrays = [],
    array &$droppedKeys = [],
    array $base64ImageStrings = [],
    array $rawStrings = []
  ): array|false
  {
    return RequestGuard::filterPost(
      $allowedStrings,
      $allowedArrays,
      $droppedKeys,
      $base64ImageStrings,
      $rawStrings,
      self::CSRF_FORM_TYPE,
    );
  }

  /**
   * Validate browser-authenticated business mutations that carry all identity in
   * the route path and only need a business CSRF token in the POST body/header.
   */
  private static function requireBusinessCsrfPost(): bool
  {
    self::promoteBusinessCsrfHeader();
    $droppedKeys = [];

    return self::filterBusinessPost(['csrf_token'], [], $droppedKeys) !== false;
  }

  private static function promoteBusinessCsrfHeader(): void
  {
    if (isset($_POST['csrf_token']) && is_scalar($_POST['csrf_token']) && trim((string) $_POST['csrf_token']) !== '') {
      return;
    }

    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (is_scalar($header) && trim((string) $header) !== '') {
      $_POST['csrf_token'] = (string) $header;
    }
  }

  /**
   * Map domain/service failures to the appropriate HTTP status.
    *
    * @param array{success?: bool, message?: string, data?: array<string, mixed>} $result
   */
  private static function serviceFailureHttpStatus(array $result): int
  {
    $message = strtolower(trim((string) ($result['message'] ?? '')));
    if ($message === '') {
      return HttpStatus::HTTP_BAD_REQUEST;
    }

    if (
      str_contains($message, 'do not have permission')
      || str_contains($message, 'only authorized')
      || str_contains($message, 'only business')
      || str_contains($message, 'only the current owner')
      || str_contains($message, 'must be business owner')
      || str_contains($message, 'managers cannot')
      || str_contains($message, 'cannot modify')
      || str_contains($message, 'cannot assign roles above')
      || str_contains($message, 'cannot promote')
      || str_contains($message, 'transfer ownership before leaving')
      || str_contains($message, 'cannot be deleted or left')
      || str_contains($message, 'subscription required')
      || str_contains($message, 'premium subscription required')
    ) {
      return HttpStatus::HTTP_FORBIDDEN;
    }

    return HttpStatus::HTTP_BAD_REQUEST;
  }

  /**
   * Constructor. Aborts with 401 if the request is not authenticated.
   */
  public function __construct()
  {
    Authentication::abortIfUnauthenticated();
  }

  /**
   * Return whether the current user can use the business workspace.
   */
  private static function canUseBusinessWorkspace(): bool
  {
    if (User::isAdmin()) {
      return true;
    }

    $userUUID = User::currentUUID();

    return $userUUID !== '' && SubscriptionGate::hasActiveBusiness($userUUID);
  }

  /**
   * Require business workspace access for an operation.
   */
  private static function requireBusinessWorkspace(string $operation): bool
  {
    if (self::canUseBusinessWorkspace()) {
      return true;
    }

    Response::error('[Business] Business workspace access requires PayCal Business.', [
      'operation' => $operation,
    ], HttpStatus::HTTP_FORBIDDEN);

    return false;
  }

  /**
   * POST businesses/create
   *
   * Creates a new business owned by the current user.
   */
  #[Route('businesses/create', ['POST'])]
  /**
   * Handles create operation.
   */
  public function create(): void
  {
    $allowedStrings = ['name', 'business_type', 'organization_type'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $nameRaw = $filtered['name'] ?? '';
    $name = is_scalar($nameRaw) ? (string) $nameRaw : '';
    $businessTypeRaw = $filtered['business_type'] ?? $filtered['organization_type'] ?? 'shared';
    $businessType = is_scalar($businessTypeRaw) ? (string) $businessTypeRaw : 'shared';

    $service = new BusinessDiscoveryService();
    $result = $service->createBusiness(User::currentUUID(), $name, [
      'business_type' => $businessType,
    ]);

    if ($result['success']) {
      Response::success('[Business] Business created.', $result['data'], HttpStatus::HTTP_CREATED);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET businesses
   *
   * Returns a flat list of all businesses the current user belongs to.
   */
  #[Route('businesses', ['GET'])]
  /**
   * Handles listForCurrentUser operation.
   */
  public function listForCurrentUser(): void
  {
    $service = new BusinessDiscoveryService();
    $result = $service->listForUser(User::currentUUID());

    Response::success('[Business] Businesses retrieved.', $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * GET connections/people
   *
   * Lists person-to-person connections and explicit grants for the current user.
   */
  #[Route('connections/people', ['GET'])]
  public function listPersonConnections(): void
  {
    $service = new UserConnectionService();
    $result = $service->listForUser(User::currentUUID());

    if ($result['success']) {
      Response::success('Person connections retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST connections/people/request
   *
   * Sends a person connection request. No data access is granted by this action.
   */
  #[Route('connections/people/request', ['POST'])]
  public function requestPersonConnection(): void
  {
    $filtered = self::filterBusinessPost(['target_email'], []);
    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $emailRaw = $filtered['target_email'] ?? '';
    $targetEmail = is_scalar($emailRaw) ? (string) $emailRaw : '';
    $service = new UserConnectionService();
    $result = $service->requestPersonConnection(User::currentUUID(), $targetEmail);

    if ($result['success']) {
      Response::success($result['message'], $result['data'], HttpStatus::HTTP_CREATED);
    } else {
      Response::error($result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST connections/people/{connectionId}/approve
   *
   * Approves an incoming person connection request without granting data access.
   */
  #[Route('connections/people/{connectionId}/approve', ['POST'])]
  public function approvePersonConnection(string $connectionId): void
  {
    if (!self::requireBusinessCsrfPost()) {
      return;
    }

    $service = new UserConnectionService();
    $result = $service->approvePersonConnection(User::currentUUID(), InputSanitizer::sanitizeString($connectionId));

    if ($result['success']) {
      Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST connections/people/{connectionId}/revoke
   *
   * Revokes, cancels, or declines a person connection.
   */
  #[Route('connections/people/{connectionId}/revoke', ['POST'])]
  public function revokePersonConnection(string $connectionId): void
  {
    $filtered = self::filterBusinessPost(['status'], []);
    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $statusRaw = $filtered['status'] ?? UserConnectionService::STATUS_REVOKED;
    $status = is_scalar($statusRaw) ? (string) $statusRaw : UserConnectionService::STATUS_REVOKED;
    $service = new UserConnectionService();
    $result = $service->revokePersonConnection(
      User::currentUUID(),
      InputSanitizer::sanitizeString($connectionId),
      $status
    );

    if ($result['success']) {
      Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST connections/people/{connectionId}/grants
   *
   * Enables or disables one explicit permission for a person connection.
   */
  #[Route('connections/people/{connectionId}/grants', ['POST'])]
  public function updatePersonConnectionGrant(string $connectionId): void
  {
    $filtered = self::filterBusinessPost(['capability', 'enabled'], []);
    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $capabilityRaw = $filtered['capability'] ?? '';
    $enabledRaw = $filtered['enabled'] ?? '0';
    $capability = is_scalar($capabilityRaw) ? (string) $capabilityRaw : '';
    $enabled = in_array(strtolower(trim(is_scalar($enabledRaw) ? (string) $enabledRaw : '0')), ['1', 'true', 'yes', 'on'], true);

    $service = new UserConnectionService();
    $result = $enabled
      ? $service->grantCapability(User::currentUUID(), InputSanitizer::sanitizeString($connectionId), $capability)
      : $service->revokeCapability(User::currentUUID(), InputSanitizer::sanitizeString($connectionId), $capability);

    if ($result['success']) {
      Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/notifications/read
   *
   * Marks business notifications as read for the current user.
   */
  #[Route('businesses/{businessId}/notifications/read', ['POST'])]
  /**
   * Handles markNotificationsRead operation.
   */
  public function markNotificationsRead(string $businessId): void
  {
    if (!self::requireBusinessCsrfPost()) {
      return;
    }

    $service = new BusinessDiscoveryService();
    $businessId = InputSanitizer::sanitizeString($businessId);
    $result = $service->markBusinessNotificationsRead(User::currentUUID(), $businessId);

    if ($result['success']) {
      Response::success('[Business] Business notifications marked read.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET businesses/lists
   *
   * Returns a DataGrid-formatted list of businesses for the current user.
   */
  #[Route('businesses/lists', ['GET'])]
  /**
   * Handles listGrid operation.
   */
  public function listGrid(): void
  {
    if (!self::requireBusinessWorkspace('businesses.grid')) {
      return;
    }

    $service = new BusinessDiscoveryService();
    $result = $service->listForUser(User::currentUUID());

    if (!$result['success']) {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));

      return;
    }

    $businesses = is_array($result['data']['businesses'] ?? null)
      ? $result['data']['businesses']
      : [];

    $allBusinesses = $businesses;
    $businesses = array_values(array_filter($businesses, static function (mixed $business): bool {
      if (!is_array($business)) {
        return false;
      }

      $type = isset($business['business_type']) && is_scalar($business['business_type'])
        ? strtolower((string) $business['business_type'])
        : 'shared';

      return $type !== 'personal';
    }));

    if ($businesses === [] && $allBusinesses !== []) {
      $businesses = $allBusinesses;
    }

    $search = trim((string) (InputSanitizer::getString('search') ?? ''));
    $sort = (string) (InputSanitizer::getString('sort') ?? 'name');
    $direction = strtolower((string) (InputSanitizer::getString('direction') ?? 'asc')) === 'desc' ? 'desc' : 'asc';
    $page = max(1, (int) (InputSanitizer::getString('page') ?? '1'));

    if ($search !== '') {
      $needle = mb_strtolower($search);
      $businesses = array_values(array_filter($businesses, static function (mixed $business) use ($needle): bool {
        if (!is_array($business)) {
          return false;
        }

        $haystacks = [
          isset($business['name']) && is_scalar($business['name']) ? (string) $business['name'] : '',
          isset($business['business_type']) && is_scalar($business['business_type']) ? (string) $business['business_type'] : '',
          isset($business['role']) && is_scalar($business['role']) ? (string) $business['role'] : '',
          isset($business['status']) && is_scalar($business['status']) ? (string) $business['status'] : '',
        ];

        foreach ($haystacks as $haystack) {
          if (mb_stripos($haystack, $needle) !== false) {
            return true;
          }
        }

        return false;
      }));
    }

    $allowedSorts = ['name', 'business_type', 'role', 'status'];
    if (!in_array($sort, $allowedSorts, true)) {
      $sort = 'name';
    }

    usort($businesses, static function (mixed $a, mixed $b) use ($sort, $direction): int {
      if (!is_array($a) || !is_array($b)) {
        return 0;
      }

      $aValue = isset($a[$sort]) && is_scalar($a[$sort]) ? (string) $a[$sort] : '';
      $bValue = isset($b[$sort]) && is_scalar($b[$sort]) ? (string) $b[$sort] : '';
      $comparison = strcasecmp($aValue, $bValue);

      return $direction === 'desc' ? -$comparison : $comparison;
    });

    $rows = array_map(static function (mixed $business): array {
      if (!is_array($business)) {
        return [
          'id' => '',
          'name' => '',
          'business_type' => 'shared',
          'role' => '',
          'status' => '',
        ];
      }

      return [
        'id' => isset($business['business_id']) && is_scalar($business['business_id']) ? (string) $business['business_id'] : '',
        'name' => isset($business['name']) && is_scalar($business['name']) ? (string) $business['name'] : '',
        'business_type' => isset($business['business_type']) && is_scalar($business['business_type']) ? (string) $business['business_type'] : 'shared',
        'role' => isset($business['role']) && is_scalar($business['role']) ? (string) $business['role'] : '',
        'status' => isset($business['status']) && is_scalar($business['status']) ? (string) $business['status'] : '',
      ];
    }, $businesses);

    $grid = \PayCal\Domain\DataGrid::create('businesses', 'Businesses');
    $grid->enableSearch('Filter businesses…');
    $grid->enableSorting();
    $grid->addColumn('name', 'Name', true);
    $grid->addColumn('business_type', 'Type', true);
    $grid->addColumn('role', 'Role', true);
    $grid->addColumn('status', 'Status', true);
    $i18n = [];
    foreach (['REMOVE'] as $key) {
      $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
    }
    $removeLabel = $i18n['REMOVE'];
    $grid->addRowAction('remove', $removeLabel);
    $grid->setItemLabel('businesses');

    $pager = \PayCal\Domain\ArrayPager::fromArray($rows, [
      'pageSize' => 25,
    ]);
    $pager->setPage($page);
    $html = $grid->table($pager);

    $start = $pager->getTotal() === 0 ? 0 : (($pager->getPage() - 1) * $pager->getPageSize()) + 1;
    $end = min($pager->getPage() * $pager->getPageSize(), $pager->getTotal());
    $total = $pager->getTotal();
    $html = str_replace(
      '<div class="datagrid" data-grid="businesses" data-page="' . $pager->getPage() . '">',
      '<div class="datagrid" data-grid="businesses" data-page="' . $pager->getPage() . '" data-search="' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '" data-sort="' . htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') . '" data-direction="' . htmlspecialchars($direction, ENT_QUOTES, 'UTF-8') . '" data-pagination-start="' . $start . '" data-pagination-end="' . $end . '" data-pagination-total="' . $total . '">',
      $html
    );

    Response::success('[Business] Businesses grid rendered.', [
      'html' => $html,
    ], HttpStatus::HTTP_OK);
  }

  /**
   * GET businesses/{businessId}/members/grid
   *
   * Returns a DataGrid-formatted list of members for the specified business.
   * Requires that the current user is a member of the business.
   */
  #[Route('businesses/{businessId}/members/grid', ['GET'])]
  /**
   * Handles listMembersGrid operation.
   */
  public function listMembersGrid(string $businessId): void
  {
    $businessId = InputSanitizer::sanitizeString($businessId);
    $result = (new BusinessMembersGridRenderer())->renderForBusiness(
      User::currentUUID(),
      $businessId,
      [
        'search' => trim((string) (InputSanitizer::getString('search') ?? '')),
        'sort' => (string) (InputSanitizer::getString('sort') ?? 'full_name'),
        'direction' => (string) (InputSanitizer::getString('direction') ?? 'asc'),
        'page' => max(1, (int) (InputSanitizer::getString('page') ?? '1')),
        'role' => strtolower(trim((string) (InputSanitizer::getString('role') ?? ''))),
        // fresh=1 bypasses the materialized members cache (auditor/SOC verification path).
        'fresh' => trim((string) (InputSanitizer::getString('fresh') ?? '')) === '1',
      ],
    );

    if (!$result['success']) {
      $serviceResult = $result['service_result'] ?? [
        'message' => $result['message'],
        'data' => [],
      ];
      Response::error(
        '[Business] ' . $result['message'],
        $serviceResult['data'],
        self::serviceFailureHttpStatus($serviceResult),
      );

      return;
    }

    Response::success($result['message'], [
      'html' => $result['html'],
    ], HttpStatus::HTTP_OK);
  }

  /**
   * GET businesses/{businessId}/members/{memberUUID}/reports/breakdown/year/{year}
   *
   * Returns JSON member earnings breakdown for the reports member dialog.
   */
  #[Route('businesses/{businessId}/members/{memberUUID}/reports/breakdown/year/{year}', ['GET'])]
  public function getMemberReportsBreakdownJson(string $businessId, string $memberUUID, string $year): void
  {
    $businessId = InputSanitizer::sanitizeString($businessId);
    $memberUUID = InputSanitizer::sanitizeString($memberUUID);
    $yearInt = max(2000, min(2100, (int) $year));

    $result = (new BusinessMemberReportsService())->getMemberBreakdownJson(
      User::currentUUID(),
      $businessId,
      $memberUUID,
      $yearInt,
    );

    if (!$result['success']) {
      Response::error(
        '[Business] ' . $result['message'],
        $result['data'],
        HttpStatus::HTTP_FORBIDDEN,
      );

      return;
    }

    Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * POST businesses/{businessId}/members/apply-work-site
   *
   * Reassigns work entries for selected members to a business-linked site.
   */
  #[Route('businesses/{businessId}/members/apply-work-site', ['POST'])]
  public function applyMemberWorkSite(string $businessId): void
  {
    $allowedStrings = ['site_id', 'site_owner_uuid', 'apply_scope', 'select_all'];
    $allowedArrays = ['member_uuids'];
    $filtered = self::filterBusinessPost($allowedStrings, $allowedArrays);

    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $siteIdRaw = $filtered['site_id'] ?? '';
    $siteOwnerRaw = $filtered['site_owner_uuid'] ?? '';
    $siteId = is_scalar($siteIdRaw) ? InputSanitizer::sanitizeString((string) $siteIdRaw) : '';
    $siteOwner = is_scalar($siteOwnerRaw) ? InputSanitizer::sanitizeString((string) $siteOwnerRaw) : '';
    $applyScope = is_scalar($filtered['apply_scope'] ?? null)
      ? InputSanitizer::sanitizeString((string) $filtered['apply_scope'])
      : 'unlinked';
    $selectAll = is_scalar($filtered['select_all'] ?? null)
      && in_array(strtolower(trim((string) $filtered['select_all'])), ['1', 'true', 'yes'], true);

    $memberUUIDs = [];
    if ($selectAll) {
      $connectionPattern = Keys::BUSINESS_CONNECTION . ':' . InputSanitizer::sanitizeString($businessId) . ':*';
      foreach (Database::scanKeys($connectionPattern) as $connectionKey) {
        $parts = explode(':', (string) $connectionKey);
        // business:connection:{businessId}:{memberUUID}
        $memberUUID = (string) ($parts[3] ?? '');
        if ($memberUUID !== '') {
          $memberUUIDs[] = $memberUUID;
        }
      }
    } else {
      $memberUUIDsRaw = $filtered['member_uuids'] ?? [];
      if (is_array($memberUUIDsRaw)) {
        foreach ($memberUUIDsRaw as $memberUUID) {
          if (!is_scalar($memberUUID)) {
            continue;
          }

          $normalized = InputSanitizer::sanitizeString((string) $memberUUID);
          if ($normalized !== '') {
            $memberUUIDs[] = $normalized;
          }
        }
      }
    }

    $service = new BusinessDiscoveryService();
    $result = $service->applyWorkSiteToMembers(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      $memberUUIDs,
      $siteOwner,
      $siteId,
      $applyScope,
    );

    if ($result['success']) {
      Response::success('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * GET businesses/{businessId}/groups
   */
  #[Route('businesses/{businessId}/groups', ['GET'])]
  public function listBusinessGroups(string $businessId): void
  {
    $businessId = InputSanitizer::sanitizeString($businessId);
    $activeOnly = trim((string) (InputSanitizer::getString('active') ?? '')) === '1';
    $result = (new BusinessGroupService())->listGroups(User::currentUUID(), $businessId, $activeOnly);

    if (!$result['success']) {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * GET businesses/{businessId}/groups/grid
   */
  #[Route('businesses/{businessId}/groups/grid', ['GET'])]
  public function listBusinessGroupsGrid(string $businessId): void
  {
    $businessId = InputSanitizer::sanitizeString($businessId);
    $status = strtolower(trim((string) (InputSanitizer::getString('status') ?? 'active')));
    if (!in_array($status, ['active', 'archived'], true)) {
      $status = 'active';
    }

    $result = (new BusinessGroupsGridRenderer())->renderForBusiness(
      User::currentUUID(),
      $businessId,
      [
        'search' => trim((string) (InputSanitizer::getString('search') ?? '')),
        'sort' => (string) (InputSanitizer::getString('sort') ?? 'name'),
        'direction' => (string) (InputSanitizer::getString('direction') ?? 'asc'),
        'page' => max(1, (int) (InputSanitizer::getString('page') ?? '1')),
        'status' => $status,
      ],
    );

    if (!$result['success']) {
      $serviceResult = $result['service_result'] ?? [
        'message' => $result['message'],
        'data' => [],
      ];
      Response::error('[Business] ' . $result['message'], $serviceResult['data'], self::serviceFailureHttpStatus($serviceResult));
      return;
    }

    Response::success($result['message'], [
      'html' => $result['html'],
    ], HttpStatus::HTTP_OK);
  }

  /**
   * POST businesses/{businessId}/groups
   */
  #[Route('businesses/{businessId}/groups', ['POST'])]
  public function saveBusinessGroup(string $businessId): void
  {
    $filtered = self::filterBusinessPost(['group_id', 'name', 'description'], []);
    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $result = (new BusinessGroupService())->saveGroup(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $filtered);
    if (!$result['success']) {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * POST businesses/{businessId}/groups/{groupId}/members
   */
  #[Route('businesses/{businessId}/groups/{groupId}/members', ['POST'])]
  public function addBusinessGroupMembers(string $businessId, string $groupId): void
  {
    $filtered = self::filterBusinessPost([], ['member_uuids']);
    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $memberUUIDs = [];
    $raw = $filtered['member_uuids'] ?? [];
    if (is_array($raw)) {
      foreach ($raw as $memberUUIDRaw) {
        if (is_scalar($memberUUIDRaw)) {
          $memberUUID = InputSanitizer::sanitizeString((string) $memberUUIDRaw);
          if ($memberUUID !== '') {
            $memberUUIDs[] = $memberUUID;
          }
        }
      }
    }

    $result = (new BusinessGroupService())->addMembers(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      InputSanitizer::sanitizeString($groupId),
      $memberUUIDs,
    );
    if (!$result['success']) {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * POST businesses/{businessId}/groups/{groupId}/archive
   */
  #[Route('businesses/{businessId}/groups/{groupId}/archive', ['POST'])]
  public function archiveBusinessGroup(string $businessId, string $groupId): void
  {
    if (!self::requireBusinessCsrfPost()) {
      return;
    }

    $result = (new BusinessGroupService())->archiveGroup(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      InputSanitizer::sanitizeString($groupId),
    );
    if (!$result['success']) {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * POST businesses/{businessId}/groups/{groupId}/restore
   */
  #[Route('businesses/{businessId}/groups/{groupId}/restore', ['POST'])]
  public function restoreBusinessGroup(string $businessId, string $groupId): void
  {
    if (!self::requireBusinessCsrfPost()) {
      return;
    }

    $result = (new BusinessGroupService())->restoreGroup(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      InputSanitizer::sanitizeString($groupId),
    );
    if (!$result['success']) {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * POST businesses/{businessId}/groups/{groupId}/delete
   */
  #[Route('businesses/{businessId}/groups/{groupId}/delete', ['POST'])]
  public function deleteBusinessGroup(string $businessId, string $groupId): void
  {
    if (!self::requireBusinessCsrfPost()) {
      return;
    }

    $result = (new BusinessGroupService())->deleteGroup(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      InputSanitizer::sanitizeString($groupId),
    );
    if (!$result['success']) {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
      return;
    }

    Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * GET businesses/{businessId}/members/{memberUUID}/reports
   *
   * Returns earnings breakdown data for a single business member.
   * Requires access-management permission for the business.
   */
  #[Route('businesses/{businessId}/members/{memberUUID}/reports', ['GET'])]
  /**
   * Handles getMemberReports operation.
   */
  public function getMemberReports(string $businessId, string $memberUUID): void
  {
    $businessId = InputSanitizer::sanitizeString($businessId);
    $memberUUID = InputSanitizer::sanitizeString($memberUUID);
    $year = max(2000, min(2100, (int) (InputSanitizer::getString('year') ?? date('Y'))));

    $result = (new BusinessMemberReportsService())->getMemberBreakdown(
      User::currentUUID(),
      $businessId,
      $memberUUID,
      $year,
    );

    if (!$result['success']) {
      Response::error(
        '[Business] ' . $result['message'],
        $result['data'],
        HttpStatus::HTTP_FORBIDDEN,
      );

      return;
    }

    Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * GET businesses/{businessId}/members/{memberUUID}/reports/ytd/year/{year}
   */
  #[Route('businesses/{businessId}/members/{memberUUID}/reports/ytd/year/{year}', ['GET'])]
  public function getMemberReportsYtdSection(string $businessId, string $memberUUID, string $year): void
  {
    $this->respondMemberReportsSection($businessId, $memberUUID, $year, 'ytd');
  }

  /**
   * GET businesses/{businessId}/members/{memberUUID}/reports/payperiods/year/{year}
   */
  #[Route('businesses/{businessId}/members/{memberUUID}/reports/payperiods/year/{year}', ['GET'])]
  public function getMemberReportsPayPeriodsSection(string $businessId, string $memberUUID, string $year): void
  {
    $this->respondMemberReportsSection($businessId, $memberUUID, $year, 'payperiods');
  }

  /**
   * GET businesses/{businessId}/members/{memberUUID}/reports/monthly/year/{year}
   */
  #[Route('businesses/{businessId}/members/{memberUUID}/reports/monthly/year/{year}', ['GET'])]
  public function getMemberReportsMonthlySection(string $businessId, string $memberUUID, string $year): void
  {
    $this->respondMemberReportsSection($businessId, $memberUUID, $year, 'monthly');
  }

  /**
   * GET businesses/{businessId}/members/{memberUUID}/reports/gross/year/{year}
   */
  #[Route('businesses/{businessId}/members/{memberUUID}/reports/gross/year/{year}', ['GET'])]
  public function getMemberReportsGrossYear(string $businessId, string $memberUUID, string $year): void
  {
    $businessId = InputSanitizer::sanitizeString($businessId);
    $memberUUID = InputSanitizer::sanitizeString($memberUUID);
    $yearInt = max(2000, min(2100, (int) $year));

    $result = (new BusinessMemberReportsService())->getMemberReportsGrossYear(
      User::currentUUID(),
      $businessId,
      $memberUUID,
      $yearInt,
    );

    if (!$result['success']) {
      Response::error(
        '[Business] ' . $result['message'],
        $result['data'],
        HttpStatus::HTTP_FORBIDDEN,
      );

      return;
    }

    Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * GET businesses/{businessId}/members/{memberUUID}/reports/daily/year/{year}
   */
  #[Route('businesses/{businessId}/members/{memberUUID}/reports/daily/year/{year}', ['GET'])]
  public function getMemberReportsDailyYear(string $businessId, string $memberUUID, string $year): void
  {
    $businessId = InputSanitizer::sanitizeString($businessId);
    $memberUUID = InputSanitizer::sanitizeString($memberUUID);
    $yearInt = max(2000, min(2100, (int) $year));

    $result = (new BusinessMemberReportsService())->getMemberReportsDailyYear(
      User::currentUUID(),
      $businessId,
      $memberUUID,
      $yearInt,
    );

    if (!$result['success']) {
      Response::error(
        '[Business] ' . $result['message'],
        $result['data'],
        HttpStatus::HTTP_FORBIDDEN,
      );

      return;
    }

    Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * POST businesses/{businessId}/members/reports/audit
   *
   * Records a business audit event after a selected-member report batch runs.
   */
  #[Route('businesses/{businessId}/members/reports/audit', ['POST'])]
  public function recordMemberReportsAudit(string $businessId): void
  {
    if (!self::requireBusinessWorkspace('member_reports.audit')) {
      return;
    }

    $businessId = InputSanitizer::sanitizeString($businessId);
    $allowedStrings = [
      'report_key',
      'report_scope',
      'year',
      'format',
      'delivery',
      'member_count',
      'succeeded',
      'failed',
      'duration_ms',
      'generated_at',
      'event_phase',
      'result',
      'reason',
      'generation_path',
      'trust_level',
    ];
    $filtered = self::filterBusinessPost($allowedStrings, ['member_uuids']);
    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $access = (new BusinessDiscoveryService())->listConnections(User::currentUUID(), $businessId);
    if (!$access['success']) {
      Response::error('[Business] Business access denied.', $access['data'], self::serviceFailureHttpStatus($access));
      return;
    }

    $scalar = static function (mixed $value): string {
      return is_scalar($value) ? trim((string) $value) : '';
    };
    $memberUuids = [];
    if (is_array($filtered['member_uuids'] ?? null)) {
      foreach ($filtered['member_uuids'] as $memberUuid) {
        if (is_scalar($memberUuid)) {
          $memberUuidClean = trim(InputSanitizer::sanitizeString((string) $memberUuid));
          if ($memberUuidClean !== '') {
            $memberUuids[] = $memberUuidClean;
          }
        }
      }
    }

    $eventPhase = strtolower($scalar($filtered['event_phase'] ?? 'requested'));
    if ($eventPhase !== 'requested' && $eventPhase !== 'request') {
      Response::error(
        '[Business] Export completion audit events must be emitted by the server export workflow.',
        ['allowed_event_phase' => 'requested'],
        HttpStatus::HTTP_FORBIDDEN
      );
      return;
    }
    $eventType = 'business.member.report.export.requested';

    (new BusinessDiscoveryService())->appendBusinessAuditEvent(
      $businessId,
      $eventType,
      User::currentUUID(),
      [
        'report_key' => $scalar($filtered['report_key'] ?? ''),
        'report_scope' => $scalar($filtered['report_scope'] ?? ''),
        'year' => $scalar($filtered['year'] ?? ''),
        'format' => $scalar($filtered['format'] ?? ''),
        'delivery' => $scalar($filtered['delivery'] ?? ''),
        'member_count' => $scalar($filtered['member_count'] ?? ''),
        'member_uuids' => $memberUuids,
        'result' => 'requested',
        'reason' => $scalar($filtered['reason'] ?? ''),
      ],
    );

    Response::success('[Business] Report batch audit recorded.', [], HttpStatus::HTTP_OK);
  }

  /**
   * POST businesses/{businessId}/members/{memberUUID}/reports/export/{format}
   *
   * Generates protected member XLSX/PDF exports from server-side gated work rows.
   */
  #[Route('businesses/{businessId}/members/{memberUUID}/reports/export/{format}', ['POST'])]
  public function exportMemberReport(string $businessId, string $memberUUID, string $format): void
  {
    Authentication::abortIfUnauthenticated();
    if (!self::requireBusinessCsrfPost()) {
      return;
    }

    $businessId = InputSanitizer::sanitizeString($businessId);
    $memberUUID = InputSanitizer::sanitizeString($memberUUID);
    $format = InputSanitizer::sanitizeString($format);

    $postData = [];
    $body = file_get_contents('php://input');
    if (is_string($body) && trim($body) !== '') {
      try {
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
      } catch (\JsonException) {
        Response::error('[Business] Invalid JSON payload.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      if (!is_array($decoded)) {
        Response::error('[Business] JSON payload must be an object.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }
      $postData = $decoded;
    }

    $scope = isset($postData['scope']) && is_scalar($postData['scope'])
      ? trim((string) $postData['scope'])
      : 'yearly';
    $year = isset($postData['year']) && is_numeric($postData['year'])
      ? (int) $postData['year']
      : (int) date('Y');

    $result = (new BusinessMemberReportExportService())->exportMemberReport(
      User::currentUUID(),
      $businessId,
      $memberUUID,
      $scope,
      $format,
      $year,
    );

    if (!$result['success']) {
      $reason = $result['reason'];
      $status = match ($reason) {
        'invalid_scope', 'invalid_format', 'missing_context' => HttpStatus::HTTP_BAD_REQUEST,
        'no_export_rows' => HttpStatus::HTTP_UNPROCESSABLE,
        default => HttpStatus::HTTP_FORBIDDEN,
      };

      Response::error('[Business] ' . $result['message'], $result['data'], $status);
      return;
    }

    $bytes = isset($result['data']['bytes']) && is_string($result['data']['bytes'])
      ? $result['data']['bytes']
      : '';
    $mime = isset($result['data']['mime']) && is_scalar($result['data']['mime'])
      ? (string) $result['data']['mime']
      : 'application/octet-stream';
    $filename = isset($result['data']['filename']) && is_scalar($result['data']['filename'])
      ? (string) $result['data']['filename']
      : 'paycal-member-report.' . strtolower($format);

    http_response_code(HttpStatus::HTTP_OK);
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('Cache-Control: no-store, max-age=0');
    echo $bytes;
  }

  /**
   * GET businesses/{businessId}/members/{memberUUID}/reports/forecast
   */
  #[Route('businesses/{businessId}/members/{memberUUID}/reports/forecast', ['GET'])]
  public function getMemberReportsForecast(string $businessId, string $memberUUID): void
  {
    $businessId = InputSanitizer::sanitizeString($businessId);
    $memberUUID = InputSanitizer::sanitizeString($memberUUID);

    $result = (new BusinessMemberReportsService())->getMemberReportsForecast(
      User::currentUUID(),
      $businessId,
      $memberUUID,
    );

    if (!$result['success']) {
      Response::error(
        '[Business] ' . $result['message'],
        $result['data'],
        HttpStatus::HTTP_FORBIDDEN,
      );

      return;
    }

    Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * POST businesses/{businessId}/members/{memberUUID}/reports/forecast/preview
   */
  #[Route('businesses/{businessId}/members/{memberUUID}/reports/forecast/preview', ['POST'])]
  public function postMemberReportsForecastPreview(string $businessId, string $memberUUID): void
  {
    if (!self::requireBusinessCsrfPost()) {
      return;
    }

    $businessId = InputSanitizer::sanitizeString($businessId);
    $memberUUID = InputSanitizer::sanitizeString($memberUUID);

    $context = (new BusinessMemberReportsService())->resolveMemberForecastAccess(
      User::currentUUID(),
      $businessId,
      $memberUUID,
    );

    if (!$context['success']) {
      Response::error(
        '[Business] ' . $context['message'],
        $context['data'],
        HttpStatus::HTTP_FORBIDDEN,
      );

      return;
    }

    $body = file_get_contents('php://input');
    $postData = [];
    if (is_string($body) && $body !== '') {
      try {
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (is_array($decoded)) {
          $postData = $decoded;
        }
      } catch (\JsonException) {
        Response::error('[Business] Invalid JSON payload.', [], HttpStatus::HTTP_BAD_REQUEST);

        return;
      }
    }

    $scenarioInput = $postData['scenario'] ?? 'normal';
    $scenarioRaw = strtolower(trim(is_scalar($scenarioInput) ? (string) $scenarioInput : 'normal'));
    $scenario = ForecastScenario::tryFrom($scenarioRaw) ?? ForecastScenario::Normal;
    $overrides = isset($postData['overrides']) && is_array($postData['overrides'])
      ? $postData['overrides']
      : [];

    /** @var User $memberUser */
    $memberUser = $context['data']['member_user'];
    $state = (new ForecastProjectionService())->preview($memberUser, $overrides, $scenario);
    Response::success('[Business] Member forecast preview calculated.', $state, HttpStatus::HTTP_OK);
  }

  /**
   * Handles lazy-loaded member reports HTML sections.
   */
  private function respondMemberReportsSection(string $businessId, string $memberUUID, string $year, string $section): void
  {
    $businessId = InputSanitizer::sanitizeString($businessId);
    $memberUUID = InputSanitizer::sanitizeString($memberUUID);
    $yearInt = max(2000, min(2100, (int) $year));

    $result = (new BusinessMemberReportsService())->getMemberReportsSectionHtml(
      User::currentUUID(),
      $businessId,
      $memberUUID,
      $section,
      $yearInt,
    );

    if (!$result['success']) {
      Response::error(
        '[Business] ' . $result['message'],
        $result['data'],
        HttpStatus::HTTP_FORBIDDEN,
      );

      return;
    }

    Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * POST businesses/{businessId}/invites/send
   *
   * Sends an invitation email to a target email address for the specified business.
   * Caller must be an owner or manager of the business.
   */
  #[Route('businesses/{businessId}/invites/send', ['POST'])]
  /**
   * Handles sendInvite operation.
   */
  public function sendInvite(string $businessId): void
  {
    $allowedStrings = ['email'];
    $allowedArrays = ['scopes'];
    $filtered = self::filterBusinessPost($allowedStrings, $allowedArrays);

    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $emailRaw = $filtered['email'] ?? '';
    $email = is_scalar($emailRaw) ? (string) $emailRaw : '';

    $scopesRaw = $filtered['scopes'] ?? [];
    $scopes = [];
    if (is_array($scopesRaw)) {
      foreach ($scopesRaw as $scope) {
        if (!is_scalar($scope)) {
          continue;
        }

        $scopes[] = (string) $scope;
      }
    }

    $service = new BusinessDiscoveryService();
    $result = $service->sendInvite(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $email, $scopes);

    if ($result['success']) {
      Response::success('[Business] Invite created.', $result['data'], HttpStatus::HTTP_CREATED);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/invites/import/prepare
   *
   * Validates a batch of email addresses and returns a prepared import set
   * with per-email results (valid, duplicate, already member).
   */
  #[Route('businesses/{businessId}/invites/import/prepare', ['POST'])]
  /**
   * Handles prepareInviteImport operation.
   */
  public function prepareInviteImport(string $businessId): void
  {
    $allowedStrings = ['emails'];
    $allowedArrays = ['scopes', 'emails_chunks'];
    $filtered = self::filterBusinessPost($allowedStrings, $allowedArrays);

    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $payloadValidation = $this->validateInviteImportPayload($filtered);
    if (!$payloadValidation['valid']) {
      Response::error('[Business] Malformed import payload.', [
        'malformed_fields' => $payloadValidation['malformed_fields'],
        'details' => $payloadValidation['details'],
      ], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $emailsSource = $payloadValidation['emails_source'];
    $emailsChunksRaw = $payloadValidation['emails_chunks'];
    $emails = $this->expandSanitizeTextareaEmails($emailsSource, $emailsChunksRaw);

    $scopesRaw = $filtered['scopes'] ?? [];
    $scopes = [];
    if (is_array($scopesRaw)) {
      foreach ($scopesRaw as $scope) {
        if (!is_scalar($scope)) {
          continue;
        }

        $scopes[] = (string) $scope;
      }
    }

    $service = new BusinessDiscoveryService();
    $result = $service->prepareBulkInviteImport(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $emails, $scopes);

    if ($result['success']) {
      Response::success('[Business] Invite import prepared.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * @param array<string, mixed> $filtered
   * @return array{valid: bool, malformed_fields: array<int, string>, details: array<string, string>, emails_source: string, emails_chunks: array<int, string>}
   */
  private function validateInviteImportPayload(array $filtered): array
  {
    $malformedFields = [];
    $details = [];

    $emailsRaw = $filtered['emails'] ?? '';
    $emailsSource = '';
    if ($emailsRaw !== '' && !is_scalar($emailsRaw)) {
      $malformedFields[] = 'emails';
      $details['emails'] = 'Expected a textarea string payload.';
    } else {
      $emailsSource = trim((string) $emailsRaw);
      if (strlen($emailsSource) > 20000) {
        $malformedFields[] = 'emails';
        $details['emails'] = 'Textarea payload exceeds maximum length.';
      }
    }

    $emailsChunks = [];
    $rawChunksProvided = array_key_exists('emails_chunks', $_POST);
    $rawChunks = $rawChunksProvided ? ($_POST['emails_chunks'] ?? null) : null;
    if ($rawChunksProvided && !is_array($rawChunks)) {
      $malformedFields[] = 'emails_chunks';
      $details['emails_chunks'] = 'Expected an array of email chunks.';
    } else {
      $chunksRaw = $filtered['emails_chunks'] ?? [];
      if (!is_array($chunksRaw)) {
        $malformedFields[] = 'emails_chunks';
        $details['emails_chunks'] = 'Expected an array of email chunks.';
        $chunksRaw = [];
      }

      foreach ($chunksRaw as $index => $chunk) {
        if (!is_scalar($chunk)) {
          $malformedFields[] = 'emails_chunks';
          $details['emails_chunks'] = 'Chunk #' . (string) $index . ' is not a string value.';
          break;
        }

        $value = trim((string) $chunk);
        if ($value === '') {
          continue;
        }

        if (strlen($value) > 320) {
          $malformedFields[] = 'emails_chunks';
          $details['emails_chunks'] = 'Chunk #' . (string) $index . ' exceeds max token length.';
          break;
        }

        $emailsChunks[] = $value;
      }
    }

    $scopesRaw = $filtered['scopes'] ?? [];
    if (!is_array($scopesRaw)) {
      $malformedFields[] = 'scopes';
      $details['scopes'] = 'Expected a list of access options.';
    }

    if ($emailsSource === '' && $emailsChunks === []) {
      $malformedFields[] = 'emails';
      $details['emails'] = 'Provide at least one email candidate.';
    }

    $malformedFields = array_values(array_unique($malformedFields));

    return [
      'valid' => $malformedFields === [],
      'malformed_fields' => $malformedFields,
      'details' => $details,
      'emails_source' => $emailsSource,
      'emails_chunks' => $emailsChunks,
    ];
  }

  /**
   * Expand textarea-origin payload into one email candidate per line.
   *
   * @param mixed $chunksRaw
   */
  private function expandSanitizeTextareaEmails(string $emailsSource, mixed $chunksRaw): string
  {
    $parts = [];

    if (is_array($chunksRaw)) {
      foreach ($chunksRaw as $chunk) {
        if (!is_scalar($chunk)) {
          continue;
        }

        $value = trim(InputSanitizer::sanitizeString((string) $chunk));
        if ($value !== '') {
          $parts[] = $value;
        }
      }
    }

    if ($parts === []) {
      $segments = preg_split('/[\s,;]+/', $emailsSource) ?: [];
      foreach ($segments as $segment) {
        $value = trim(InputSanitizer::sanitizeString((string) $segment));
        if ($value !== '') {
          $parts[] = $value;
        }
      }
    }

    return implode("\n", $parts);
  }

  /**
   * POST businesses/{businessId}/invites/import/challenge/start
   *
   * Issues a short-lived CSRF-style challenge token for the bulk invite import
   * to protect against replay attacks before the commit step.
   */
  #[Route('businesses/{businessId}/invites/import/challenge/start', ['POST'])]
  /**
   * Handles startInviteImportChallenge operation.
   */
  public function startInviteImportChallenge(string $businessId): void
  {
    $allowedStrings = ['import_id'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $importIdRaw = $filtered['import_id'] ?? '';
    $importId = is_scalar($importIdRaw) ? (string) $importIdRaw : '';

    $service = new BusinessDiscoveryService();
    $result = $service->startBulkInviteImportChallenge(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $importId);

    if ($result['success']) {
      Response::success('[Business] Invite import challenge started.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/invites/import/challenge/verify
   *
   * Verifies the challenge token issued by startInviteImportChallenge and marks
   * the import as ready to commit.
   */
  #[Route('businesses/{businessId}/invites/import/challenge/verify', ['POST'])]
  /**
   * Handles verifyInviteImportChallenge operation.
   */
  public function verifyInviteImportChallenge(string $businessId): void
  {
    $allowedStrings = ['import_id', 'challenge_id', 'code'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $importIdRaw = $filtered['import_id'] ?? '';
    $challengeIdRaw = $filtered['challenge_id'] ?? '';
    $codeRaw = $filtered['code'] ?? '';

    $importId = is_scalar($importIdRaw) ? (string) $importIdRaw : '';
    $challengeId = is_scalar($challengeIdRaw) ? (string) $challengeIdRaw : '';
    $code = is_scalar($codeRaw) ? (string) $codeRaw : '';

    $service = new BusinessDiscoveryService();
    $result = $service->verifyBulkInviteImportChallenge(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $importId, $challengeId, $code);

    if ($result['success']) {
      Response::success('[Business] Invite import challenge verified.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/invites/import/commit
   *
   * Commits the verified bulk invite import, sending invitation emails to all
   * validated addresses.
   */
  #[Route('businesses/{businessId}/invites/import/commit', ['POST'])]
  /**
   * Handles commitInviteImport operation.
   */
  public function commitInviteImport(string $businessId): void
  {
    $allowedStrings = ['import_id', 'challenge_id'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $importIdRaw = $filtered['import_id'] ?? '';
    $challengeIdRaw = $filtered['challenge_id'] ?? '';
    $importId = is_scalar($importIdRaw) ? (string) $importIdRaw : '';
    $challengeId = is_scalar($challengeIdRaw) ? (string) $challengeIdRaw : '';

    $service = new BusinessDiscoveryService();
    $result = $service->commitBulkInviteImport(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $importId, $challengeId);

    if ($result['success']) {
      Response::success('[Business] Invite import committed.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * GET businesses/{businessId}/invites
   *
   * Returns a list of pending invitations for the specified business.
   */
  #[Route('businesses/{businessId}/invites', ['GET'])]
  /**
   * Handles listInvites operation.
   */
  public function listInvites(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $result = $service->listInvites(User::currentUUID(), InputSanitizer::sanitizeString($businessId));

    if ($result['success']) {
      Response::success('[Business] Invites retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * GET businesses/{businessId}/invites/history
   *
   * Returns the full invitation history (accepted, declined, revoked) for the
   * specified business.
   */
  #[Route('businesses/{businessId}/invites/history', ['GET'])]
  /**
   * Handles listInviteHistory operation.
   */
  public function listInviteHistory(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $result = $service->listInviteHistory(User::currentUUID(), InputSanitizer::sanitizeString($businessId));

    if ($result['success']) {
      Response::success('[Business] Invite history retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * GET businesses/{businessId}/access/requests
   *
   * Returns a list of pending membership access requests for the business.
   * Caller must be an owner or manager.
   */
  #[Route('businesses/{businessId}/access/requests', ['GET'])]
  /**
   * Handles listAccessRequests operation.
   */
  public function listAccessRequests(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $result = $service->listAccessRequests(User::currentUUID(), InputSanitizer::sanitizeString($businessId));

    if ($result['success']) {
      Response::success('Access requests loaded.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/access/requests/approve
   *
   * Approves a pending membership access request and adds the requester as a member.
   */
  #[Route('businesses/{businessId}/access/requests/approve', ['POST'])]
  /**
   * Handles approveAccessRequest operation.
   */
  public function approveAccessRequest(string $businessId): void
  {
    $allowedStrings = ['request_id', 'consent_id', 'consent_version', 'consent_acknowledged', 'disclaimer_text'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $requestIdRaw = $filtered['request_id'] ?? '';
    $requestId = is_scalar($requestIdRaw) ? InputSanitizer::sanitizeString((string) $requestIdRaw) : '';

    $consentContext = [
      'consent_id' => is_scalar($filtered['consent_id'] ?? null) ? InputSanitizer::sanitizeString((string) $filtered['consent_id']) : '',
      'consent_version' => is_scalar($filtered['consent_version'] ?? null) ? InputSanitizer::sanitizeString((string) $filtered['consent_version']) : '',
      'consent_acknowledged' => is_scalar($filtered['consent_acknowledged'] ?? null) ? (string) $filtered['consent_acknowledged'] : '',
      'disclaimer_text' => is_scalar($filtered['disclaimer_text'] ?? null) ? (string) $filtered['disclaimer_text'] : '',
      'ip' => isset($_SERVER['REMOTE_ADDR']) && is_scalar($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '',
      'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) && is_scalar($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '',
    ];

    $service = new BusinessDiscoveryService();
    $result = $service->approveAccessRequest(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $requestId, $consentContext);

    if ($result['success']) {
      Response::success('Request approved. The member can now access the business workspace.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/access/requests/reject
   *
   * Rejects a pending membership access request.
   */
  #[Route('businesses/{businessId}/access/requests/reject', ['POST'])]
  /**
   * Handles rejectAccessRequest operation.
   */
  public function rejectAccessRequest(string $businessId): void
  {
    $allowedStrings = ['request_id'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $requestIdRaw = $filtered['request_id'] ?? '';
    $requestId = is_scalar($requestIdRaw) ? InputSanitizer::sanitizeString((string) $requestIdRaw) : '';

    $service = new BusinessDiscoveryService();
    $result = $service->rejectAccessRequest(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $requestId);

    if ($result['success']) {
      Response::success('Request declined.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/invites/revoke
   *
   * Revokes a pending invitation so it can no longer be accepted.
   */
  #[Route('businesses/{businessId}/invites/revoke', ['POST'])]
  /**
   * Handles revokeInvite operation.
   */
  public function revokeInvite(string $businessId): void
  {
    $allowedStrings = ['invite_id'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $inviteIdRaw = $filtered['invite_id'] ?? '';
    $inviteID = is_scalar($inviteIdRaw) ? InputSanitizer::sanitizeString((string) $inviteIdRaw) : '';

    $service = new BusinessDiscoveryService();
    $result = $service->revokeInvite(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $inviteID);

    if ($result['success']) {
      Response::success('Invite cancelled.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/invites/accept
   *
   * Accepts a pending invitation (identified by token) and creates a membership
   * connection between the current user and the inviting business.
   */
  #[Route('businesses/invites/accept', ['POST'])]
  /**
   * Handles acceptInvite operation.
   */
  public function acceptInvite(): void
  {
    $allowedStrings = ['invite_token', 'consent_id', 'consent_version', 'consent_acknowledged', 'disclaimer_text'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $tokenRaw = $filtered['invite_token'] ?? '';
    $token = is_scalar($tokenRaw) ? (string) $tokenRaw : '';

    $consentContext = [
      'consent_id' => is_scalar($filtered['consent_id'] ?? null) ? InputSanitizer::sanitizeString((string) $filtered['consent_id']) : '',
      'consent_version' => is_scalar($filtered['consent_version'] ?? null) ? InputSanitizer::sanitizeString((string) $filtered['consent_version']) : '',
      'consent_acknowledged' => is_scalar($filtered['consent_acknowledged'] ?? null) ? (string) $filtered['consent_acknowledged'] : '',
      'disclaimer_text' => is_scalar($filtered['disclaimer_text'] ?? null) ? (string) $filtered['disclaimer_text'] : '',
      'ip' => isset($_SERVER['REMOTE_ADDR']) && is_scalar($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '',
      'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) && is_scalar($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '',
    ];

    $service = new BusinessDiscoveryService();
    $result = $service->acceptInvite($token, User::currentUUID(), $consentContext);

    if ($result['success']) {
      Response::success('Invite accepted. Your business membership is active.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST businesses/{businessId}/membership/accept
   *
   * Captures mandatory consent and processes membership acceptance.
   * Supports invite acceptance and consent capture for pending access requests.
   */
  #[Route('businesses/{businessId}/membership/accept', ['POST'])]
  /**
   * Handles acceptMembership operation.
   */
  public function acceptMembership(string $businessId): void
  {
    $allowedStrings = [
      'invite_token',
      'request_id',
      'consent_id',
      'consent_version',
      'consent_acknowledged',
      'disclaimer_text',
    ];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $payload = [
      'invite_token' => is_scalar($filtered['invite_token'] ?? null) ? (string) $filtered['invite_token'] : '',
      'request_id' => is_scalar($filtered['request_id'] ?? null) ? InputSanitizer::sanitizeString((string) $filtered['request_id']) : '',
      'consent_id' => is_scalar($filtered['consent_id'] ?? null) ? InputSanitizer::sanitizeString((string) $filtered['consent_id']) : '',
      'consent_version' => is_scalar($filtered['consent_version'] ?? null) ? InputSanitizer::sanitizeString((string) $filtered['consent_version']) : '',
      'consent_acknowledged' => is_scalar($filtered['consent_acknowledged'] ?? null) ? (string) $filtered['consent_acknowledged'] : '',
      'disclaimer_text' => is_scalar($filtered['disclaimer_text'] ?? null) ? (string) $filtered['disclaimer_text'] : '',
      'ip' => isset($_SERVER['REMOTE_ADDR']) && is_scalar($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '',
      'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) && is_scalar($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '',
    ];

    $service = new BusinessDiscoveryService();
    $result = $service->acceptMembershipWithConsent(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $payload);

    if ($result['success']) {
      Response::success('Membership accepted. Your data access choices are saved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST businesses/{businessId}/consent/grant
   *
   * Lets the current member grant protected business data sharing consent
   * without changing their membership.
   */
  #[Route('businesses/{businessId}/consent/grant', ['POST'])]
  public function grantBusinessDataConsent(string $businessId): void
  {
    $allowedStrings = [
      'consent_action',
      'consent_id',
      'consent_version',
      'consent_acknowledged',
      'disclaimer_text',
    ];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $payload = [
      'consent_id' => is_scalar($filtered['consent_id'] ?? null) ? InputSanitizer::sanitizeString((string) $filtered['consent_id']) : '',
      'consent_version' => is_scalar($filtered['consent_version'] ?? null) ? InputSanitizer::sanitizeString((string) $filtered['consent_version']) : 'v1',
      'consent_acknowledged' => is_scalar($filtered['consent_acknowledged'] ?? null) ? (string) $filtered['consent_acknowledged'] : '1',
      'disclaimer_text' => is_scalar($filtered['disclaimer_text'] ?? null) ? (string) $filtered['disclaimer_text'] : '',
      'ip' => isset($_SERVER['REMOTE_ADDR']) && is_scalar($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '',
      'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) && is_scalar($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '',
    ];

    $service = new BusinessDiscoveryService();
    $result = $service->grantBusinessDataConsent(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $payload);

    if ($result['success']) {
      Response::success('Data access allowed.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/consent/revoke
   *
   * Lets the current member revoke protected business data sharing consent
   * without leaving the business.
   */
  #[Route('businesses/{businessId}/consent/revoke', ['POST'])]
  public function revokeBusinessDataConsent(string $businessId): void
  {
    $filtered = self::filterBusinessPost(['consent_action'], []);

    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $service = new BusinessDiscoveryService();
    $result = $service->revokeBusinessDataConsent(User::currentUUID(), InputSanitizer::sanitizeString($businessId));

    if ($result['success']) {
      Response::success('Data access revoked.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/connections/revoke
   *
   * Permanently removes a member from the business (owner/manager only).
   */
  #[Route('businesses/{businessId}/connections/revoke', ['POST'])]
  /**
   * Handles revokeConnection operation.
   */
  public function revokeConnection(string $businessId): void
  {
    $allowedStrings = ['target_user_uuid'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('We could not read that request. Please try again.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $targetRaw = $filtered['target_user_uuid'] ?? '';
    $targetUUID = is_scalar($targetRaw) ? InputSanitizer::sanitizeString((string) $targetRaw) : '';

    $service = new BusinessDiscoveryService();
    $result = $service->revokeConnection(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $targetUUID);

    if ($result['success']) {
      Response::success('Member access removed.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/connections/update-role
   *
   * Updates the role of an existing member (e.g. member → manager).
   * Owner cannot change their own role.
   */
  #[Route('businesses/{businessId}/connections/update-role', ['POST'])]
  /**
   * Handles updateConnectionRole operation.
   */
  public function updateConnectionRole(string $businessId): void
  {
    $allowedStrings = ['target_user_uuid', 'role'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $targetRaw = $filtered['target_user_uuid'] ?? '';
    $targetUUID = is_scalar($targetRaw) ? InputSanitizer::sanitizeString((string) $targetRaw) : '';
    $roleRaw = $filtered['role'] ?? '';
    $role = is_scalar($roleRaw) ? InputSanitizer::sanitizeString((string) $roleRaw) : '';

    $service = new BusinessDiscoveryService();
    $result = $service->updateConnectionRole(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $targetUUID, $role);

    if ($result['success']) {
      Response::success('[Business] Connection role updated.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/leave
   *
   * Removes the current user from the specified business.
   * The business owner cannot leave without transferring ownership first.
   */
  #[Route('businesses/{businessId}/leave', ['POST'])]
  /**
   * Handle the current user's request to leave a business.
   */
  public function leaveBusiness(string $businessId): void
  {
    if (!self::requireBusinessCsrfPost()) {
      return;
    }

    $service = new BusinessDiscoveryService();
    $result = $service->leaveBusiness(User::currentUUID(), InputSanitizer::sanitizeString($businessId));

    if ($result['success']) {
      Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error($result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * GET businesses/{businessId}/cache/warm
   *
   * Pre-populates the business workspace cache (roster, sites, business reports,
   * member summaries). Intended to be called non-blocking from the dashboard.
   */
  #[Route('businesses/{businessId}/cache/warm', ['GET', 'POST'])]
  /**
   * Handles warmWorkspaceCache operation.
   */
  public function warmWorkspaceCache(string $businessId): void
  {
    $businessId = InputSanitizer::sanitizeString($businessId);
    $yearRaw = InputSanitizer::getString('year');
    $year = $yearRaw !== '' ? (int) $yearRaw : null;

    $result = BusinessWorkspaceWarmer::requestWarm($businessId, User::currentUUID(), $year);
    $warmStatus = $result['warm_status'];

    if ($warmStatus === 'denied') {
      Response::error('[Business] Business workspace cache warm denied.', $result, HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    if (in_array($warmStatus, ['accepted', 'in_progress'], true)) {
      Response::success(
        '[Business] Business workspace cache warm accepted.',
        $result,
        HttpStatus::HTTP_ACCEPTED,
      );
      return;
    }

    Response::success('[Business] Business workspace cache warm complete.', $result, HttpStatus::HTTP_OK);
  }

  /**
   * GET businesses/{businessId}/sites
   *
   * Returns all sites linked to the business, each with its org-level
   * site settings (budget, targets, lifecycle, etc.).
   */
  #[Route('businesses/{businessId}/sites', ['GET'])]
  /**
   * List sites linked to a business.
   */
  public function listBusinessSites(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $result  = $service->listBusinessSites(User::currentUUID(), InputSanitizer::sanitizeString($businessId));

    if ($result['success']) {
      Response::success('[Business] Business sites retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET businesses/{businessId}/sites/grid
   *
   * Returns a DataGrid-formatted list of sites linked to the specified business.
   */
  #[Route('businesses/{businessId}/sites/grid', ['GET'])]
  /**
   * Handles listBusinessSitesGrid operation.
   */
  public function listBusinessSitesGrid(string $businessId): void
  {
    $businessId = InputSanitizer::sanitizeString($businessId);
    $status = strtolower(trim((string) (InputSanitizer::getString('status') ?? SiteStatus::ACTIVE->value)));
    if (!in_array($status, [SiteStatus::ACTIVE->value, SiteStatus::INACTIVE->value, SiteStatus::ARCHIVED->value], true)) {
      $status = SiteStatus::ACTIVE->value;
    }

    $result = (new BusinessSitesGridRenderer())->renderForBusiness(
      User::currentUUID(),
      $businessId,
      [
        'search' => trim((string) (InputSanitizer::getString('search') ?? '')),
        'sort' => (string) (InputSanitizer::getString('sort') ?? 'site_name'),
        'direction' => (string) (InputSanitizer::getString('direction') ?? 'asc'),
        'page' => max(1, (int) (InputSanitizer::getString('page') ?? '1')),
        'status' => $status,
      ],
    );

    if (!$result['success']) {
      $serviceResult = $result['service_result'] ?? [
        'message' => $result['message'],
        'data' => [],
      ];
      Response::error(
        '[Business] ' . $result['message'],
        $serviceResult['data'],
        self::serviceFailureHttpStatus($serviceResult),
      );

      return;
    }

    Response::success($result['message'], [
      'html' => $result['html'],
    ], HttpStatus::HTTP_OK);
  }

  /**
   * POST businesses/{businessId}/sites/unlink
   *
   * Removes a linked site from the specified business.
   */
  #[Route('businesses/{businessId}/sites/unlink', ['POST'])]
  /**
   * Handles unlinkSite operation.
   */
  public function unlinkSite(string $businessId): void
  {
    $allowedStrings = ['site_id', 'site_owner_uuid'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $siteIdRaw = $filtered['site_id'] ?? '';
    $siteOwnerRaw = $filtered['site_owner_uuid'] ?? User::currentUUID();
    $siteId = is_scalar($siteIdRaw) ? InputSanitizer::sanitizeString((string) $siteIdRaw) : '';
    $siteOwner = is_scalar($siteOwnerRaw) ? InputSanitizer::sanitizeString((string) $siteOwnerRaw) : User::currentUUID();

    $service = new BusinessDiscoveryService();
    $result = $service->unlinkBusinessSite(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      $siteOwner,
      $siteId,
    );

    if ($result['success']) {
      Response::success('[Business] Site unlinked.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST businesses/{businessId}/sites/link
   *
   * Links one of the current user's sites to the specified business
   * so organisation members can see shared work data.
   */
  #[Route('businesses/{businessId}/sites/link', ['POST'])]
  /**
   * Handles linkSite operation.
   */
  public function linkSite(string $businessId): void
  {
    $allowedStrings = ['site_id', 'site_owner_uuid'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $siteIdRaw = $filtered['site_id'] ?? '';
    $siteOwnerRaw = $filtered['site_owner_uuid'] ?? User::currentUUID();
    $siteId = is_scalar($siteIdRaw) ? InputSanitizer::sanitizeString((string) $siteIdRaw) : '';
    $siteOwner = is_scalar($siteOwnerRaw) ? InputSanitizer::sanitizeString((string) $siteOwnerRaw) : User::currentUUID();

    $service = new BusinessDiscoveryService();
    $result = $service->linkSite(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $siteOwner, $siteId);

    if ($result['success']) {
      Response::success('[Business] Site linked.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST businesses/{businessId}/sites/create
   *
   * Creates a site owned by the current user and links it to the business.
   */
  #[Route('businesses/{businessId}/sites/create', ['POST'])]
  public function createBusinessSite(string $businessId): void
  {
    $allowedStrings = ['site_name', 'wage', 'living_out_allowance', 'travel_hours', 'province'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $service = new BusinessDiscoveryService();
    $result = $service->createBusinessSite(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      $filtered,
    );

    if ($result['success']) {
      Response::success('[Business] Business site created.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/unlink
   *
   * Removes a site association from the business workspace.
   */
  #[Route('businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/unlink', ['POST'])]
  public function unlinkBusinessSite(string $businessId, string $siteOwnerUUID, string $siteID): void
  {
    if (!self::requireBusinessCsrfPost()) {
      return;
    }

    $service = new BusinessDiscoveryService();
    $result = $service->unlinkBusinessSite(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      InputSanitizer::sanitizeString($siteOwnerUUID),
      InputSanitizer::sanitizeString($siteID),
    );

    if ($result['success']) {
      Response::success('[Business] Site unlinked.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET businesses/{businessId}/settings
   *
   * Returns the current settings/metadata for the specified business.
   */
  #[Route('businesses/{businessId}/settings', ['GET'])]
  /**
   * Handles getSettings operation.
   */
  public function getSettings(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $result = $service->getBusinessSettings(User::currentUUID(), InputSanitizer::sanitizeString($businessId));

    if ($result['success']) {
      Response::success('[Business] Business settings retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST businesses/{businessId}/settings/update
   *
   * Updates modifiable settings for the specified business (name, type, etc.).
   * Caller must be the owner.
   */
  #[Route('businesses/{businessId}/settings/update', ['POST'])]
  /**
   * Handles updateSettings operation.
   */
  public function updateSettings(string $businessId): void
  {
    $allowedStrings = [
      'name',
      'business_type',
      'role',
      'status',
      'pay_frequency',
      'pay_anchor',
      'pay_period_start',
      'pay_period_length',
      'editing_grace_days',
      'default_wage',
      'timezone',
      'currency',
      'legal_name',
      'industry',
      'registration_number',
      'tax_id',
      'employee_count',
      'founded_year',
      'contact_email',
      'contact_phone',
      'website',
      'indigenous_owned',
      'resident_on_reserve',
      'reserve_name',
      'address_line1',
      'address_line2',
      'address_city',
      'address_region',
      'address_postal',
      'address_country',
      'support_hours',
      'org_notes',
      'enforce_contact_domain',
      'allowed_contact_domains',
      'contact_payroll_name',
      'contact_payroll_image_url',
      'contact_payroll_email',
      'contact_payroll_phone',
      'contact_payroll_role',
      'contact_hr_name',
      'contact_hr_image_url',
      'contact_hr_email',
      'contact_hr_phone',
      'contact_hr_role',
      'contact_ceo_name',
      'contact_ceo_image_url',
      'contact_ceo_email',
      'contact_ceo_phone',
      'contact_ceo_role',
      'contact_coo_name',
      'contact_coo_image_url',
      'contact_coo_email',
      'contact_coo_phone',
      'contact_coo_role',
      'contact_cto_name',
      'contact_cto_image_url',
      'contact_cto_email',
      'contact_cto_phone',
      'contact_cto_role',
      'contact_support_name',
      'contact_support_image_url',
      'contact_support_email',
      'contact_support_phone',
      'contact_support_role',
      'contact_operations_name',
      'contact_operations_image_url',
      'contact_operations_email',
      'contact_operations_phone',
      'contact_operations_role',
      'contact_manager_name',
      'contact_manager_image_url',
      'contact_manager_email',
      'contact_manager_phone',
      'contact_manager_role',
      'contact_custom_json',
    ];
    $base64ImageFields = [
      'contact_payroll_image_url',
      'contact_hr_image_url',
      'contact_ceo_image_url',
      'contact_coo_image_url',
      'contact_cto_image_url',
      'contact_support_image_url',
      'contact_operations_image_url',
      'contact_manager_image_url',
    ];
    $rawStringFields = [
      'website',
      'contact_custom_json',
    ];
    $droppedKeys = [];
    $filtered = self::filterBusinessPost($allowedStrings, [], $droppedKeys, $base64ImageFields, $rawStringFields);

    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $service = new BusinessDiscoveryService();
    $result = $service->updateBusinessSettings(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $filtered);

    if ($result['success']) {
      Response::success('[Business] Business settings updated.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * GET businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/editor
   *
   * Returns site core fields and business planning settings for the editor dialog.
   */
  #[Route('businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/editor', ['GET'])]
  public function getBusinessSiteEditor(string $businessId, string $siteOwnerUUID, string $siteID): void
  {
    $service = new BusinessDiscoveryService();
    $result = $service->getBusinessSiteEditorData(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      InputSanitizer::sanitizeString($siteOwnerUUID),
      InputSanitizer::sanitizeString($siteID),
    );

    if ($result['success']) {
      Response::success('[Business] Business site editor data retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/settings
   *
   * Returns org-only settings for a specific linked site (budget, targets, status, tags).
   * Caller must be the org owner or a coordinator.
   */
  #[Route('businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/settings', ['GET'])]
  public function getBusinessSiteSettings(string $businessId, string $siteOwnerUUID, string $siteID): void
  {
    $service = new BusinessDiscoveryService();
    $result  = $service->getBusinessSiteSettings(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      InputSanitizer::sanitizeString($siteOwnerUUID),
      InputSanitizer::sanitizeString($siteID)
    );

    if ($result['success']) {
      Response::success('[Business] Business site settings retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/restore
   *
   * Restores an archived linked site and its archived work entries.
   */
  #[Route('businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/restore', ['POST'])]
  public function restoreBusinessSite(string $businessId, string $siteOwnerUUID, string $siteID): void
  {
    if (!self::requireBusinessCsrfPost()) {
      return;
    }

    $service = new BusinessDiscoveryService();
    $result  = $service->restoreBusinessSite(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      InputSanitizer::sanitizeString($siteOwnerUUID),
      InputSanitizer::sanitizeString($siteID),
    );

    if ($result['success']) {
      Response::success('[Business] Site restored.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/archive
   *
   * Archives a business-linked site while keeping it available in the Archived list.
   */
  #[Route('businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/archive', ['POST'])]
  public function archiveBusinessSite(string $businessId, string $siteOwnerUUID, string $siteID): void
  {
    if (!self::requireBusinessCsrfPost()) {
      return;
    }

    $service = new BusinessDiscoveryService();
    $result  = $service->archiveBusinessSite(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      InputSanitizer::sanitizeString($siteOwnerUUID),
      InputSanitizer::sanitizeString($siteID),
    );

    if ($result['success']) {
      Response::success('[Business] Site archived.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/permanent-delete
   *
   * Permanently deletes an archived business-linked site.
   */
  #[Route('businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/permanent-delete', ['POST'])]
  public function permanentDeleteBusinessSite(string $businessId, string $siteOwnerUUID, string $siteID): void
  {
    if (!self::requireBusinessCsrfPost()) {
      return;
    }

    $service = new BusinessDiscoveryService();
    $result  = $service->permanentDeleteBusinessSite(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      InputSanitizer::sanitizeString($siteOwnerUUID),
      InputSanitizer::sanitizeString($siteID),
    );

    if ($result['success']) {
      Response::success('[Business] Site permanently deleted.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/settings/update
   *
   * Persists org-only settings for a specific linked site.
   * Caller must be the org owner or a coordinator.
   */
  #[Route('businesses/{businessId}/sites/{siteOwnerUUID}/{siteID}/settings/update', ['POST'])]
  public function updateBusinessSiteSettings(string $businessId, string $siteOwnerUUID, string $siteID): void
  {
    $allowedStrings = [
      'budget_type', 'budget_amount', 'budget_start', 'budget_end',
      'warn_threshold', 'critical_threshold',
      'site_status', 'primary_manager_uuid',
      'target_headcount', 'target_utilization', 'target_ot_ratio',
      'tags', 'client_name', 'cost_code', 'start_date', 'end_date',
    ];
    $droppedKeys = [];
    $filtered    = self::filterBusinessPost($allowedStrings, [], $droppedKeys);

    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $service = new BusinessDiscoveryService();
    $result  = $service->updateBusinessSiteSettings(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      InputSanitizer::sanitizeString($siteOwnerUUID),
      InputSanitizer::sanitizeString($siteID),
      $filtered
    );

    if ($result['success']) {
      Response::success('[Business] Site settings updated.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/access/request
   *
   * Submits a membership access request from the current user to a specified
   * business whose discovery is enabled.
   */
  #[Route('businesses/access/request', ['POST'])]
  /**
   * Handles requestAccess operation.
   */
  public function requestAccess(): void
  {
    $allowedStrings = ['owner_email'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $ownerEmailRaw = $filtered['owner_email'] ?? '';
    $ownerEmail = is_scalar($ownerEmailRaw) ? (string) $ownerEmailRaw : '';

    $service = new BusinessDiscoveryService();
    $result = $service->requestAccessByOwnerEmail(User::currentUUID(), $ownerEmail);

    if ($result['success']) {
      Response::success('Request sent. No protected work data is shared by this request.', $result['data'], HttpStatus::HTTP_CREATED);
    } else {
      Response::error($result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET businesses/access/search
   *
   * Searches for discoverable businesses matching a query string.
   * Returns name, type, and membership request status.
   */
  #[Route('businesses/access/search', ['GET'])]
  /**
   * Handles searchAccessTargets operation.
   */
  public function searchAccessTargets(): void
  {
    $mode = strtolower(trim((string) (InputSanitizer::getString('mode') ?? '')));
    $limit = (int) (InputSanitizer::getString('limit') ?? '12');
    if ($limit < 1) {
      $limit = 12;
    }
    if ($limit > 25) {
      $limit = 25;
    }

    $query = trim((string) (InputSanitizer::getString('q') ?? ''));
    if ($mode === 'latest') {
      Response::success('[Business] Access lookup latest results generated.', [
        'suggestions' => [],
      ], HttpStatus::HTTP_OK);

      return;
    }

    if (mb_strlen($query) < 2) {
      Response::success('[Business] Access lookup query too short.', [
        'suggestions' => [],
      ], HttpStatus::HTTP_OK);

      return;
    }

    $currentUUID = User::currentUUID();
    $indexed = \PayCal\Domain\Business\BusinessSearchIndex::search($query, $limit, $currentUUID);
    $suggestions = [];

    foreach ($indexed as $row) {
      $businessId = trim((string) ($row['business_id'] ?? ''));
      $settings = $businessId !== ''
        ? Database::hgetall(Keys::BUSINESS_SETTINGS . ':' . $businessId)
        : [];

      $suggestions[] = [
        'source' => 'business',
        'email' => (string) ($row['email'] ?? ''),
        'name' => (string) ($row['name'] ?? ''),
        'business_name' => (string) ($row['business_name'] ?? ''),
        'public_profile' => $this->buildPublicBusinessProfile($settings),
      ];
    }

    Response::success('[Business] Access lookup results generated.', [
      'suggestions' => $suggestions,
    ], HttpStatus::HTTP_OK);
  }

  /**
   * POST businesses/{businessId}/ownership/transfer
   *
   * Transfers ownership of the specified business to another member.
   * Only the current owner may invoke this endpoint.
   */
  #[Route('businesses/{businessId}/ownership/transfer', ['POST'])]
  /**
   * Handles transferOwnership operation.
   */
  public function transferOwnership(string $businessId): void
  {
    $allowedStrings = ['target_user_uuid'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $targetRaw = $filtered['target_user_uuid'] ?? '';
    $targetUUID = is_scalar($targetRaw) ? InputSanitizer::sanitizeString((string) $targetRaw) : '';

    $service = new BusinessDiscoveryService();
    $result = $service->transferOwnership(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $targetUUID);

    if ($result['success']) {
      Response::success('[Business] Ownership transferred.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/encryption/bootstrap
   *
   * Bootstraps business DEK wraps for all active business members.
   */
  #[Route('businesses/{businessId}/encryption/bootstrap', ['POST'])]
  /**
   * Bootstrap business encryption for active members.
   */
  public function bootstrapBusinessEncryption(string $businessId): void
  {
    $allowedStrings = ['segment', 'version'];
    $filtered = self::filterBusinessPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $segmentRaw = $filtered['segment'] ?? BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD;
    $versionRaw = $filtered['version'] ?? '1';

    $segment = is_scalar($segmentRaw)
      ? InputSanitizer::sanitizeString((string) $segmentRaw)
      : BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD;
    $version = is_scalar($versionRaw)
      ? InputSanitizer::sanitizeString((string) $versionRaw)
      : '1';

    if ($segment === '') {
      $segment = BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD;
    }
    if ($version === '') {
      $version = '1';
    }

    $service = new BusinessDiscoveryService();
    $result = $service->bootstrapBusinessDekForAllMembers(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      $segment,
      $version
    );

    if ($result['success']) {
      Response::success('[Business] Business DEK bootstrap completed.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST businesses/encryption/auto-bootstrap
   *
   * Lightweight page-visit runner that opportunistically bootstraps business DEKs.
   */
  #[Route('businesses/encryption/auto-bootstrap', ['POST'])]
  /**
   * Evaluate opportunistic business encryption bootstrap on page visit.
   */
  public function autoBootstrapBusinessEncryption(): void
  {
    if (!self::requireBusinessCsrfPost()) {
      return;
    }

    $actorUUID = User::currentUUID();
    $actorThrottleKey = Keys::TELEMETRY . ':business:dek:auto_bootstrap:user:' . $actorUUID;
    // setnx is atomic (SET NX EX); eliminates the exists()→set() TOCTOU race
    // where two concurrent requests both observe the key absent and both proceed.
    if (!Database::setnx($actorThrottleKey, '1', 120)) {
      Response::success('[Business] Auto bootstrap skipped (throttled).', [
        'throttled' => true,
      ], HttpStatus::HTTP_OK);

      return;
    }

    $service = new BusinessDiscoveryService();
    $result = $service->autoBootstrapBusinessDekOnPageVisit($actorUUID);

    if ($result['success']) {
      Response::success('[Business] Auto bootstrap evaluated.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET businesses/{businessId}/connections
   *
   * Returns all active connections for the specified business.
   */
  #[Route('businesses/{businessId}/connections', ['GET'])]
  /**
   * Handles listConnections operation.
   */
  public function listConnections(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $result = $service->listConnections(User::currentUUID(), InputSanitizer::sanitizeString($businessId));

    if ($result['success']) {
      Response::success('[Business] Connections retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * GET businesses/{businessId}/audit
   *
   * Returns a flat chronological audit log for the specified business.
   */
  #[Route('businesses/{businessId}/audit', ['GET'])]
  /**
   * Handles listAuditTimeline operation.
   */
  public function listAuditTimeline(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $result = $service->listAuditTimeline(User::currentUUID(), InputSanitizer::sanitizeString($businessId));

    if ($result['success']) {
      Response::success('[Business] Audit timeline retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Generate audit control test.
   */
  #[Route('businesses/{businessId}/audit/control-test', ['POST'])]
  public function generateAuditControlTest(string $businessId): void
  {
    $filtered = self::filterBusinessPost(['summary'], []);
    if (false === $filtered) {
      Response::error('[Business] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $summaryRaw = $filtered['summary'] ?? '';
    $summary = is_scalar($summaryRaw) ? (string) $summaryRaw : '';

    $service = new BusinessAuditControlTestService();
    $result = $service->generateErrorTest(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      [
        'summary' => $summary,
        'source' => 'businesses_ui',
      ]
    );

    if ($result['success']) {
      Response::success('[Business] Audit control test generated.', $result['data'], HttpStatus::HTTP_CREATED);

      return;
    }

    $message = strtolower(trim($result['message']));
    $httpCode = str_contains($message, 'do not have permission')
      ? HttpStatus::HTTP_FORBIDDEN
      : (str_contains($message, 'gcs') ? HttpStatus::HTTP_INTERNAL_SERVER_ERROR : HttpStatus::HTTP_BAD_REQUEST);

    Response::error('[Business] ' . $result['message'], $result['data'], $httpCode);
  }

  /**
   * GET/POST businesses/{businessId}/audit/grid
   *
   * Returns a DataGrid-formatted audit-event timeline for the specified business.
   * Supports server-side paging via POST body parameters.
   */
  #[Route('businesses/{businessId}/audit/grid', ['GET', 'POST'])]
  /**
   * Handles listAuditTimelineGrid operation.
   */
  public function listAuditTimelineGrid(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $businessId = InputSanitizer::sanitizeString($businessId);
    $result = $service->listAuditTimeline(User::currentUUID(), $businessId);

    if (!$result['success']) {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $events = is_array($result['data']['events'] ?? null)
      ? $result['data']['events']
      : [];

    $this->renderAuditGridResponse($events, 'businesses-audit-grid', 'Business Audit Timeline', $businessId);
  }

  /**
   * GET businesses/{businessId}/audit/member
   *
   * Returns the audit timeline scoped to a specific member of the business.
   */
  #[Route('businesses/{businessId}/audit/member', ['GET'])]
  /**
   * Handles listMemberAuditTimeline operation.
   */
  public function listMemberAuditTimeline(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $result = $service->listAuditTimelineForMember(User::currentUUID(), InputSanitizer::sanitizeString($businessId));

    if ($result['success']) {
      Response::success('[Business] Member audit timeline retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET/POST businesses/{businessId}/audit/member/grid
   *
   * Returns a DataGrid-formatted audit-event timeline scoped to a specific member.
   * Supports server-side paging via POST body parameters.
   */
  #[Route('businesses/{businessId}/audit/member/grid', ['GET', 'POST'])]
  /**
   * Handles listMemberAuditTimelineGrid operation.
   */
  public function listMemberAuditTimelineGrid(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $businessId = InputSanitizer::sanitizeString($businessId);
    $result = $service->listAuditTimelineForMember(User::currentUUID(), $businessId);

    if (!$result['success']) {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $events = is_array($result['data']['events'] ?? null)
      ? $result['data']['events']
      : [];

    $this->renderAuditGridResponse($events, 'businesses-free-audit-grid', 'Connections Audit Timeline', $businessId);
  }

  /** @param array<int, mixed> $events */
  private function renderAuditGridResponse(array $events, string $gridId, string $title, string $businessId = ''): void
  {
    $orgName = '';
    $orgOwnerEmail = '';
    if ($businessId !== '') {
      $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
      $orgName = trim((string) ($business['name'] ?? ''));
      $ownerUUID = trim((string) ($business['owner_uuid'] ?? ''));
      if ($ownerUUID !== '') {
        $ownerUser = UserRepository::getByUUID($ownerUUID);
        if ($ownerUser !== null) {
          $orgOwnerEmail = trim($ownerUser->email);
        }
      }
    }

    $actorUUIDs = [];
    foreach ($events as $event) {
      if (!is_array($event)) {
        continue;
      }

      $actorUUID = isset($event['actor_uuid']) && is_scalar($event['actor_uuid'])
        ? trim((string) $event['actor_uuid'])
        : '';

      if ($actorUUID !== '') {
        $actorUUIDs[$actorUUID] = true;
      }
    }

    $actorLabels = [];
    foreach (array_keys($actorUUIDs) as $actorUUID) {
      $label = $actorUUID;
      $actor = UserRepository::getByUUID($actorUUID);
      if ($actor !== null) {
        $fullName = trim($actor->full_name);
        $email = trim($actor->email);
        if ($fullName !== '') {
          $label = $fullName;
        } elseif ($email !== '') {
          $label = $email;
        }
      }

      $actorLabels[$actorUUID] = $label;
    }

    $rows = array_map(static function (mixed $event) use ($actorLabels, $orgName, $orgOwnerEmail): array {
      if (!is_array($event)) {
        return [
          'id' => '',
          'created_at' => '',
          'event_type' => '',
          'actor' => '',
          'target' => '',
          'details' => '',
          'event_details_json' => '{}',
        ];
      }

      $detailsMap = self::decodeAuditDetails(isset($event['details']) && is_scalar($event['details']) ? (string) $event['details'] : '');
      $displayDetailsMap = self::presentAuditDetails($detailsMap);
      $detailsJson = json_encode($displayDetailsMap, JSON_UNESCAPED_SLASHES) ?: '{}';

      $enrichedDetails = $detailsMap;
      if (($enrichedDetails['business_name'] ?? '') === '' && $orgName !== '') {
        $enrichedDetails['business_name'] = $orgName;
      }
      if (($enrichedDetails['owner_email'] ?? '') === '' && $orgOwnerEmail !== '') {
        $enrichedDetails['owner_email'] = $orgOwnerEmail;
      }

      $createdAtRaw = isset($event['created_at']) && is_scalar($event['created_at']) ? (string) $event['created_at'] : '';

      return [
        'id' => isset($event['event_id']) && is_scalar($event['event_id']) ? (string) $event['event_id'] : '',
        'created_at' => TimestampFormatter::formatAuditTimestamp($createdAtRaw),
        'created_at_raw' => $createdAtRaw,
        'event_type' => self::presentAuditEventType(
          isset($event['event_type']) && is_scalar($event['event_type']) ? (string) $event['event_type'] : '',
        ),
        'actor' => isset($event['actor_uuid']) && is_scalar($event['actor_uuid'])
          ? (string) ($actorLabels[(string) $event['actor_uuid']] ?? (string) $event['actor_uuid'])
          : '',
        'target' => self::deriveAuditTarget($enrichedDetails),
        'details' => self::summarizeAuditDetails($displayDetailsMap),
        'event_details_json' => $detailsJson,
      ];
    }, $events);

    $search = trim($this->gridParam('search', ''));
    $sort = $this->gridParam('sort', 'created_at');
    $direction = strtolower($this->gridParam('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
    $page = max(1, (int) $this->gridParam('page', '1'));

    if ($search !== '') {
      $needle = mb_strtolower($search);
      $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
        foreach (['created_at', 'event_type', 'actor', 'target', 'details'] as $field) {
          if (mb_stripos((string) $row[$field], $needle) !== false) {
            return true;
          }
        }

        return false;
      }));
    }

    $allowedSorts = ['created_at', 'event_type', 'actor', 'target'];
    if (!in_array($sort, $allowedSorts, true)) {
      $sort = 'created_at';
    }

    usort($rows, static function (array $a, array $b) use ($sort, $direction): int {
      $aValue = (string) $a[$sort];
      $bValue = (string) $b[$sort];
      $comparison = strcasecmp($aValue, $bValue);

      return $direction === 'desc' ? -$comparison : $comparison;
    });

    $grid = DataGrid::create($gridId, $title);
    $grid->enableSearch('Filter audit events...');
    $grid->setSearchValue($search);
    $grid->enableSorting();
    $grid->addColumn('created_at', 'Timestamp', true);
    $grid->addColumn('event_type', 'Event', true);
    $grid->addColumn('actor', 'Actor', true);
    $grid->addColumn('target', 'Target', true);
    $grid->setItemLabel('events');

    $pager = ArrayPager::fromArray($rows, ['pageSize' => 25]);
    $pager->setPage($page);
    $html = $grid->table($pager);

    $start = $pager->getTotal() === 0 ? 0 : (($pager->getPage() - 1) * $pager->getPageSize()) + 1;
    $end = min($pager->getPage() * $pager->getPageSize(), $pager->getTotal());
    $total = $pager->getTotal();

    $searchAttr = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
    $sortAttr = htmlspecialchars($sort, ENT_QUOTES, 'UTF-8');
    $directionAttr = htmlspecialchars($direction, ENT_QUOTES, 'UTF-8');
    $gridIdAttr = htmlspecialchars($gridId, ENT_QUOTES, 'UTF-8');
    $escapedGridId = preg_quote($gridIdAttr, '/');
    $pattern = '/(<div\\s+id="' . $escapedGridId . '"[^>]*class="datagrid"[^>]*data-grid="' . $escapedGridId . '"[^>]*)>/';
    $replacement = '$1 data-search="' . $searchAttr . '" data-sort="' . $sortAttr . '" data-direction="' . $directionAttr . '" data-pagination-start="' . $start . '" data-pagination-end="' . $end . '" data-pagination-total="' . $total . '">';
    $html = (string) preg_replace($pattern, $replacement, $html, 1);

    // Build a mapping of event details indexed by row ID for JavaScript access
    $eventDetailsMap = [];
    foreach ($rows as $row) {
      if ($row['id'] === '') {
        continue;
      }

      $eventDetailsMap[(string) $row['id']] = [
        'event_details_json' => (string) $row['event_details_json'],
        'created_at' => (string) $row['created_at'],
        'created_at_raw' => (string) ($row['created_at_raw'] ?? ''),
        'event_type' => (string) $row['event_type'],
        'actor' => (string) $row['actor'],
        'target' => (string) $row['target'],
      ];
    }

    $eventDetailsJson = json_encode($eventDetailsMap, JSON_UNESCAPED_SLASHES) ?: '{}';
    $eventDetailsStore = '<div id="' . htmlspecialchars($gridId, ENT_QUOTES, 'UTF-8') . '_event_details" class="businesses_audit_event_details_store" hidden aria-hidden="true" data-event-details-json="' . htmlspecialchars($eventDetailsJson, ENT_QUOTES, 'UTF-8') . '"></div>';

    $html = $html . $eventDetailsStore;

    Response::success('[Business] Audit grid rendered.', [
      'html' => $html,
    ], HttpStatus::HTTP_OK);
  }

  /**
   * GET/POST businesses/{businessId}/invites/history/grid
   *
   * Returns a DataGrid-formatted invite history for the specified business.
   * Supports server-side paging via POST body parameters.
   */
  #[Route('businesses/{businessId}/invites/history/grid', ['GET', 'POST'])]
  /**
   * Handles listInviteHistoryGrid operation.
   */
  public function listInviteHistoryGrid(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $businessId = InputSanitizer::sanitizeString($businessId);
    $result = $service->listInviteHistory(User::currentUUID(), $businessId);

    if (!$result['success']) {
      Response::error('[Business] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $accessHistoryResult = $service->listAccessRequestHistory(User::currentUUID(), $businessId);
    if (!$accessHistoryResult['success']) {
      Response::error('[Business] ' . $accessHistoryResult['message'], $accessHistoryResult['data'], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $invites = is_array($result['data']['invites'] ?? null)
      ? $result['data']['invites']
      : [];

    $rows = array_map(static function (mixed $invite): array {
      if (!is_array($invite)) {
        return [
          'id' => '',
          'invitee_email' => '',
          'role' => '',
          'status' => '',
          'resolved_at' => '',
        ];
      }

      $scopes = isset($invite['scopes']) && is_array($invite['scopes']) ? $invite['scopes'] : [];

      return [
        'id' => isset($invite['invite_id']) && is_scalar($invite['invite_id']) ? (string) $invite['invite_id'] : '',
        'invitee_email' => isset($invite['invitee_email']) && is_scalar($invite['invitee_email']) ? (string) $invite['invitee_email'] : '',
        'role' => self::deriveInviteRoleFromScopes($scopes),
        'status' => isset($invite['status']) && is_scalar($invite['status']) ? (string) $invite['status'] : '',
        'resolved_at' => isset($invite['resolved_at']) && is_scalar($invite['resolved_at'])
          ? (string) $invite['resolved_at']
          : (isset($invite['created_at']) && is_scalar($invite['created_at']) ? (string) $invite['created_at'] : ''),
      ];
    }, $invites);

    $accessRequests = is_array($accessHistoryResult['data']['requests'] ?? null)
      ? $accessHistoryResult['data']['requests']
      : [];

    $requestRows = array_map(static function (mixed $request): array {
      if (!is_array($request)) {
        return [
          'id' => '',
          'invitee_email' => '',
          'role' => 'access request',
          'status' => '',
          'resolved_at' => '',
        ];
      }

      return [
        'id' => isset($request['request_id']) && is_scalar($request['request_id']) ? (string) $request['request_id'] : '',
        'invitee_email' => isset($request['requester_contact_email']) && is_scalar($request['requester_contact_email']) ? (string) $request['requester_contact_email'] : '',
        'role' => 'access request',
        'status' => isset($request['status']) && is_scalar($request['status']) ? (string) $request['status'] : '',
        'resolved_at' => isset($request['resolved_at']) && is_scalar($request['resolved_at']) ? (string) $request['resolved_at'] : '',
      ];
    }, $accessRequests);

    $rows = array_merge($rows, $requestRows);

    $search = trim($this->gridParam('search', ''));
    $sort = $this->gridParam('sort', 'resolved_at');
    $direction = strtolower($this->gridParam('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
    $page = max(1, (int) $this->gridParam('page', '1'));

    if ($search !== '') {
      $needle = mb_strtolower($search);
      $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
        foreach (['invitee_email', 'role', 'status', 'resolved_at'] as $field) {
          if (mb_stripos((string) $row[$field], $needle) !== false) {
            return true;
          }
        }

        return false;
      }));
    }

    $allowedSorts = ['invitee_email', 'role', 'status', 'resolved_at'];
    if (!in_array($sort, $allowedSorts, true)) {
      $sort = 'resolved_at';
    }

    usort($rows, static function (array $a, array $b) use ($sort, $direction): int {
      $aValue = (string) $a[$sort];
      $bValue = (string) $b[$sort];
      $comparison = strcasecmp($aValue, $bValue);

      return $direction === 'desc' ? -$comparison : $comparison;
    });

    $grid = DataGrid::create('businesses-invite-history-grid', 'Invite & Access Request History');
    $grid->enableSearch('Filter invite/request history...');
    $grid->enableSorting();
    $grid->addColumn('invitee_email', 'Email', true);
    $grid->addColumn('role', 'Role', true);
    $grid->addColumn('status', 'Status', true);
    $grid->addColumn('resolved_at', 'Timestamp', true);
    $grid->setItemLabel('history events');

    $pager = ArrayPager::fromArray($rows, ['pageSize' => 20]);
    $pager->setPage($page);
    $html = $grid->table($pager);

    $start = $pager->getTotal() === 0 ? 0 : (($pager->getPage() - 1) * $pager->getPageSize()) + 1;
    $end = min($pager->getPage() * $pager->getPageSize(), $pager->getTotal());
    $total = $pager->getTotal();

    $searchAttr = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
    $sortAttr = htmlspecialchars($sort, ENT_QUOTES, 'UTF-8');
    $directionAttr = htmlspecialchars($direction, ENT_QUOTES, 'UTF-8');
    $pattern = '/(<div\\s+id="businesses-invite-history-grid"[^>]*class="datagrid"[^>]*data-grid="businesses-invite-history-grid"[^>]*)>/';
    $replacement = '$1 data-search="' . $searchAttr . '" data-sort="' . $sortAttr . '" data-direction="' . $directionAttr . '" data-pagination-start="' . $start . '" data-pagination-end="' . $end . '" data-pagination-total="' . $total . '">';
    $html = (string) preg_replace($pattern, $replacement, $html, 1);

    Response::success('[Business] Invite history grid rendered.', [
      'html' => $html,
    ], HttpStatus::HTTP_OK);
  }

  /**
   * GET businesses/discovery
   *
   * Returns metadata about business discovery settings for the current user.
   */
  #[Route('businesses/discovery', ['GET'])]
  /**
   * Handles discovery operation.
   */
  public function discovery(): void
  {
    $service = new BusinessDiscoveryService();
    $result = $service->discoveryForUser(User::currentUUID());

    Response::success('[Business] Discovery generated.', $result['data'], HttpStatus::HTTP_OK);
  }

  /** @return array<string, string> */
  private static function decodeAuditDetails(string $detailsJson): array
  {
    $decoded = json_decode($detailsJson, true);
    if (!is_array($decoded)) {
      return [];
    }

    $normalized = [];
    foreach ($decoded as $key => $value) {
      if (!is_scalar($value)) {
        continue;
      }

      $normalized[(string) $key] = (string) $value;
    }

    return self::redactUUIDsFromDetails($normalized);
  }

  /** @param array<string, string> $details
   *  @return array<string, string>
   */
  private static function redactUUIDsFromDetails(array $details): array
  {
    $redactableKeys = [
      'event_id',
      'business_id',
      'organization_id',
      'site_id',
      'user_id',
      'request_id',
    ];

    $redacted = [];
    foreach ($details as $key => $value) {
      if (in_array($key, $redactableKeys, true) || str_ends_with($key, '_uuid')) {
        $redacted[$key] = '[REDACTED]';
      } else {
        $redacted[$key] = $value;
      }
    }

    return $redacted;
  }

  /** @param array<string, string> $details */
  private static function deriveAuditTarget(array $details): string
  {
    foreach (['business_name', 'owner_email'] as $key) {
      if (isset($details[$key]) && $details[$key] !== '') {
        return $details[$key];
      }
    }

    return '[target not resolved]';
  }

  /** @param array<string, string> $details */
  private static function summarizeAuditDetails(array $details): string
  {
    if ($details === []) {
      return '';
    }

    $parts = [];
    foreach ($details as $key => $value) {
      $parts[] = $key . ': ' . $value;
    }

    return implode(', ', $parts);
  }

  /**
   * Convert an audit event type into readable UI copy.
   */
  private static function presentAuditEventType(string $eventType): string
  {
    $normalized = strtolower(trim($eventType));
    $labels = [
      'access.request.approved' => 'Access request approved',
      'access.request.consented' => 'Consent captured',
      'access.request.notification' => 'Access request notification sent',
      'access.request.rejected' => 'Access request rejected',
      'access.requested' => 'Access requested',
      'audit.control_test.error_generated' => 'Audit control test generated',
      'business.created' => 'Business created',
      'invite.accepted' => 'Invitation accepted',
      'invite.bulk_challenge_started' => 'Bulk invite verification started',
      'invite.bulk_import_committed' => 'Bulk invite import completed',
      'invite.bulk_prepare' => 'Bulk invite import prepared',
      'invite.revoked' => 'Invitation revoked',
      'invite.sent' => 'Invitation sent',
      'business.consent.accepted' => 'Business consent recorded',
      'business.consent.granted_from_settings' => 'Data sharing approved',
      'business.consent.revoked_from_settings' => 'Data sharing revoked',
      'business.dek.wrap.bootstrap' => 'Secure access prepared',
      'business.dek.wrap.bootstrap.bulk' => 'Secure access prepared for members',
      'ownership.transferred' => 'Ownership transferred',
      'connection.revoked' => 'Business access revoked',
      'connection.role_updated' => 'Business role updated',
      'connection.withdrawn' => 'Member left business',
      'settings.updated' => 'Business settings updated',
      'site.linked' => 'Site connected',
      'site.unlinked' => 'Site removed',
      'site_settings.updated' => 'Site settings updated',
    ];

    return $labels[$normalized] ?? self::humanizeAuditToken($eventType);
  }

  /** @param array<string, string> $details
   *  @return array<string, string>
   */
  private static function presentAuditDetails(array $details): array
  {
    $display = [];
    foreach ($details as $key => $value) {
      $label = self::presentAuditDetailLabel($key);
      if (isset($display[$label])) {
        $label .= ' ' . (string) (count($display) + 1);
      }

      $display[$label] = self::presentAuditDetailValue($key, $value);
    }

    return $display;
  }

  /**
   * Convert an audit detail key into a readable label.
   */
  private static function presentAuditDetailLabel(string $key): string
  {
    $normalized = strtolower(trim($key));
    $labels = [
      'accepted_by' => 'Accepted by',
      'accepted_count' => 'Accepted invites',
      'already_invited_count' => 'Already invited',
      'already_member_count' => 'Already members',
      'authority_domain' => 'Allowed email domain',
      'authority_email' => 'Authority email',
      'batch_code' => 'Batch code',
      'bootstrapped_count' => 'Members prepared',
      'business_name' => 'Business',
      'business_type' => 'Business type',
      'challenge_id' => 'Verification challenge',
      'consent_id' => 'Consent record',
      'consent_version' => 'Consent version',
      'credential_id' => 'Passkey credential',
      'dek_id' => 'Secure access key',
      'duplicate_count' => 'Duplicate emails',
      'email_dispatch' => 'Email delivery',
      'failed_count' => 'Members skipped',
      'failure_count' => 'Failed invites',
      'fields' => 'Updated fields',
      'fields_updated' => 'Updated fields',
      'from_user_uuid' => 'Previous owner',
      'import_id' => 'Import session',
      'input_count' => 'Submitted emails',
      'invalid_count' => 'Invalid emails',
      'invitee_email' => 'Invitee email',
      'invitee_uuid' => 'Invitee',
      'invite_id' => 'Invitation',
      'key_version' => 'Secure access version',
      'name' => 'Business name',
      'owner_email' => 'Owner email',
      'requester_contact_email' => 'Requester email',
      'requester_uuid' => 'Requester',
      'request_id' => 'Access request',
      'revoked_consent_count' => 'Consent records revoked',
      'revoked_wrap_count' => 'Secure access records revoked',
      'role' => 'Role',
      'scopes' => Strings::i18n('BUSINESSES_ACCESS_SCOPES_LABEL'),
      'secure_bootstrap_skipped' => 'Secure setup',
      'segment' => 'Protected data area',
      'site_id' => 'Site',
      'site_owner_uuid' => 'Site owner',
      'status' => 'Status',
      'success_count' => 'Invites sent',
      'target_user_uuid' => 'Member',
      'to_user_uuid' => 'New owner',
      'truncated_count' => 'Skipped because of limit',
      'user_agent_hash' => 'Device check',
      'user_uuid' => 'Member',
      'wrong_domain_count' => 'Wrong email domain',
    ];

    return $labels[$normalized] ?? self::humanizeAuditToken($key);
  }

  /**
   * Convert an audit detail value into readable UI copy.
   */
  private static function presentAuditDetailValue(string $key, string $value): string
  {
    $normalizedKey = strtolower(trim($key));
    $trimmedValue = trim($value);

    if ($trimmedValue === '[REDACTED]') {
      return 'Protected';
    }

    if ($normalizedKey === 'scopes' || str_ends_with($normalizedKey, '_scopes')) {
      return BusinessPermissionPresenter::scopeListLabel($trimmedValue);
    }

    if (in_array($normalizedKey, ['fields', 'fields_updated'], true)) {
      return self::humanizeAuditTokenList($trimmedValue);
    }

    if ($normalizedKey === 'email_dispatch') {
      return match (strtolower($trimmedValue)) {
        'sent' => 'Sent',
        'failed' => 'Not sent',
        default => self::humanizeAuditToken($trimmedValue),
      };
    }

    if ($normalizedKey === 'secure_bootstrap_skipped' && $trimmedValue === 'business_shared_encryption_disabled') {
      return 'Secure setup was not required for this workspace.';
    }

    if ($normalizedKey === 'segment') {
      return match (strtolower($trimmedValue)) {
        'current_period' => 'Current pay period',
        'archive' => 'Archive',
        default => self::humanizeAuditToken($trimmedValue),
      };
    }

    if (in_array($normalizedKey, ['business_type', 'role', 'status'], true)) {
      return self::humanizeAuditToken($trimmedValue);
    }

    return $trimmedValue;
  }

  /**
   * Convert a comma-separated audit token list into readable text.
   */
  private static function humanizeAuditTokenList(string $value): string
  {
    $tokens = preg_split('/\s*,\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($tokens) || $tokens === []) {
      return '';
    }

    return implode(', ', array_map(static fn (string $token): string => self::humanizeAuditToken($token), $tokens));
  }

  /**
   * Convert an audit token into readable text.
   */
  private static function humanizeAuditToken(string $value): string
  {
    $trimmed = trim($value);
    if ($trimmed === '') {
      return '';
    }

    $normalized = preg_replace('/[\._-]+/', ' ', $trimmed);
    $normalized = is_string($normalized) ? preg_replace('/\s+/', ' ', $normalized) : $trimmed;
    $normalized = is_string($normalized) ? trim($normalized) : $trimmed;
    if ($normalized === '') {
      return '';
    }

    return ucwords(strtolower($normalized));
  }

  /** @param array<int, mixed> $scopes */
  private static function deriveInviteRoleFromScopes(array $scopes): string
  {
    $values = [];
    foreach ($scopes as $scope) {
      if (!is_scalar($scope)) {
        continue;
      }

      $value = trim((string) $scope);
      if ($value !== '') {
        $values[] = $value;
      }
    }

    if (in_array('access.manage', $values, true) || in_array('business.settings.write', $values, true)) {
      return 'manager';
    }
    if (in_array('sites.write', $values, true)
      || (in_array('work.write', $values, true) && in_array('work.scope.business', $values, true))) {
      return 'contributor';
    }
    if (in_array('work.self.write', $values, true)
      || (in_array('work.write', $values, true) && in_array('work.scope.self', $values, true))) {
      return 'member';
    }
    if ($values !== []) {
      return 'viewer';
    }

    return 'member';
  }

  /**
   * Handles gridParam operation.
   */
  private function gridParam(string $key, string $default = ''): string
  {
    $value = InputSanitizer::getString($key);
    if (is_string($value)) {
      return $value;
    }

    static $jsonPayload = null;
    if (!is_array($jsonPayload)) {
      $raw = file_get_contents('php://input');
      $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
      $jsonPayload = is_array($decoded) ? $decoded : [];
    }

    if (array_key_exists($key, $jsonPayload) && is_scalar($jsonPayload[$key])) {
      return (string) $jsonPayload[$key];
    }

    return $default;
  }

  /**
   * @param array<string, mixed> $settings
   * @return array<string, string>
   */
  private function buildPublicBusinessProfile(array $settings): array
  {
    $allowedFields = [
      'legal_name',
      'industry',
      'registration_number',
      'tax_id',
      'employee_count',
      'founded_year',
      'contact_email',
      'contact_phone',
      'website',
      'indigenous_owned',
      'resident_on_reserve',
      'reserve_name',
      'address_line1',
      'address_line2',
      'address_city',
      'address_region',
      'address_postal',
      'address_country',
      'support_hours',
    ];

    $publicProfile = [];
    foreach ($allowedFields as $field) {
      $rawValue = $settings[$field] ?? '';
      $value = is_scalar($rawValue)
        ? trim((string) $rawValue)
        : '';
      if ($value === '') {
        continue;
      }

      $publicProfile[$field] = $value;
    }

    return $publicProfile;
  }

  /**
   * GET businesses/{businessId}/audit
   *
   * Returns the full org audit timeline for org owners and coordinators.
   * Callers without manage-access are rejected by the service layer.
   * Members can use /audit/member to see only events related to them.
   */
  #[Route('businesses/{businessId}/audit', ['GET'])]
  /**
   * Handles getAuditTimeline operation.
   */
  public function getAuditTimeline(string $businessId): void
  {
    $businessId   = InputSanitizer::sanitizeString($businessId);
    $service = new BusinessDiscoveryService();
    $result  = $service->listAuditTimeline(User::currentUUID(), $businessId);

    \PayCal\Infrastructure\Audit\SystemAuditRepository::recordReadAccess(
      User::currentUUID(),
      'org_audit_timeline_read'
    );

    if ($result['success']) {
      Response::success('[Business] Business audit timeline retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * GET businesses/{businessId}/audit/member
   *
   * Returns the member-scoped audit view for the current user, showing only
   * events related to that user's own membership activity in this org.
   */
  #[Route('businesses/{businessId}/audit/member', ['GET'])]
  /**
   * Handles getAuditTimelineForMember operation.
   */
  public function getAuditTimelineForMember(string $businessId): void
  {
    $businessId   = InputSanitizer::sanitizeString($businessId);
    $service = new BusinessDiscoveryService();
    $result  = $service->listAuditTimelineForMember(User::currentUUID(), $businessId);

    if ($result['success']) {
      Response::success('[Business] Member audit timeline retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Business] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }
}
