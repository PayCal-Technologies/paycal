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
use PayCal\Domain\OrganizationDiscoveryService;
use PayCal\Domain\RequestGuard;
use PayCal\Domain\Response;
use PayCal\Domain\TimestampFormatter;
use PayCal\Domain\User;
use PayCal\Domain\UserRepository;

/**
 * OrganizationDiscoveryController.php
 *
 * Purpose: HTTP adapter for organization discovery, invite workflows, access
 * requests, membership management, settings, and audit-grid endpoints.
 *
 * Developer notes:
 * - This controller should stay thin and delegate policy decisions to
 *   OrganizationDiscoveryService.
 * - Keep request parsing and response shaping here; keep role/scope rules in
 *   the domain layer so controller endpoints remain consistent.
 * - Grid endpoints are contract-sensitive because the organizations UI depends
 *   on stable column keys, sorting, and paging metadata.
 * - Permission failures should continue to map through the shared status helper
 *   so endpoint behavior stays uniform.
 */

/**
 * Organization discovery API surface.
 *
 * Responsibilities:
 * - Translate request input into service calls for org-related workflows.
 * - Convert service outcomes into HTTP status codes and response payloads.
 * - Serve both action endpoints and datagrid-oriented listing endpoints.
 */
final class OrganizationDiscoveryController
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
      || str_contains($message, 'only organization')
      || str_contains($message, 'only the current owner')
      || str_contains($message, 'managers cannot')
      || str_contains($message, 'cannot modify')
      || str_contains($message, 'cannot assign roles above')
      || str_contains($message, 'cannot promote')
      || str_contains($message, 'must be organization owner')
      || str_contains($message, 'transfer ownership before leaving')
      || str_contains($message, 'cannot be deleted or left')
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
   * POST organizations/create
   *
   * Creates a new organization owned by the current user.
   */
  #[Route('organizations/create', ['POST'])]
  /**
   * Handles create operation.
   */
  public function create(): void
  {
    $allowedStrings = ['name', 'organization_type'];
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $nameRaw = $filtered['name'] ?? '';
    $name = is_scalar($nameRaw) ? (string) $nameRaw : '';
    $organizationTypeRaw = $filtered['organization_type'] ?? 'shared';
    $organizationType = is_scalar($organizationTypeRaw) ? (string) $organizationTypeRaw : 'shared';

    $service = new OrganizationDiscoveryService();
    $result = $service->createOrganization(User::currentUUID(), $name, [
      'organization_type' => $organizationType,
    ]);

    if ($result['success']) {
      Response::success('[OrgC] Organization created.', $result['data'], HttpStatus::HTTP_CREATED);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET organizations
   *
   * Returns a flat list of all organizations the current user belongs to.
   */
  #[Route('organizations', ['GET'])]
  /**
   * Handles listForCurrentUser operation.
   */
  public function listForCurrentUser(): void
  {
    $service = new OrganizationDiscoveryService();
    $result = $service->listForUser(User::currentUUID());

    Response::success('[OrgC] Organizations retrieved.', $result['data'], HttpStatus::HTTP_OK);
  }

  /**
   * POST organizations/{organizationID}/notifications/read
   *
   * Marks organization notifications as read for the current user.
   */
  #[Route('organizations/{organizationID}/notifications/read', ['POST'])]
  /**
   * Handles markNotificationsRead operation.
   */
  public function markNotificationsRead(string $organizationID): void
  {
    $service = new OrganizationDiscoveryService();
    $orgId = InputSanitizer::sanitizeString($organizationID);
    $result = $service->markOrganizationNotificationsRead(User::currentUUID(), $orgId);

    if ($result['success']) {
      Response::success('[OrgC] Organization notifications marked read.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET organizations/lists
   *
   * Returns a DataGrid-formatted list of organizations for the current user.
   */
  #[Route('organizations/lists', ['GET'])]
  /**
   * Handles listGrid operation.
   */
  public function listGrid(): void
  {
    $service = new OrganizationDiscoveryService();
    $result = $service->listForUser(User::currentUUID());

    if (!$result['success']) {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));

      return;
    }

    $organizations = is_array($result['data']['organizations'] ?? null)
      ? $result['data']['organizations']
      : [];

    $allOrganizations = $organizations;
    $organizations = array_values(array_filter($organizations, static function (mixed $organization): bool {
      if (!is_array($organization)) {
        return false;
      }

      $type = isset($organization['organization_type']) && is_scalar($organization['organization_type'])
        ? strtolower((string) $organization['organization_type'])
        : 'shared';

      return $type !== 'personal';
    }));

    if ($organizations === [] && $allOrganizations !== []) {
      $organizations = $allOrganizations;
    }

    $search = trim((string) (InputSanitizer::getString('search') ?? ''));
    $sort = (string) (InputSanitizer::getString('sort') ?? 'name');
    $direction = strtolower((string) (InputSanitizer::getString('direction') ?? 'asc')) === 'desc' ? 'desc' : 'asc';
    $page = max(1, (int) (InputSanitizer::getString('page') ?? '1'));

    if ($search !== '') {
      $needle = mb_strtolower($search);
      $organizations = array_values(array_filter($organizations, static function (mixed $organization) use ($needle): bool {
        if (!is_array($organization)) {
          return false;
        }

        $haystacks = [
          isset($organization['name']) && is_scalar($organization['name']) ? (string) $organization['name'] : '',
          isset($organization['organization_type']) && is_scalar($organization['organization_type']) ? (string) $organization['organization_type'] : '',
          isset($organization['role']) && is_scalar($organization['role']) ? (string) $organization['role'] : '',
          isset($organization['status']) && is_scalar($organization['status']) ? (string) $organization['status'] : '',
        ];

        foreach ($haystacks as $haystack) {
          if (mb_stripos($haystack, $needle) !== false) {
            return true;
          }
        }

        return false;
      }));
    }

    $allowedSorts = ['name', 'organization_type', 'role', 'status'];
    if (!in_array($sort, $allowedSorts, true)) {
      $sort = 'name';
    }

    usort($organizations, static function (mixed $a, mixed $b) use ($sort, $direction): int {
      if (!is_array($a) || !is_array($b)) {
        return 0;
      }

      $aValue = isset($a[$sort]) && is_scalar($a[$sort]) ? (string) $a[$sort] : '';
      $bValue = isset($b[$sort]) && is_scalar($b[$sort]) ? (string) $b[$sort] : '';
      $comparison = strcasecmp($aValue, $bValue);

      return $direction === 'desc' ? -$comparison : $comparison;
    });

    $rows = array_map(static function (mixed $organization): array {
      if (!is_array($organization)) {
        return [
          'id' => '',
          'name' => '',
          'organization_type' => 'shared',
          'role' => '',
          'status' => '',
        ];
      }

      return [
        'id' => isset($organization['organization_id']) && is_scalar($organization['organization_id']) ? (string) $organization['organization_id'] : '',
        'name' => isset($organization['name']) && is_scalar($organization['name']) ? (string) $organization['name'] : '',
        'organization_type' => isset($organization['organization_type']) && is_scalar($organization['organization_type']) ? (string) $organization['organization_type'] : 'shared',
        'role' => isset($organization['role']) && is_scalar($organization['role']) ? (string) $organization['role'] : '',
        'status' => isset($organization['status']) && is_scalar($organization['status']) ? (string) $organization['status'] : '',
      ];
    }, $organizations);

    $grid = \PayCal\Domain\DataGrid::create('organizations', 'Organizations');
    $grid->enableSearch('Filter organizations…');
    $grid->enableSorting();
    $grid->addColumn('name', 'Name', true);
    $grid->addColumn('organization_type', 'Type', true);
    $grid->addColumn('role', 'Role', true);
    $grid->addColumn('status', 'Status', true);
    $i18n = [];
    foreach (['REMOVE'] as $key) {
      $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
    }
    $removeLabel = $i18n['REMOVE'];
    $grid->addRowAction('remove', $removeLabel);
    $grid->setItemLabel('organizations');

    $pager = \PayCal\Domain\ArrayPager::fromArray($rows, [
      'pageSize' => 25,
    ]);
    $pager->setPage($page);
    $html = $grid->table($pager);

    $start = $pager->getTotal() === 0 ? 0 : (($pager->getPage() - 1) * $pager->getPageSize()) + 1;
    $end = min($pager->getPage() * $pager->getPageSize(), $pager->getTotal());
    $total = $pager->getTotal();
    $html = str_replace(
      '<div class="datagrid" data-grid="organizations" data-page="' . $pager->getPage() . '">',
      '<div class="datagrid" data-grid="organizations" data-page="' . $pager->getPage() . '" data-search="' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '" data-sort="' . htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') . '" data-direction="' . htmlspecialchars($direction, ENT_QUOTES, 'UTF-8') . '" data-pagination-start="' . $start . '" data-pagination-end="' . $end . '" data-pagination-total="' . $total . '">',
      $html
    );

    Response::success('[OrgC] Organizations grid rendered.', [
      'html' => $html,
    ], HttpStatus::HTTP_OK);
  }

  /**
   * GET organizations/{organizationID}/members/grid
   *
   * Returns a DataGrid-formatted list of members for the specified organization.
   * Requires that the current user is a member of the organization.
   */
  #[Route('organizations/{organizationID}/members/grid', ['GET'])]
  /**
   * Handles listMembersGrid operation.
   */
  public function listMembersGrid(string $organizationID): void
  {
    $service = new OrganizationDiscoveryService();
    $orgId = InputSanitizer::sanitizeString($organizationID);
    $result = $service->listRelationships(User::currentUUID(), $orgId);

    if (!$result['success']) {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));

      return;
    }

    $members = is_array($result['data']['members'] ?? null)
      ? $result['data']['members']
      : [];

    $search = trim((string) (InputSanitizer::getString('search') ?? ''));
    $sort = (string) (InputSanitizer::getString('sort') ?? 'full_name');
    $direction = strtolower((string) (InputSanitizer::getString('direction') ?? 'asc')) === 'desc' ? 'desc' : 'asc';
    $page = max(1, (int) (InputSanitizer::getString('page') ?? '1'));
    $roleFilter = strtolower(trim((string) (InputSanitizer::getString('role') ?? '')));

    if ($roleFilter !== '') {
      $members = array_values(array_filter($members, static function (mixed $member) use ($roleFilter): bool {
        if (!is_array($member)) {
          return false;
        }

        $role = isset($member['role']) && is_scalar($member['role'])
          ? strtolower((string) $member['role'])
          : '';

        return $role === $roleFilter;
      }));
    }

    if ($search !== '') {
      $needle = mb_strtolower($search);
      $members = array_values(array_filter($members, static function (mixed $member) use ($needle): bool {
        if (!is_array($member)) {
          return false;
        }

        $haystacks = [
          isset($member['full_name']) && is_scalar($member['full_name']) ? (string) $member['full_name'] : '',
          isset($member['email']) && is_scalar($member['email']) ? (string) $member['email'] : '',
          isset($member['role']) && is_scalar($member['role']) ? (string) $member['role'] : '',
          isset($member['status']) && is_scalar($member['status']) ? (string) $member['status'] : '',
          isset($member['joined_at']) && is_scalar($member['joined_at']) ? (string) $member['joined_at'] : '',
        ];

        foreach ($haystacks as $haystack) {
          if (mb_stripos($haystack, $needle) !== false) {
            return true;
          }
        }

        return false;
      }));
    }

    $allowedSorts = ['full_name', 'email', 'role', 'status', 'joined_at'];
    if (!in_array($sort, $allowedSorts, true)) {
      $sort = 'full_name';
    }

    usort($members, static function (mixed $a, mixed $b) use ($sort, $direction): int {
      if (!is_array($a) || !is_array($b)) {
        return 0;
      }

      $aValue = isset($a[$sort]) && is_scalar($a[$sort]) ? (string) $a[$sort] : '';
      $bValue = isset($b[$sort]) && is_scalar($b[$sort]) ? (string) $b[$sort] : '';
      $comparison = strcasecmp($aValue, $bValue);

      return $direction === 'desc' ? -$comparison : $comparison;
    });

    $rows = array_map(static function (mixed $member): array {
      if (!is_array($member)) {
        return [
          'id' => '',
          'full_name' => '',
          'email' => '',
          'role' => '',
          'status' => '',
          'joined_at' => '',
        ];
      }

      $joinedAt = '';
      foreach (['owner_since', 'accepted_at', 'created_at', 'updated_at'] as $field) {
        if (isset($member[$field]) && is_scalar($member[$field])) {
          $candidate = trim((string) $member[$field]);
          if ($candidate !== '') {
            $joinedAt = $candidate;
            break;
          }
        }
      }

      return [
        'id' => isset($member['user_uuid']) && is_scalar($member['user_uuid'])
          ? (string) $member['user_uuid']
          : (isset($member['uuid']) && is_scalar($member['uuid']) ? (string) $member['uuid'] : ''),
        'full_name' => isset($member['full_name']) && is_scalar($member['full_name']) ? (string) $member['full_name'] : '',
        'email' => isset($member['email']) && is_scalar($member['email']) ? (string) $member['email'] : '',
        'role' => isset($member['role']) && is_scalar($member['role']) ? (string) $member['role'] : '',
        'status' => isset($member['status']) && is_scalar($member['status']) ? (string) $member['status'] : '',
        'joined_at' => $joinedAt,
      ];
    }, $members);

    $grid = DataGrid::create('organization-members', 'Organization Members');
    $grid->enableSearch('Filter members...');
    $grid->enableSorting();
    $grid->addColumn('full_name', 'Name', true);
    $grid->addColumn('email', 'Email', true);
    $grid->addColumn('role', 'Role', true);
    $grid->addColumn('status', 'Status', true);
    $grid->addColumn('joined_at', 'Joined', true);
    $grid->addRowAction('change-role', 'Change Role');
    $grid->addRowAction('revoke', 'Revoke');
    $grid->setItemLabel('members');

    $pager = ArrayPager::fromArray($rows, [
      'pageSize' => 25,
    ]);
    $pager->setPage($page);
    $html = $grid->table($pager);

    $start = $pager->getTotal() === 0 ? 0 : (($pager->getPage() - 1) * $pager->getPageSize()) + 1;
    $end = min($pager->getPage() * $pager->getPageSize(), $pager->getTotal());
    $total = $pager->getTotal();

    $searchAttr = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
    $sortAttr = htmlspecialchars($sort, ENT_QUOTES, 'UTF-8');
    $directionAttr = htmlspecialchars($direction, ENT_QUOTES, 'UTF-8');
    $pattern = '/(<div\\s+id="organization-members"[^>]*class="datagrid"[^>]*data-grid="organization-members"[^>]*)>/';
    $replacement = '$1 data-search="' . $searchAttr . '" data-sort="' . $sortAttr . '" data-direction="' . $directionAttr . '" data-pagination-start="' . $start . '" data-pagination-end="' . $end . '" data-pagination-total="' . $total . '">';
    $html = (string) preg_replace($pattern, $replacement, $html, 1);

    Response::success('[OrgC] Members grid rendered.', [
      'html' => $html,
    ], HttpStatus::HTTP_OK);
  }

  /**
   * POST organizations/{organizationID}/invites/send
   *
   * Sends an invitation email to a target email address for the specified organization.
   * Caller must be an owner or manager of the organization.
   */
  #[Route('organizations/{organizationID}/invites/send', ['POST'])]
  /**
   * Handles sendInvite operation.
   */
  public function sendInvite(string $organizationID): void
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

    $service = new OrganizationDiscoveryService();
    $result = $service->sendInvite(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $email, $scopes);

    if ($result['success']) {
      Response::success('[OrgC] Invite created.', $result['data'], HttpStatus::HTTP_CREATED);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST organizations/{organizationID}/invites/import/prepare
   *
   * Validates a batch of email addresses and returns a prepared import set
   * with per-email results (valid, duplicate, already member).
   */
  #[Route('organizations/{organizationID}/invites/import/prepare', ['POST'])]
  /**
   * Handles prepareInviteImport operation.
   */
  public function prepareInviteImport(string $organizationID): void
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

    $service = new OrganizationDiscoveryService();
    $result = $service->prepareBulkInviteImport(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $emails, $scopes);

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
   * POST organizations/{organizationID}/invites/import/challenge/start
   *
   * Issues a short-lived CSRF-style challenge token for the bulk invite import
   * to protect against replay attacks before the commit step.
   */
  #[Route('organizations/{organizationID}/invites/import/challenge/start', ['POST'])]
  /**
   * Handles startInviteImportChallenge operation.
   */
  public function startInviteImportChallenge(string $organizationID): void
  {
    $allowedStrings = ['import_id'];
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $importIdRaw = $filtered['import_id'] ?? '';
    $importId = is_scalar($importIdRaw) ? (string) $importIdRaw : '';

    $service = new OrganizationDiscoveryService();
    $result = $service->startBulkInviteImportChallenge(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $importId);

    if ($result['success']) {
      Response::success('[OrgC] Invite import challenge started.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST organizations/{organizationID}/invites/import/challenge/verify
   *
   * Verifies the challenge token issued by startInviteImportChallenge and marks
   * the import as ready to commit.
   */
  #[Route('organizations/{organizationID}/invites/import/challenge/verify', ['POST'])]
  /**
   * Handles verifyInviteImportChallenge operation.
   */
  public function verifyInviteImportChallenge(string $organizationID): void
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

    $service = new OrganizationDiscoveryService();
    $result = $service->verifyBulkInviteImportChallenge(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $importId, $challengeId, $code);

    if ($result['success']) {
      Response::success('[OrgC] Invite import challenge verified.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST organizations/{organizationID}/invites/import/commit
   *
   * Commits the verified bulk invite import, sending invitation emails to all
   * validated addresses.
   */
  #[Route('organizations/{organizationID}/invites/import/commit', ['POST'])]
  /**
   * Handles commitInviteImport operation.
   */
  public function commitInviteImport(string $organizationID): void
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

    $service = new OrganizationDiscoveryService();
    $result = $service->commitBulkInviteImport(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $importId, $challengeId);

    if ($result['success']) {
      Response::success('[OrgC] Invite import committed.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * GET organizations/{organizationID}/invites
   *
   * Returns a list of pending invitations for the specified organization.
   */
  #[Route('organizations/{organizationID}/invites', ['GET'])]
  /**
   * Handles listInvites operation.
   */
  public function listInvites(string $organizationID): void
  {
    $service = new OrganizationDiscoveryService();
    $result = $service->listInvites(User::currentUUID(), InputSanitizer::sanitizeString($organizationID));

    if ($result['success']) {
      Response::success('[OrgC] Invites retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * GET organizations/{organizationID}/invites/history
   *
   * Returns the full invitation history (accepted, declined, revoked) for the
   * specified organization.
   */
  #[Route('organizations/{organizationID}/invites/history', ['GET'])]
  /**
   * Handles listInviteHistory operation.
   */
  public function listInviteHistory(string $organizationID): void
  {
    $service = new OrganizationDiscoveryService();
    $result = $service->listInviteHistory(User::currentUUID(), InputSanitizer::sanitizeString($organizationID));

    if ($result['success']) {
      Response::success('[OrgC] Invite history retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * GET organizations/{organizationID}/access/requests
   *
   * Returns a list of pending membership access requests for the organization.
   * Caller must be an owner or manager.
   */
  #[Route('organizations/{organizationID}/access/requests', ['GET'])]
  /**
   * Handles listAccessRequests operation.
   */
  public function listAccessRequests(string $organizationID): void
  {
    $service = new OrganizationDiscoveryService();
    $result = $service->listAccessRequests(User::currentUUID(), InputSanitizer::sanitizeString($organizationID));

    if ($result['success']) {
      Response::success('[OrgC] Access requests retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST organizations/{organizationID}/access/requests/approve
   *
   * Approves a pending membership access request and adds the requester as a member.
   */
  #[Route('organizations/{organizationID}/access/requests/approve', ['POST'])]
  /**
   * Handles approveAccessRequest operation.
   */
  public function approveAccessRequest(string $organizationID): void
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

    $service = new OrganizationDiscoveryService();
    $result = $service->approveAccessRequest(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $requestId, $consentContext);

    if ($result['success']) {
      Response::success('[OrgC] Access request approved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST organizations/{organizationID}/access/requests/reject
   *
   * Rejects a pending membership access request.
   */
  #[Route('organizations/{organizationID}/access/requests/reject', ['POST'])]
  /**
   * Handles rejectAccessRequest operation.
   */
  public function rejectAccessRequest(string $organizationID): void
  {
    $allowedStrings = ['request_id'];
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $requestIdRaw = $filtered['request_id'] ?? '';
    $requestId = is_scalar($requestIdRaw) ? InputSanitizer::sanitizeString((string) $requestIdRaw) : '';

    $service = new OrganizationDiscoveryService();
    $result = $service->rejectAccessRequest(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $requestId);

    if ($result['success']) {
      Response::success('[OrgC] Access request rejected.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST organizations/{organizationID}/invites/revoke
   *
   * Revokes a pending invitation so it can no longer be accepted.
   */
  #[Route('organizations/{organizationID}/invites/revoke', ['POST'])]
  /**
   * Handles revokeInvite operation.
   */
  public function revokeInvite(string $organizationID): void
  {
    $allowedStrings = ['invite_id'];
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $inviteIdRaw = $filtered['invite_id'] ?? '';
    $inviteID = is_scalar($inviteIdRaw) ? InputSanitizer::sanitizeString((string) $inviteIdRaw) : '';

    $service = new OrganizationDiscoveryService();
    $result = $service->revokeInvite(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $inviteID);

    if ($result['success']) {
      Response::success('[OrgC] Invite revoked.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST organizations/invites/accept
   *
   * Accepts a pending invitation (identified by token) and creates a membership
   * relationship between the current user and the inviting organization.
   */
  #[Route('organizations/invites/accept', ['POST'])]
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

    $service = new OrganizationDiscoveryService();
    $result = $service->acceptInvite($token, User::currentUUID(), $consentContext);

    if ($result['success']) {
      Response::success('[OrgC] Invite accepted.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST organizations/{organizationID}/membership/accept
   *
   * Captures mandatory consent and processes membership acceptance.
   * Supports invite acceptance and consent capture for pending access requests.
   */
  #[Route('organizations/{organizationID}/membership/accept', ['POST'])]
  /**
   * Handles acceptMembership operation.
   */
  public function acceptMembership(string $organizationID): void
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

    $service = new OrganizationDiscoveryService();
    $result = $service->acceptMembershipWithConsent(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $payload);

    if ($result['success']) {
      Response::success('[OrgC] Membership consent flow completed.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST organizations/{organizationID}/relationships/revoke
   *
   * Permanently removes a member from the organization (owner/manager only).
   */
  #[Route('organizations/{organizationID}/relationships/revoke', ['POST'])]
  /**
   * Handles revokeRelationship operation.
   */
  public function revokeRelationship(string $organizationID): void
  {
    $allowedStrings = ['target_user_uuid'];
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $targetRaw = $filtered['target_user_uuid'] ?? '';
    $targetUUID = is_scalar($targetRaw) ? InputSanitizer::sanitizeString((string) $targetRaw) : '';

    $service = new OrganizationDiscoveryService();
    $result = $service->revokeRelationship(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $targetUUID);

    if ($result['success']) {
      Response::success('[OrgC] Relationship revoked.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST organizations/{organizationID}/relationships/update-role
   *
   * Updates the role of an existing member (e.g. member → manager).
   * Owner cannot change their own role.
   */
  #[Route('organizations/{organizationID}/relationships/update-role', ['POST'])]
  /**
   * Handles updateRelationshipRole operation.
   */
  public function updateRelationshipRole(string $organizationID): void
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

    $service = new OrganizationDiscoveryService();
    $result = $service->updateRelationshipRole(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $targetUUID, $role);

    if ($result['success']) {
      Response::success('[OrgC] Relationship role updated.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST organizations/{organizationID}/leave
   *
   * Removes the current user from the specified organization.
   * The organization owner cannot leave without transferring ownership first.
   */
  #[Route('organizations/{organizationID}/leave', ['POST'])]
  /**
   * Handles leaveOrganization operation.
   */
  public function leaveOrganization(string $organizationID): void
  {
    $service = new OrganizationDiscoveryService();
    $result = $service->leaveOrganization(User::currentUUID(), InputSanitizer::sanitizeString($organizationID));

    if ($result['success']) {
      Response::success('[OrgC] Organization relationship withdrawn.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST organizations/{organizationID}/sites/link
   *
   * Links one of the current user's sites to the specified organization
   * so organisation members can see shared work data.
   */
  #[Route('organizations/{organizationID}/sites/link', ['POST'])]
  /**
   * Handles linkSite operation.
   */
  public function linkSite(string $organizationID): void
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

    $service = new OrganizationDiscoveryService();
    $result = $service->linkSite(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $siteOwner, $siteId);

    if ($result['success']) {
      Response::success('[OrgC] Site linked.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET organizations/{organizationID}/settings
   *
   * Returns the current settings/metadata for the specified organization.
   */
  #[Route('organizations/{organizationID}/settings', ['GET'])]
  /**
   * Handles getSettings operation.
   */
  public function getSettings(string $organizationID): void
  {
    $service = new OrganizationDiscoveryService();
    $result = $service->getOrganizationSettings(User::currentUUID(), InputSanitizer::sanitizeString($organizationID));

    if ($result['success']) {
      Response::success('[OrgC] Organization settings retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST organizations/{organizationID}/settings/update
   *
   * Updates modifiable settings for the specified organization (name, type, etc.).
   * Caller must be the owner.
   */
  #[Route('organizations/{organizationID}/settings/update', ['POST'])]
  /**
   * Handles updateSettings operation.
   */
  public function updateSettings(string $organizationID): void
  {
    $allowedStrings = [
      'name',
      'organization_type',
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

    $service = new OrganizationDiscoveryService();
    $result = $service->updateOrganizationSettings(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $filtered);

    if ($result['success']) {
      Response::success('[OrgC] Organization settings updated.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST organizations/access/request
   *
   * Submits a membership access request from the current user to a specified
   * organization whose discovery is enabled.
   */
  #[Route('organizations/access/request', ['POST'])]
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

    $service = new OrganizationDiscoveryService();
    $result = $service->requestAccessByOwnerEmail(User::currentUUID(), $ownerEmail);

    if ($result['success']) {
      Response::success('[OrgC] Access request submitted.', $result['data'], HttpStatus::HTTP_CREATED);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET organizations/access/search
   *
   * Searches for discoverable organizations matching a query string.
   * Returns name, type, and membership request status.
   */
  #[Route('organizations/access/search', ['GET'])]
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
      $currentUUID = User::currentUUID();
      $candidates = [];

      foreach (Database::scanKeys(Keys::ORGANIZATION . ':*') as $organizationKey) {
        $organization = Database::hgetall($organizationKey);
        if ($organization === []) {
          continue;
        }

        $organizationName = trim((string) ($organization['name'] ?? ''));
        if ($organizationName === '') {
          continue;
        }

        $status = strtolower(trim((string) ($organization['status'] ?? 'active')));
        if ($status !== '' && $status !== 'active' && $status !== 'pending') {
          continue;
        }

        $ownerUUID = trim((string) ($organization['owner_uuid'] ?? ''));
        if ($ownerUUID === '' || $ownerUUID === $currentUUID) {
          continue;
        }

        $owner = UserRepository::getByUUID($ownerUUID);
        if (!$owner instanceof \PayCal\Domain\User) {
          continue;
        }

        $ownerEmail = InputSanitizer::sanitizeEmail((string) ($owner->email ?? ''));
        if ($ownerEmail === '') {
          continue;
        }

        $organizationId = str_replace(Keys::ORGANIZATION . ':', '', $organizationKey);
        $settings = $organizationId !== ''
          ? Database::hgetall(Keys::ORGANIZATION_SETTINGS . ':' . $organizationId)
          : [];

        $createdAt = trim((string) ($organization['created_at'] ?? $organization['updated_at'] ?? ''));
        $candidates[] = [
          'created_at' => $createdAt,
          'source' => 'organization',
          'email' => $ownerEmail,
          'name' => trim((string) ($owner->full_name ?? '')),
          'organization_name' => $organizationName,
          'public_profile' => $this->buildPublicOrganizationProfile($settings),
        ];
      }

      usort($candidates, static function (array $a, array $b): int {
        return strcmp((string) $b['created_at'], (string) $a['created_at']);
      });

      $suggestions = [];
      foreach ($candidates as $candidate) {
        unset($candidate['created_at']);
        $suggestions[] = $candidate;
        if (count($suggestions) >= $limit) {
          break;
        }
      }

      Response::success('[OrgC] Access lookup latest results generated.', [
        'suggestions' => $suggestions,
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
    $needle = mb_strtolower($query);
    $maxResults = $limit;
    $seenEmails = [];
    $suggestions = [];

    foreach (Database::scanKeys(Keys::USER . ':*') as $userKey) {
      $userUUID = str_replace(Keys::USER . ':', '', $userKey);
      if ($userUUID === '' || $userUUID === $currentUUID) {
        continue;
      }

      $userData = Database::hgetall($userKey);
      if ($userData === []) {
        continue;
      }

      $email = InputSanitizer::sanitizeEmail((string) ($userData['email'] ?? ''));
      $name = trim((string) ($userData['full_name'] ?? ''));
      if ($email === '') {
        continue;
      }

      if (mb_stripos($email, $needle) === false && ($name === '' || mb_stripos($name, $needle) === false)) {
        continue;
      }

      if (isset($seenEmails[$email])) {
        continue;
      }

      $seenEmails[$email] = true;
      $suggestions[] = [
        'source' => 'user',
        'email' => $email,
        'name' => $name,
        'organization_name' => '',
      ];

      if (count($suggestions) >= $maxResults) {
        Response::success('[OrgC] Access lookup results generated.', [
          'suggestions' => $suggestions,
        ], HttpStatus::HTTP_OK);

        return;
      }
    }

    foreach (Database::scanKeys(Keys::ORGANIZATION . ':*') as $organizationKey) {
      $organization = Database::hgetall($organizationKey);
      if ($organization === []) {
        continue;
      }

      $organizationName = trim((string) ($organization['name'] ?? ''));
      if ($organizationName === '' || mb_stripos($organizationName, $needle) === false) {
        continue;
      }

      $status = strtolower(trim((string) ($organization['status'] ?? 'active')));
      if ($status !== '' && $status !== 'active' && $status !== 'pending') {
        continue;
      }

      $ownerUUID = trim((string) ($organization['owner_uuid'] ?? ''));
      if ($ownerUUID === '' || $ownerUUID === $currentUUID) {
        continue;
      }

      $owner = UserRepository::getByUUID($ownerUUID);
      if (!$owner instanceof \PayCal\Domain\User) {
        continue;
      }

      $ownerEmail = InputSanitizer::sanitizeEmail((string) ($owner->email ?? ''));
      if ($ownerEmail === '' || isset($seenEmails[$ownerEmail])) {
        continue;
      }

      $seenEmails[$ownerEmail] = true;
      $organizationId = str_replace(Keys::ORGANIZATION . ':', '', $organizationKey);
      $settings = $organizationId !== ''
        ? Database::hgetall(Keys::ORGANIZATION_SETTINGS . ':' . $organizationId)
        : [];
      $publicProfile = $this->buildPublicOrganizationProfile($settings);
      $suggestions[] = [
        'source' => 'organization',
        'email' => $ownerEmail,
        'name' => trim((string) ($owner->full_name ?? '')),
        'organization_name' => $organizationName,
        'public_profile' => $publicProfile,
      ];

      if (count($suggestions) >= $maxResults) {
        break;
      }
    }

    Response::success('[OrgC] Access lookup results generated.', [
      'suggestions' => $suggestions,
    ], HttpStatus::HTTP_OK);
  }

  /**
   * POST organizations/{organizationID}/ownership/transfer
   *
   * Transfers ownership of the specified organization to another member.
   * Only the current owner may invoke this endpoint.
   */
  #[Route('organizations/{organizationID}/ownership/transfer', ['POST'])]
  /**
   * Handles transferOwnership operation.
   */
  public function transferOwnership(string $organizationID): void
  {
    $allowedStrings = ['target_user_uuid'];
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $targetRaw = $filtered['target_user_uuid'] ?? '';
    $targetUUID = is_scalar($targetRaw) ? InputSanitizer::sanitizeString((string) $targetRaw) : '';

    $service = new OrganizationDiscoveryService();
    $result = $service->transferOwnership(User::currentUUID(), InputSanitizer::sanitizeString($organizationID), $targetUUID);

    if ($result['success']) {
      Response::success('[OrgC] Ownership transferred.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * POST organizations/{organizationID}/encryption/bootstrap
   *
   * Bootstraps org DEK wraps for all active organization members.
   */
  #[Route('organizations/{organizationID}/encryption/bootstrap', ['POST'])]
  /**
   * Bootstrap organization encryption for active members.
   */
  public function bootstrapOrganizationEncryption(string $organizationID): void
  {
    $allowedStrings = ['segment', 'version'];
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[OrgC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $segmentRaw = $filtered['segment'] ?? OrganizationDiscoveryService::ORG_DEK_SEGMENT_CURRENT_PERIOD;
    $versionRaw = $filtered['version'] ?? '1';

    $segment = is_scalar($segmentRaw)
      ? InputSanitizer::sanitizeString((string) $segmentRaw)
      : OrganizationDiscoveryService::ORG_DEK_SEGMENT_CURRENT_PERIOD;
    $version = is_scalar($versionRaw)
      ? InputSanitizer::sanitizeString((string) $versionRaw)
      : '1';

    if ($segment === '') {
      $segment = OrganizationDiscoveryService::ORG_DEK_SEGMENT_CURRENT_PERIOD;
    }
    if ($version === '') {
      $version = '1';
    }

    $service = new OrganizationDiscoveryService();
    $result = $service->bootstrapOrgDekForAllMembers(
      User::currentUUID(),
      InputSanitizer::sanitizeString($organizationID),
      $segment,
      $version
    );

    if ($result['success']) {
      Response::success('[OrgC] Organization DEK bootstrap completed.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST organizations/encryption/auto-bootstrap
   *
   * Lightweight page-visit runner that opportunistically bootstraps org DEKs.
   */
  #[Route('organizations/encryption/auto-bootstrap', ['POST'])]
  /**
   * Evaluate opportunistic organization encryption bootstrap on page visit.
   */
  public function autoBootstrapOrganizationEncryption(): void
  {
    $actorUUID = User::currentUUID();
    $actorThrottleKey = Keys::TELEMETRY . ':org:dek:auto_bootstrap:user:' . $actorUUID;
    if (Database::exists($actorThrottleKey)) {
      Response::success('[OrgC] Auto bootstrap skipped (throttled).', [
        'throttled' => true,
      ], HttpStatus::HTTP_OK);

      return;
    }

    Database::set($actorThrottleKey, '1', 120);

    $service = new OrganizationDiscoveryService();
    $result = $service->autoBootstrapOrgDekOnPageVisit($actorUUID);

    if ($result['success']) {
      Response::success('[OrgC] Auto bootstrap evaluated.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET organizations/{organizationID}/relationships
   *
   * Returns all active membership relationships for the specified organization.
   */
  #[Route('organizations/{organizationID}/relationships', ['GET'])]
  /**
   * Handles listRelationships operation.
   */
  public function listRelationships(string $organizationID): void
  {
    $service = new OrganizationDiscoveryService();
    $result = $service->listRelationships(User::currentUUID(), InputSanitizer::sanitizeString($organizationID));

    if ($result['success']) {
      Response::success('[OrgC] Relationships retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], self::serviceFailureHttpStatus($result));
    }
  }

  /**
   * GET organizations/{organizationID}/audit
   *
   * Returns a flat chronological audit log for the specified organization.
   */
  #[Route('organizations/{organizationID}/audit', ['GET'])]
  /**
   * Handles listAuditTimeline operation.
   */
  public function listAuditTimeline(string $organizationID): void
  {
    $service = new OrganizationDiscoveryService();
    $result = $service->listAuditTimeline(User::currentUUID(), InputSanitizer::sanitizeString($organizationID));

    if ($result['success']) {
      Response::success('[OrgC] Audit timeline retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET/POST organizations/{organizationID}/audit/grid
   *
   * Returns a DataGrid-formatted audit-event timeline for the specified organization.
   * Supports server-side paging via POST body parameters.
   */
  #[Route('organizations/{organizationID}/audit/grid', ['GET', 'POST'])]
  /**
   * Handles listAuditTimelineGrid operation.
   */
  public function listAuditTimelineGrid(string $organizationID): void
  {
    $service = new OrganizationDiscoveryService();
    $orgId = InputSanitizer::sanitizeString($organizationID);
    $result = $service->listAuditTimeline(User::currentUUID(), $orgId);

    if (!$result['success']) {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $events = is_array($result['data']['events'] ?? null)
      ? $result['data']['events']
      : [];

    $this->renderAuditGridResponse($events, 'organizations-audit-grid', 'Organization Audit Timeline', $orgId);
  }

  /**
   * GET organizations/{organizationID}/audit/member
   *
   * Returns the audit timeline scoped to a specific member of the organization.
   */
  #[Route('organizations/{organizationID}/audit/member', ['GET'])]
  /**
   * Handles listMemberAuditTimeline operation.
   */
  public function listMemberAuditTimeline(string $organizationID): void
  {
    $service = new OrganizationDiscoveryService();
    $result = $service->listAuditTimelineForMember(User::currentUUID(), InputSanitizer::sanitizeString($organizationID));

    if ($result['success']) {
      Response::success('[OrgC] Member audit timeline retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * GET/POST organizations/{organizationID}/audit/member/grid
   *
   * Returns a DataGrid-formatted audit-event timeline scoped to a specific member.
   * Supports server-side paging via POST body parameters.
   */
  #[Route('organizations/{organizationID}/audit/member/grid', ['GET', 'POST'])]
  /**
   * Handles listMemberAuditTimelineGrid operation.
   */
  public function listMemberAuditTimelineGrid(string $organizationID): void
  {
    $service = new OrganizationDiscoveryService();
    $orgId = InputSanitizer::sanitizeString($organizationID);
    $result = $service->listAuditTimelineForMember(User::currentUUID(), $orgId);

    if (!$result['success']) {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $events = is_array($result['data']['events'] ?? null)
      ? $result['data']['events']
      : [];

    $this->renderAuditGridResponse($events, 'organizations-free-audit-grid', 'My Organization Audit Timeline', $orgId);
  }

  /** @param array<int, mixed> $events */
  private function renderAuditGridResponse(array $events, string $gridId, string $title, string $orgId = ''): void
  {
    $orgName = '';
    $orgOwnerEmail = '';
    if ($orgId !== '') {
      $org = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
      $orgName = trim((string) ($org['name'] ?? ''));
      $ownerUUID = trim((string) ($org['owner_uuid'] ?? ''));
      if ($ownerUUID !== '') {
        $ownerUser = UserRepository::getByUUID($ownerUUID);
        if ($ownerUser !== null) {
          $orgOwnerEmail = trim((string) ($ownerUser->email ?? ''));
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
        $fullName = trim((string) ($actor->full_name ?? ''));
        $email = trim((string) ($actor->email ?? ''));
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
      if (($enrichedDetails['organization_name'] ?? '') === '' && $orgName !== '') {
        $enrichedDetails['organization_name'] = $orgName;
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
    $eventDetailsStore = '<div id="' . htmlspecialchars($gridId, ENT_QUOTES, 'UTF-8') . '_event_details" class="organizations_audit_event_details_store" hidden aria-hidden="true" data-event-details-json="' . htmlspecialchars($eventDetailsJson, ENT_QUOTES, 'UTF-8') . '"></div>';

    $html = $html . $eventDetailsStore;

    Response::success('[OrgC] Audit grid rendered.', [
      'html' => $html,
    ], HttpStatus::HTTP_OK);
  }

  /**
   * GET/POST organizations/{organizationID}/invites/history/grid
   *
   * Returns a DataGrid-formatted invite history for the specified organization.
   * Supports server-side paging via POST body parameters.
   */
  #[Route('organizations/{organizationID}/invites/history/grid', ['GET', 'POST'])]
  /**
   * Handles listInviteHistoryGrid operation.
   */
  public function listInviteHistoryGrid(string $organizationID): void
  {
    $service = new OrganizationDiscoveryService();
    $orgId = InputSanitizer::sanitizeString($organizationID);
    $result = $service->listInviteHistory(User::currentUUID(), $orgId);

    if (!$result['success']) {
      Response::error('[OrgC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $accessHistoryResult = $service->listAccessRequestHistory(User::currentUUID(), $orgId);
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

    $grid = DataGrid::create('organizations-invite-history-grid', 'Invite & Access Request History');
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
    $pattern = '/(<div\\s+id="organizations-invite-history-grid"[^>]*class="datagrid"[^>]*data-grid="organizations-invite-history-grid"[^>]*)>/';
    $replacement = '$1 data-search="' . $searchAttr . '" data-sort="' . $sortAttr . '" data-direction="' . $directionAttr . '" data-pagination-start="' . $start . '" data-pagination-end="' . $end . '" data-pagination-total="' . $total . '">';
    $html = (string) preg_replace($pattern, $replacement, $html, 1);

    Response::success('[OrgC] Invite history grid rendered.', [
      'html' => $html,
    ], HttpStatus::HTTP_OK);
  }

  /**
   * GET organizations/discovery
   *
   * Returns metadata about organization discovery settings for the current user.
   */
  #[Route('organizations/discovery', ['GET'])]
  /**
   * Handles discovery operation.
   */
  public function discovery(): void
  {
    $service = new OrganizationDiscoveryService();
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
    foreach (['organization_name', 'owner_email'] as $key) {
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
  private function buildPublicOrganizationProfile(array $settings): array
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
}


