<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\ArrayPager;
use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\DataGrid;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\InputSanitizer;
use PayCal\Infrastructure\Audit\BusinessAuditControlTestService;
use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Domain\BusinessMemberReportsService;
use PayCal\Domain\BusinessMembersGridRenderer;
use PayCal\Domain\BusinessSitesGridRenderer;
use PayCal\Domain\BusinessWorkspaceWarmer;
use PayCal\Domain\ForecastProjectionService;
use PayCal\Domain\ForecastScenario;
use PayCal\Domain\Enums\SiteStatus;
use PayCal\Domain\RequestGuard;
use PayCal\Domain\Response;
use PayCal\Domain\TimestampFormatter;
use PayCal\Domain\User;
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
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

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
      Response::success('[OrgC] Business created.', $result['data'], HttpStatus::HTTP_CREATED);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
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

    Response::success('[OrgC] Businesses retrieved.', $result['data'], HttpStatus::HTTP_OK);
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
    $service = new BusinessDiscoveryService();
    $businessId = InputSanitizer::sanitizeString($businessId);
    $result = $service->markBusinessNotificationsRead(User::currentUUID(), $businessId);

    if ($result['success']) {
      Response::success('[OrgC] Business notifications marked read.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
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
    $service = new BusinessDiscoveryService();
    $result = $service->listForUser(User::currentUUID());

    if (!$result['success']) {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));

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

    Response::success('[OrgC] Businesses grid rendered.', [
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
        '[OrgC] ' . $result['message'],
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
        '[OrgC] ' . $result['message'],
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
    $filtered = RequestGuard::filterPost($allowedStrings, $allowedArrays);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

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
      $relationshipPattern = Keys::BUSINESS_RELATIONSHIP . ':' . InputSanitizer::sanitizeString($businessId) . ':*';
      foreach (Database::scanKeys($relationshipPattern) as $relationshipKey) {
        $parts = explode(':', (string) $relationshipKey);
        // business:relationship:{businessId}:{memberUUID}
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
      Response::success('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
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
        '[OrgC] ' . $result['message'],
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
        '[OrgC] ' . $result['message'],
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
        '[OrgC] ' . $result['message'],
        $result['data'],
        HttpStatus::HTTP_FORBIDDEN,
      );

      return;
    }

    Response::success($result['message'], $result['data'], HttpStatus::HTTP_OK);
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
        '[OrgC] ' . $result['message'],
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
    $businessId = InputSanitizer::sanitizeString($businessId);
    $memberUUID = InputSanitizer::sanitizeString($memberUUID);

    $context = (new BusinessMemberReportsService())->resolveMemberForecastAccess(
      User::currentUUID(),
      $businessId,
      $memberUUID,
    );

    if (!$context['success']) {
      Response::error(
        '[OrgC] ' . $context['message'],
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
        Response::error('[OrgC] Invalid JSON payload.', [], HttpStatus::HTTP_BAD_REQUEST);

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
    Response::success('[OrgC] Member forecast preview calculated.', $state, HttpStatus::HTTP_OK);
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
        '[OrgC] ' . $result['message'],
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
    $filtered = RequestGuard::filterPost($allowedStrings, $allowedArrays);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

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
      Response::success('[OrgC] Invite created.', $result['data'], HttpStatus::HTTP_CREATED);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
    $filtered = RequestGuard::filterPost($allowedStrings, $allowedArrays);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $payloadValidation = $this->validateInviteImportPayload($filtered);
    if (!$payloadValidation['valid']) {
      Response::error('[OrgC] Malformed import payload.', [
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
      Response::success('[OrgC] Invite import prepared.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
      $details['scopes'] = 'Expected an array of invite scopes.';
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
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $importIdRaw = $filtered['import_id'] ?? '';
    $importId = is_scalar($importIdRaw) ? (string) $importIdRaw : '';

    $service = new BusinessDiscoveryService();
    $result = $service->startBulkInviteImportChallenge(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $importId);

    if ($result['success']) {
      Response::success('[OrgC] Invite import challenge started.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

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
      Response::success('[OrgC] Invite import challenge verified.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $importIdRaw = $filtered['import_id'] ?? '';
    $challengeIdRaw = $filtered['challenge_id'] ?? '';
    $importId = is_scalar($importIdRaw) ? (string) $importIdRaw : '';
    $challengeId = is_scalar($challengeIdRaw) ? (string) $challengeIdRaw : '';

    $service = new BusinessDiscoveryService();
    $result = $service->commitBulkInviteImport(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $importId, $challengeId);

    if ($result['success']) {
      Response::success('[OrgC] Invite import committed.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
      Response::success('[OrgC] Invites retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
      Response::success('[OrgC] Invite history retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
      Response::success('[OrgC] Access requests retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

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
      Response::success('[OrgC] Access request approved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $requestIdRaw = $filtered['request_id'] ?? '';
    $requestId = is_scalar($requestIdRaw) ? InputSanitizer::sanitizeString((string) $requestIdRaw) : '';

    $service = new BusinessDiscoveryService();
    $result = $service->rejectAccessRequest(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $requestId);

    if ($result['success']) {
      Response::success('[OrgC] Access request rejected.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $inviteIdRaw = $filtered['invite_id'] ?? '';
    $inviteID = is_scalar($inviteIdRaw) ? InputSanitizer::sanitizeString((string) $inviteIdRaw) : '';

    $service = new BusinessDiscoveryService();
    $result = $service->revokeInvite(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $inviteID);

    if ($result['success']) {
      Response::success('[OrgC] Invite revoked.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/invites/accept
   *
   * Accepts a pending invitation (identified by token) and creates a membership
   * relationship between the current user and the inviting business.
   */
  #[Route('businesses/invites/accept', ['POST'])]
  /**
   * Handles acceptInvite operation.
   */
  public function acceptInvite(): void
  {
    $allowedStrings = ['invite_token', 'consent_id', 'consent_version', 'consent_acknowledged', 'disclaimer_text'];
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

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
      Response::success('[OrgC] Invite accepted.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
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
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

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
      Response::success('[OrgC] Membership consent flow completed.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST businesses/{businessId}/relationships/revoke
   *
   * Permanently removes a member from the business (owner/manager only).
   */
  #[Route('businesses/{businessId}/relationships/revoke', ['POST'])]
  /**
   * Handles revokeRelationship operation.
   */
  public function revokeRelationship(string $businessId): void
  {
    $allowedStrings = ['target_user_uuid'];
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $targetRaw = $filtered['target_user_uuid'] ?? '';
    $targetUUID = is_scalar($targetRaw) ? InputSanitizer::sanitizeString((string) $targetRaw) : '';

    $service = new BusinessDiscoveryService();
    $result = $service->revokeRelationship(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $targetUUID);

    if ($result['success']) {
      Response::success('[OrgC] Relationship revoked.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/relationships/update-role
   *
   * Updates the role of an existing member (e.g. member → manager).
   * Owner cannot change their own role.
   */
  #[Route('businesses/{businessId}/relationships/update-role', ['POST'])]
  /**
   * Handles updateRelationshipRole operation.
   */
  public function updateRelationshipRole(string $businessId): void
  {
    $allowedStrings = ['target_user_uuid', 'role'];
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $targetRaw = $filtered['target_user_uuid'] ?? '';
    $targetUUID = is_scalar($targetRaw) ? InputSanitizer::sanitizeString((string) $targetRaw) : '';
    $roleRaw = $filtered['role'] ?? '';
    $role = is_scalar($roleRaw) ? InputSanitizer::sanitizeString((string) $roleRaw) : '';

    $service = new BusinessDiscoveryService();
    $result = $service->updateRelationshipRole(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $targetUUID, $role);

    if ($result['success']) {
      Response::success('[OrgC] Relationship role updated.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
   * Handles leaveBusinessLegacyAlias operation.
   */
  public function leaveBusiness(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $result = $service->leaveBusiness(User::currentUUID(), InputSanitizer::sanitizeString($businessId));

    if ($result['success']) {
      Response::success('[OrgC] Business relationship withdrawn.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * Backward-compatibility alias for callers still invoking the legacy method.
   */
  public function leaveBusinessLegacyAlias(string $businessId): void
  {
    $this->leaveBusiness($businessId);
  }

  /**
   * GET businesses/{businessId}/cache/warm
   *
   * Pre-populates the business workspace cache (roster, sites, team earnings,
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
      Response::error('[OrgC] Business workspace cache warm denied.', $result, HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    if (in_array($warmStatus, ['accepted', 'in_progress'], true)) {
      Response::success(
        '[OrgC] Business workspace cache warm accepted.',
        $result,
        HttpStatus::HTTP_ACCEPTED,
      );
      return;
    }

    Response::success('[OrgC] Business workspace cache warm complete.', $result, HttpStatus::HTTP_OK);
  }

  /**
   * GET businesses/{businessId}/sites
   *
   * Returns all sites linked to the business, each with its org-level
   * site settings (budget, targets, lifecycle, etc.).
   */
  #[Route('businesses/{businessId}/sites', ['GET'])]
  /**
   * Handles listOrgSites operation.
   */
  public function listBusinessSites(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $result  = $service->listBusinessSites(User::currentUUID(), InputSanitizer::sanitizeString($businessId));

    if ($result['success']) {
      Response::success('[OrgC] Org sites retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
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
        '[OrgC] ' . $result['message'],
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
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

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
      Response::success('[OrgC] Site unlinked.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Backward-compatibility alias for callers still invoking the legacy method.
   */
  public function listOrgSites(string $businessId): void
  {
    $this->listBusinessSites($businessId);
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
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $siteIdRaw = $filtered['site_id'] ?? '';
    $siteOwnerRaw = $filtered['site_owner_uuid'] ?? User::currentUUID();
    $siteId = is_scalar($siteIdRaw) ? InputSanitizer::sanitizeString((string) $siteIdRaw) : '';
    $siteOwner = is_scalar($siteOwnerRaw) ? InputSanitizer::sanitizeString((string) $siteOwnerRaw) : User::currentUUID();

    $service = new BusinessDiscoveryService();
    $result = $service->linkSite(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $siteOwner, $siteId);

    if ($result['success']) {
      Response::success('[OrgC] Site linked.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
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
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $service = new BusinessDiscoveryService();
    $result = $service->createBusinessSite(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      $filtered,
    );

    if ($result['success']) {
      Response::success('[OrgC] Business site created.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
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
    $service = new BusinessDiscoveryService();
    $result = $service->unlinkBusinessSite(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      InputSanitizer::sanitizeString($siteOwnerUUID),
      InputSanitizer::sanitizeString($siteID),
    );

    if ($result['success']) {
      Response::success('[OrgC] Site unlinked.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
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
      Response::success('[OrgC] Business settings retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
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
    $filtered = RequestGuard::filterPost($allowedStrings, [], $droppedKeys, $base64ImageFields, $rawStringFields);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $service = new BusinessDiscoveryService();
    $result = $service->updateBusinessSettings(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $filtered);

    if ($result['success']) {
      Response::success('[OrgC] Business settings updated.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
      Response::success('[OrgC] Business site editor data retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
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
      Response::success('[OrgC] Org site settings retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Backward-compatibility alias for callers still invoking the legacy method.
   */
  public function getOrgSiteSettings(string $businessId, string $siteOwnerUUID, string $siteID): void
  {
    $this->getBusinessSiteSettings($businessId, $siteOwnerUUID, $siteID);
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
    $filtered    = RequestGuard::filterPost($allowedStrings, [], $droppedKeys);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

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
      Response::success('[OrgC] Site settings updated.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * Backward-compatibility alias for callers still invoking the legacy method.
   */
  public function updateOrgSiteSettings(string $businessId, string $siteOwnerUUID, string $siteID): void
  {
    $this->updateBusinessSiteSettings($businessId, $siteOwnerUUID, $siteID);
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
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $ownerEmailRaw = $filtered['owner_email'] ?? '';
    $ownerEmail = is_scalar($ownerEmailRaw) ? (string) $ownerEmailRaw : '';

    $service = new BusinessDiscoveryService();
    $result = $service->requestAccessByOwnerEmail(User::currentUUID(), $ownerEmail);

    if ($result['success']) {
      Response::success('[OrgC] Access request submitted.', $result['data'], HttpStatus::HTTP_CREATED);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
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
      Response::success('[OrgC] Access lookup latest results generated.', [
        'suggestions' => [],
      ], HttpStatus::HTTP_OK);

      return;
    }

    if (mb_strlen($query) < 2) {
      Response::success('[OrgC] Access lookup query too short.', [
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

    Response::success('[OrgC] Access lookup results generated.', [
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
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $targetRaw = $filtered['target_user_uuid'] ?? '';
    $targetUUID = is_scalar($targetRaw) ? InputSanitizer::sanitizeString((string) $targetRaw) : '';

    $service = new BusinessDiscoveryService();
    $result = $service->transferOwnership(User::currentUUID(), InputSanitizer::sanitizeString($businessId), $targetUUID);

    if ($result['success']) {
      Response::success('[OrgC] Ownership transferred.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST businesses/{businessId}/encryption/bootstrap
   *
   * Bootstraps org DEK wraps for all active business members.
   */
  #[Route('businesses/{businessId}/encryption/bootstrap', ['POST'])]
  /**
   * Bootstrap business encryption for active members.
   */
  public function bootstrapBusinessEncryption(string $businessId): void
  {
    $allowedStrings = ['segment', 'version'];
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $segmentRaw = $filtered['segment'] ?? BusinessDiscoveryService::ORG_DEK_SEGMENT_CURRENT_PERIOD;
    $versionRaw = $filtered['version'] ?? '1';

    $segment = is_scalar($segmentRaw)
      ? InputSanitizer::sanitizeString((string) $segmentRaw)
      : BusinessDiscoveryService::ORG_DEK_SEGMENT_CURRENT_PERIOD;
    $version = is_scalar($versionRaw)
      ? InputSanitizer::sanitizeString((string) $versionRaw)
      : '1';

    if ($segment === '') {
      $segment = BusinessDiscoveryService::ORG_DEK_SEGMENT_CURRENT_PERIOD;
    }
    if ($version === '') {
      $version = '1';
    }

    $service = new BusinessDiscoveryService();
    $result = $service->bootstrapOrgDekForAllMembers(
      User::currentUUID(),
      InputSanitizer::sanitizeString($businessId),
      $segment,
      $version
    );

    if ($result['success']) {
      Response::success('[OrgC] Business DEK bootstrap completed.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Backward-compatibility alias for callers still invoking the legacy method.
   */
  public function bootstrapBusinessEncryptionLegacyAlias(string $businessId): void
  {
    $this->bootstrapBusinessEncryption($businessId);
  }

  /**
   * POST businesses/encryption/auto-bootstrap
   *
   * Lightweight page-visit runner that opportunistically bootstraps org DEKs.
   */
  #[Route('businesses/encryption/auto-bootstrap', ['POST'])]
  /**
   * Evaluate opportunistic business encryption bootstrap on page visit.
   */
  public function autoBootstrapBusinessEncryption(): void
  {
    $actorUUID = User::currentUUID();
    $actorThrottleKey = Keys::TELEMETRY . ':org:dek:auto_bootstrap:user:' . $actorUUID;
    // setnx is atomic (SET NX EX); eliminates the exists()→set() TOCTOU race
    // where two concurrent requests both observe the key absent and both proceed.
    if (!Database::setnx($actorThrottleKey, '1', 120)) {
      Response::success('[OrgC] Auto bootstrap skipped (throttled).', [
        'throttled' => true,
      ], HttpStatus::HTTP_OK);

      return;
    }

    $service = new BusinessDiscoveryService();
    $result = $service->autoBootstrapOrgDekOnPageVisit($actorUUID);

    if ($result['success']) {
      Response::success('[OrgC] Auto bootstrap evaluated.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Backward-compatibility alias for callers still invoking the legacy method.
   */
  public function autoBootstrapBusinessEncryptionLegacyAlias(): void
  {
    $this->autoBootstrapBusinessEncryption();
  }

  /**
   * GET businesses/{businessId}/relationships
   *
   * Returns all active membership relationships for the specified business.
   */
  #[Route('businesses/{businessId}/relationships', ['GET'])]
  /**
   * Handles listRelationships operation.
   */
  public function listRelationships(string $businessId): void
  {
    $service = new BusinessDiscoveryService();
    $result = $service->listRelationships(User::currentUUID(), InputSanitizer::sanitizeString($businessId));

    if ($result['success']) {
      Response::success('[OrgC] Relationships retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
      Response::success('[OrgC] Audit timeline retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  #[Route('businesses/{businessId}/audit/control-test', ['POST'])]
  public function generateAuditControlTest(string $businessId): void
  {
    $filtered = RequestGuard::filterPost(['summary'], []);
    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

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
      Response::success('[OrgC] Audit control test generated.', $result['data'], HttpStatus::HTTP_CREATED);

      return;
    }

    $message = strtolower(trim($result['message']));
    $httpCode = str_contains($message, 'do not have permission')
      ? HttpStatus::HTTP_FORBIDDEN
      : (str_contains($message, 'gcs') ? HttpStatus::HTTP_INTERNAL_SERVER_ERROR : HttpStatus::HTTP_BAD_REQUEST);

    Response::error('[OrgC] ' . $result['message'], $result['data'], $httpCode);
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
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);

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
      Response::success('[OrgC] Member audit timeline retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
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
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $events = is_array($result['data']['events'] ?? null)
      ? $result['data']['events']
      : [];

    $this->renderAuditGridResponse($events, 'businesses-free-audit-grid', 'My Business Audit Timeline', $businessId);
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
      $detailsJson = json_encode($detailsMap, JSON_UNESCAPED_SLASHES) ?: '{}';

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
        'event_type' => isset($event['event_type']) && is_scalar($event['event_type']) ? (string) $event['event_type'] : '',
        'actor' => isset($event['actor_uuid']) && is_scalar($event['actor_uuid'])
          ? (string) ($actorLabels[(string) $event['actor_uuid']] ?? (string) $event['actor_uuid'])
          : '',
        'target' => self::deriveAuditTarget($enrichedDetails),
        'details' => self::summarizeAuditDetails($detailsMap),
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

    Response::success('[OrgC] Audit grid rendered.', [
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
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $accessHistoryResult = $service->listAccessRequestHistory(User::currentUUID(), $businessId);
    if (!$accessHistoryResult['success']) {
      Response::error('[OrgC] ' . $accessHistoryResult['message'], $accessHistoryResult['data'], HttpStatus::HTTP_BAD_REQUEST);

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

    Response::success('[OrgC] Invite history grid rendered.', [
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

    Response::success('[OrgC] Discovery generated.', $result['data'], HttpStatus::HTTP_OK);
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
      $parts[] = $key . '=' . $value;
    }

    return implode(', ', $parts);
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

    if (in_array('access.manage', $values, true) || in_array('org.settings.write', $values, true)) {
      return 'manager';
    }
    if (in_array('sites.write', $values, true)
      || (in_array('work.write', $values, true) && in_array('work.scope.org', $values, true))) {
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
      Response::success('[OrgC] Business audit timeline retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
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
      Response::success('[OrgC] Member audit timeline retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }
}


