<?php declare(strict_types=1);

namespace PayCal\Controllers;

use IntlDateFormatter;
use Throwable;
use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\Calendar;
use PayCal\Domain\CalendarFields;
use PayCal\Domain\Database;
use PayCal\Domain\EmailVerificationGuard;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Log;
use PayCal\Domain\OrganizationDiscoveryService;
use PayCal\Domain\PayPeriodGenerator;
use PayCal\Domain\Render;
use PayCal\Domain\RequestGuard;
use PayCal\Domain\Response;
use PayCal\Domain\Security\CorrelationBroker;
use PayCal\Domain\Security\CorrelationContext;
use PayCal\Domain\Enums\SessionTimeout;
use PayCal\Domain\Strings;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\SystemLimits;
use PayCal\Domain\User;
use PayCal\Domain\UserRepository;
use PayCal\Domain\Work;
use PayCal\Domain\WorkEntry;
use PayCal\Domain\WorkEntryRepository;
use PayCal\Domain\WorkEntryLockService;
use PayCal\Observability\Lens;

/**
 * CalendarController.php
 *
 * Purpose: HTTP orchestration layer for calendar reads, writes, deletes, and
 * month-grid payload generation.
 *
 * Developer notes:
 * - This controller is request glue, not the authority for work-entry schema.
 * - Historical locking must be enforced before any mutation path persists data.
 * - Delegated organization writes must emit audit and denial telemetry.
 * - Correlation of financial/site metadata must remain behind broker checks.
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
 * Calendar API surface.
 *
 * Responsibilities:
 * - Validate request context and authenticated access.
 * - Assemble calendar payloads for day, week, and month views.
 * - Coordinate encrypted work-entry mutations and destructive deletes.
 * - Keep reusable policy logic in domain services instead of the controller.
 */

class CalendarController
{
  private const MAX_AUTOREPAIR_DATES = SystemLimits::MAX_USER_RESULTS;
  private const WEEK_START_SUNDAY = 0;
  private const GRID_SPAN_DAYS = 41;
  private const MIN_MONTH = 1;
  private const MAX_MONTH = 12;
  private const STATUS_ENTRY_LOCKED = 'ENTRY_LOCKED';

  /**
   * Handles scalarString operation.
   */
  private static function scalarString(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * Handles numericFloat operation.
   */
  private static function numericFloat(mixed $value, float $default = 0.0): float
  {
    return is_numeric($value) ? (float) $value : $default;
  }

  /**
   * Resolve which user's calendar may be viewed by the actor.
   * Returns null when a requested delegated user is not permitted.
   */
  private function resolveCalendarSubjectUser(User $actor): ?User
  {
    $actorUUID = self::scalarString($actor->user_uuid);
    if ($actorUUID === '') {
      return $actor;
    }

    $requestedUUIDRaw = InputSanitizer::getString('user_uuid');
    $requestedUUID = is_string($requestedUUIDRaw) ? trim($requestedUUIDRaw) : '';
    if ($requestedUUID === '' || $requestedUUID === $actorUUID) {
      return $actor;
    }

    $target = UserRepository::getByUUID($requestedUUID);
    if ($target === null) {
      return null;
    }

    if (User::isAdmin()) {
      return $target;
    }

    foreach (Database::smembers(Keys::ORGANIZATION_USER . ':' . $actorUUID) as $orgIdRaw) {
      $orgId = self::scalarString($orgIdRaw);
      if ($orgId === '') {
        continue;
      }

      $org = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
      if (empty($org)) {
        continue;
      }

      $ownerUUID = self::scalarString($org['owner_uuid'] ?? '');
      $actorRel = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $actorUUID);
      $actorRole = strtolower(self::scalarString($actorRel['role'] ?? ''));
      $actorStatus = self::scalarString($actorRel['status'] ?? '');
      $isOwner = $ownerUUID !== '' && $ownerUUID === $actorUUID;
      $isManager = $actorStatus === OrganizationDiscoveryService::MEMBERSHIP_STATE_ACTIVE && $actorRole === 'coordinator';
      if (!$isOwner && !$isManager) {
        continue;
      }

      if ($requestedUUID === $ownerUUID) {
        return $target;
      }

      $targetRel = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $requestedUUID);
      $targetStatus = self::scalarString($targetRel['status'] ?? '');
      if ($targetStatus === OrganizationDiscoveryService::MEMBERSHIP_STATE_ACTIVE) {
        return $target;
      }
    }

    return null;
  }

  /**
   * Handles correlationContext operation.
   */
  private static function correlationContext(): string
  {
    $raw = InputSanitizer::sanitizeString(InputSanitizer::getString('correlation_context'));
    return $raw === '' ? 'self-service-calendar' : strtolower($raw);
  }

  /**
   * Handles entryLockedMessage operation.
   */
  private static function entryLockedMessage(): string
  {
    static $entryLockedMessage = null;

    if (!is_string($entryLockedMessage)) {
      $entryLockedMessage = Strings::i18n(self::STATUS_ENTRY_LOCKED);
    }

    return $entryLockedMessage;
  }

  /**
   * Handles canCorrelateSiteMetadataWithFinancialPayload operation.
   */
  private static function canCorrelateSiteMetadataWithFinancialPayload(): bool
  {
    $probe = self::siteFinancialCorrelationComposeProbe();
    return ($probe['status'] ?? '') === 'success';
  }

  /** @return array<string, mixed> */
  private static function siteFinancialCorrelationComposeProbe(): array
  {
    $context = new CorrelationContext(
      self::correlationContext(),
      User::currentUUID(),
      User::isAdmin() ? 'security-admin' : 'user',
      'calendar-self-service',
      ['site_metadata:financial_payload'],
      'calendar_controller'
    );

    return CorrelationBroker::compose(
      ['scope' => 'site_metadata'],
      ['scope' => 'financial_payload'],
      'site_metadata',
      'financial_payload',
      $context
    );
  }

  /**
   * Constructor. Aborts with 401 if the request is not authenticated.
   */
  public function __construct()
  {
    Authentication::abortIfUnauthenticated();
  }

  /**
   * GET calendar/nonce
   *
   * Generates and returns a short-lived CSRF nonce for calendar form submissions.
   */
  #[Route('calendar/nonce', ['GET'])]
  /**
   * Handles getNonce operation.
   */
  public function getNonce(): void
  {
    // session_start() removed; session is no longer used

    $user = User::current();

    // Generate a proper form nonce (stores in Redis with TTL like settings does)
    $nonce = $user->generateFormNonce('calendar');
    Response::success('[CC] Nonce generated.', ['nonce' => $nonce]);
  }

  /**
   * Handles calendar work-entry updates.
   *
   * Accepts single or batched work entries from the calendar UI, validates request context (session, user, CSRF),
   * normalizes entry grouping by site, and delegates persistence to WorkEntry::updateWorkEntry().
   *
   * Supported input forms:
   * - Single entry (object)
   * - Multiple entries (array of objects)
   *
   * Processing steps:
   * - Filter and sanitize POST payload
   * - Verify calendar CSRF nonce
   * - Decode and normalize `entries` payload
   * - Group entries by site ID and aggregate hours
   * - Delete existing work entries for the day
   * - Persist normalized entries via domain logic
   *
   * Process Flow:
   *   if (batch payload exists)
   *     Process Batch; return
   *   else
   *     Process single entry
   *
   * Domain contract:
   * - Produces `WorkEntryInput` arrays
   * - Does not perform domain-level validation
   *
   * @phpstan-import-type WorkEntryInput from WorkEntry
   */
  #[Route('calendar/update', ['POST'])]
  /**
   * Handles updateCalendar operation.
   */
  public function updateCalendar(): void
  {
    // Require email verification before allowing calendar writes
    EmailVerificationGuard::requireVerified();

    // session_start() removed; session is no longer used

    $user = User::current();

    $allowedStrings = [
        // Extra fields for API
        CalendarFields::CAL_WORK_SAVE_DEFAULT->value,
        CalendarFields::CSRF_TOKEN->value,
        CalendarFields::DAY_ID->value,

        // Calendar-specific fields
        CalendarFields::CAL_WORK_DATE->value,
        CalendarFields::CAL_WORK_SITE_SELECT->value,

        // Work entry short fields
        CalendarFields::H->value,
        CalendarFields::L->value,
        CalendarFields::N->value,
        CalendarFields::O->value,
        CalendarFields::R->value,
        CalendarFields::S->value,
        // Allow encrypted blob payload from client
        'encrypted_blob',
        CalendarFields::T->value,
        CalendarFields::W->value,
        'mode',
        'organization_id',
        'org_id',
        'target_user_uuid',
        'owner_uuid',
    ];

    $allowedArrays = [];
    $filteredWorkDetails = RequestGuard::filterPost($allowedStrings, $allowedArrays);

    if (false === $filteredWorkDetails) {
      return;
    }

    /** @var array<string, null|bool|float|int|string> $filteredWorkDetails */
    $csrfToken = (string) InputSanitizer::postString('csrf_token');

    if (!$user->verifyFormNonce('calendar', $csrfToken)) {
      \PayCal\Domain\Response::error('[CC] Invalid CSRF token.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_FORBIDDEN);

      return;
    }

    $dayId = (string) InputSanitizer::postString('d');
    if (!$dayId) {
      \PayCal\Domain\Response::error('[CC] Day ID required.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $actorUUID = $user->user_uuid;
    $modeRaw = InputSanitizer::postString('mode');
    $mode = strtolower(trim($modeRaw === '' ? 'personal' : $modeRaw));

    $targetUserRaw = InputSanitizer::postString('target_user_uuid');
    if ($targetUserRaw === '') {
      $targetUserRaw = InputSanitizer::postString('owner_uuid');
    }
    $targetUserUUID = InputSanitizer::sanitizeString($targetUserRaw === '' ? $actorUUID : $targetUserRaw);

    $orgIdRaw = InputSanitizer::postString('organization_id');
    if ($orgIdRaw === '') {
      $orgIdRaw = InputSanitizer::postString('org_id');
    }
    $orgId = InputSanitizer::sanitizeString($orgIdRaw);

    if ($mode === 'organization') {
      if (!(bool) SystemConfig::get('org_shared_encryption_enabled')
        || !(bool) SystemConfig::get('org_shared_encryption_enable_write')) {
        self::incrementOrgWriteDeniedCounter('writes_disabled');
        self::appendOrganizationWorkWriteAudit($orgId, $actorUUID, $targetUserUUID, 'denied', 'writes_disabled', $dayId, 0);
        Response::error('[CC] Organization mode writes are disabled.', [], HttpStatus::HTTP_FORBIDDEN);

        return;
      }

      if ($orgId === '') {
        self::incrementOrgWriteDeniedCounter('missing_org_id');
        Response::error('[CC] organization_id is required for organization mode writes.', [], HttpStatus::HTTP_BAD_REQUEST);

        return;
      }

      if ($targetUserUUID === '') {
        self::incrementOrgWriteDeniedCounter('missing_target_user');
        self::appendOrganizationWorkWriteAudit($orgId, $actorUUID, $targetUserUUID, 'denied', 'missing_target_user', $dayId, 0);
        Response::error('[CC] target_user_uuid is required for organization mode writes.', [], HttpStatus::HTTP_BAD_REQUEST);

        return;
      }

      $orgDiscovery = new OrganizationDiscoveryService();
      if (!$orgDiscovery->canMutateWorkForOwner($actorUUID, $targetUserUUID, $orgId)) {
        self::incrementOrgWriteDeniedCounter('insufficient_scope');
        self::appendOrganizationWorkWriteAudit($orgId, $actorUUID, $targetUserUUID, 'denied', 'insufficient_scope', $dayId, 0);
        Response::error('[CC] Insufficient organization scope for delegated work mutation.', [], HttpStatus::HTTP_FORBIDDEN);

        return;
      }

      if (!self::isDateInCurrentPayPeriodForUser($dayId, $targetUserUUID)) {
        self::incrementOrgWriteDeniedCounter('outside_current_period');
        self::appendOrganizationWorkWriteAudit($orgId, $actorUUID, $targetUserUUID, 'denied', 'outside_current_period', $dayId, 0);
        Response::error('[CC] Organization mode writes are limited to the target user current pay period.', [], HttpStatus::HTTP_UNPROCESSABLE);

        return;
      }
    }

    // Check if date is locked for editing (historical record locking)
    if (WorkEntryLockService::isLocked($dayId, $targetUserUUID)) {
      if ($mode === 'organization') {
        self::incrementOrgWriteDeniedCounter('historical_lock');
        self::appendOrganizationWorkWriteAudit($orgId, $actorUUID, $targetUserUUID, 'denied', 'historical_lock', $dayId, 0);
      }
      \PayCal\Domain\Response::error(
        self::entryLockedMessage(),
        ['status' => 'ENTRY_LOCKED', 'date' => $dayId],
        \PayCal\Domain\Enums\HttpStatus::HTTP_FORBIDDEN
      );

      return;
    }

    $entriesJson = InputSanitizer::postRaw('entries');
    if ($entriesJson) {
      $entriesJson = html_entity_decode($entriesJson, ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $entries = json_decode($entriesJson, true);

      if (is_array($entries) && (isset($entries['site_id']) || isset($entries['s']))) {
        // Single entry as object, wrap in array
        $entries = [$entries];
      } elseif (!is_array($entries)) {
        \PayCal\Domain\Response::error("[CC] Invalid entries format: {$entriesJson}", [], HttpStatus::HTTP_BAD_REQUEST);

        return;
      }

      $pattern = "work:{$targetUserUUID}:{$dayId}:*";
      $altPattern = \PayCal\Domain\Constants\Keys::WORK . ":{$targetUserUUID}:{$dayId}:*";
      
      \PayCal\Domain\Log::debug("[CC] Delete patterns - hardcoded: {$pattern}, Keys::WORK: {$altPattern}, match: " . ($pattern === $altPattern ? 'YES' : 'NO'));
      \PayCal\Observability\Lens::add('Pattern Comparison', [
        'hardcoded_pattern' => $pattern,
        'keys_constant_pattern' => $altPattern,
        'patterns_match' => $pattern === $altPattern
      ], 'pattern_debug');
      
      $deleted = WorkEntry::deleteWorkEntriesByPattern($pattern);
      \PayCal\Domain\Log::debug("[CC] Delete pattern: {$pattern}, deleted: {$deleted}");
      \PayCal\Observability\Lens::add('Calendar Delete Pattern', [
        'pattern' => $pattern,
        'deleted_count' => $deleted
      ], 'delete');

      // Group entries by site ID to combine hours for same site
      $grouped = [];
      foreach ($entries as $entry) {
        if (!is_array($entry)) {
          continue;
        }

        $siteId = InputSanitizer::sanitizeString($entry['site_id'] ?? $entry['s'] ?? '');

        if ('' === $siteId) {
          continue;
        }

        $blob = isset($entry['encrypted_blob']) && is_string($entry['encrypted_blob'])
          ? $entry['encrypted_blob']
          : '';
        if ('' === $blob) {
          Response::error('[CC] encrypted_blob required for all calendar entries.', [], HttpStatus::HTTP_UNPROCESSABLE);

          return;
        }

        $blobValidation = WorkEntry::validateEncryptedBlob($blob);
        if (!$blobValidation['valid']) {
          Response::error('[CC] Invalid encrypted_blob payload.', ['error' => $blobValidation['error']], HttpStatus::HTTP_UNPROCESSABLE);

          return;
        }

        if ($mode === 'organization') {
          $contextValidation = WorkEntry::validateOrganizationEnvelopeContext(
            $blob,
            $orgId,
            OrganizationDiscoveryService::ORG_DEK_SEGMENT_CURRENT_PERIOD
          );
          if (!$contextValidation['valid']) {
            self::incrementOrgWriteDeniedCounter('envelope_context_mismatch');
            self::appendOrganizationWorkWriteAudit($orgId, $actorUUID, $targetUserUUID, 'denied', 'envelope_context_mismatch', $dayId, 0);
            Response::error('[CC] Organization envelope context mismatch.', ['error' => $contextValidation['error']], HttpStatus::HTTP_UNPROCESSABLE);

            return;
          }
        }

        if (!isset($grouped[$siteId])) {
          $siteName = is_scalar($entry['site_name'] ?? null) ? trim((string) $entry['site_name']) : '';
          $hours = is_numeric($entry['hours'] ?? null) ? (float) $entry['hours'] : 0.0;
          $regularHours = is_numeric($entry['regular_hours'] ?? null) ? (float) $entry['regular_hours'] : 0.0;
          $overtimeHours = is_numeric($entry['overtime_hours'] ?? null) ? (float) $entry['overtime_hours'] : 0.0;
          $livingOut = is_numeric($entry['living_out_allowance'] ?? null) ? (float) $entry['living_out_allowance'] : 0.0;
          $travelHours = is_numeric($entry['travel_hours'] ?? null) ? (float) $entry['travel_hours'] : 0.0;
          $wage = is_numeric($entry['wage'] ?? null) ? (float) $entry['wage'] : 0.0;

          $grouped[$siteId] = [
            'date' => $dayId,
            'site_id' => $siteId,
            'site_name' => $siteName,
            'hours' => $hours,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'living_out_allowance' => $livingOut,
            'travel_hours' => $travelHours,
            'wage' => $wage,
            'encrypted_blob' => $blob,
          ];
        } else {
          $grouped[$siteId]['encrypted_blob'] = $blob;
          $grouped[$siteId]['site_name'] = is_scalar($entry['site_name'] ?? null) ? trim((string) $entry['site_name']) : (string) $grouped[$siteId]['site_name'];
          $grouped[$siteId]['hours'] = is_numeric($entry['hours'] ?? null) ? (float) $entry['hours'] : (float) $grouped[$siteId]['hours'];
          $grouped[$siteId]['regular_hours'] = is_numeric($entry['regular_hours'] ?? null) ? (float) $entry['regular_hours'] : (float) $grouped[$siteId]['regular_hours'];
          $grouped[$siteId]['overtime_hours'] = is_numeric($entry['overtime_hours'] ?? null) ? (float) $entry['overtime_hours'] : (float) $grouped[$siteId]['overtime_hours'];
          $grouped[$siteId]['living_out_allowance'] = is_numeric($entry['living_out_allowance'] ?? null) ? (float) $entry['living_out_allowance'] : (float) $grouped[$siteId]['living_out_allowance'];
          $grouped[$siteId]['travel_hours'] = is_numeric($entry['travel_hours'] ?? null) ? (float) $entry['travel_hours'] : (float) $grouped[$siteId]['travel_hours'];
          $grouped[$siteId]['wage'] = is_numeric($entry['wage'] ?? null) ? (float) $entry['wage'] : (float) $grouped[$siteId]['wage'];
        }

        try {
          $v = \PayCal\Domain\Config\SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;
          Database::incr("telemetry:encryption:{$v}:client:entry_received");
        } catch (Throwable $e) {
          Log::debug('Telemetry increment failed: '.$e->getMessage());
        }
      }

      $updated = 0;
      $total = count($grouped);
      \PayCal\Observability\Lens::add('Calendar Batch Update', [
        'day_id' => $dayId,
        'grouped_entries' => $total
      ], 'batch_start');

      foreach ($grouped as $workDetails) {
        $workDetails['cal_work_save_as_default'] = InputSanitizer::postString('cal_work_save_as_default');

        if (true !== WorkEntryRepository::save($workDetails, $targetUserUUID)) {
          \PayCal\Domain\Response::error('[CC] Batch update error.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_UNPROCESSABLE);

          return;
        }

        ++$updated;
      }
      \PayCal\Observability\Lens::add('Calendar Batch Update Complete', [
        'total_grouped' => $total,
        'successfully_updated' => $updated
      ], 'batch_end');

      if ($updated !== $total) {
        \PayCal\Domain\Response::error('[CC] Batch update mismatch.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_UNPROCESSABLE);

        return;
      }

      // Always recalculate affected week after batch save/delete/paste so
      // regular/overtime distribution updates in real time.
      Work::processWorkWeekContainingDate($targetUserUUID, $dayId);
      \PayCal\Observability\Lens::add('Calendar Week Recalculation', [
        'mode' => 'batch',
        'day_id' => $dayId,
      ], 'recalc');

      // Refresh CSRF token TTL after successful update
      $sessionTimeout = (int) ($user->session_timeout);
      $ttl = max((int) SessionTimeout::ONE_MIN->value, min($sessionTimeout, (int) SessionTimeout::TWO_HOURS->value));
      Database::expire(Keys::USER . ":" . $user->user_uuid . ":csrf:calendar:{$csrfToken}", $ttl);

      $week = ('cli' === PHP_SAPI) ? [] : $this->buildWeekPayload($dayId, $targetUserUUID);
      
      $diagnostic = [
        'dayId' => $dayId,
        'deletePattern' => $pattern,
        'entriesDeleted' => $deleted,
        'entriesUpdated' => $updated,
        'totalEntriesInRequest' => $total,
        'lens_data' => \PayCal\Observability\Lens::data()
      ];
      \PayCal\Observability\Lens::add('Calendar Batch Success', $diagnostic, 'success');

      if ($mode === 'organization') {
        self::appendOrganizationWorkWriteAudit($orgId, $actorUUID, $targetUserUUID, 'success', 'saved', $dayId, $updated);
      }
      
      Response::success('[CC] Batch update success.', ['week' => $week, 'diagnostic' => $diagnostic], HttpStatus::HTTP_OK);

      return;
    } // json entries processing

    // Single-entry plaintext updates are disabled in encrypted-only mode.
    Response::error('[CC] encrypted_blob payload required.', [], HttpStatus::HTTP_UNPROCESSABLE);
  }

  /**
   * Checks whether a date belongs to the caller's current pay period.
   */
  private static function isDateInCurrentPayPeriodForUser(string $dateYmd, string $userUUID): bool
  {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd)) {
      return false;
    }

    $user = UserRepository::getByUUID($userUUID);
    if (null === $user) {
      return false;
    }

    $tz = $user->timezone ?: 'America/Edmonton';
    $zone = new \DateTimeZone($tz);
    $targetDate = new \DateTimeImmutable($dateYmd, $zone);
    $today = new \DateTimeImmutable('now', $zone);

    $currentPeriod = PayPeriodGenerator::resolveForDate($user, $today);
    $targetPeriod = PayPeriodGenerator::resolveForDate($user, $targetDate);
    if (null === $currentPeriod || null === $targetPeriod) {
      return false;
    }

    return $currentPeriod->start()->format('Y-m-d') === $targetPeriod->start()->format('Y-m-d')
      && $currentPeriod->endExclusive()->format('Y-m-d') === $targetPeriod->endExclusive()->format('Y-m-d');
  }

  /**
   * Increments denied-write telemetry for organization-bound work updates.
   */
  private static function incrementOrgWriteDeniedCounter(string $reason): void
  {
    $reason = trim(InputSanitizer::sanitizeString($reason));
    if ($reason === '') {
      $reason = 'unknown';
    }

    try {
      $v = SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;
      Database::incr("telemetry:encryption:{$v}:org:work_write_denied:{$reason}");
    } catch (Throwable $e) {
      Log::debug('Org write denied telemetry increment failed: ' . $e->getMessage());
    }
  }

  /**
   * Appends an organization audit event for work-write operations.
   */
  private static function appendOrganizationWorkWriteAudit(
    string $orgId,
    string $actorUUID,
    string $targetUserUUID,
    string $outcome,
    string $reason,
    string $dayId,
    int $entryCount
  ): void {
    $orgId = trim(InputSanitizer::sanitizeString($orgId));
    if ($orgId === '') {
      return;
    }

    try {
      (new OrganizationDiscoveryService())->appendOrganizationAuditEvent(
        $orgId,
        'org.work.write',
        $actorUUID,
        [
          'target_user_uuid' => $targetUserUUID,
          'date' => $dayId,
          'segment' => OrganizationDiscoveryService::ORG_DEK_SEGMENT_CURRENT_PERIOD,
          'outcome' => $outcome,
          'reason' => $reason,
          'entry_count' => (string) $entryCount,
        ]
      );
    } catch (\Throwable $e) {
      Log::debug('[CalendarController] org.work.write audit emit failed: ' . $e->getMessage());
    }
  }

  /**
   * Deletes all work entries for a specific calendar day.
   *
   * Expects the target date identifier to be provided via the
   * `X-Resource-Id` request header. The value is validated using
   * RequestGuard::deleteCheck() before any destructive action occurs.
   *
   * Processing steps:
   * - Read date identifier from request headers
   * - Validate and normalize the identifier
   * - Delete all matching work-entry keys for the user and day
   * - Return success or unprocessable response
   *
   * Scope:
   * - Operates only on the authenticated user's data
   * - Performs no soft deletes; removal is permanent
   */
  #[Route('calendar/delete', ['DELETE'])]
  /**
   * Handles delete operation.
   */
  public function delete(): void
  {
    // Require email verification before allowing calendar deletes
    EmailVerificationGuard::requireVerified();

    $headers = getallheaders();
    $dateID = $headers['X-Resource-Id'] ?? null;

    $filteredDateID = RequestGuard::deleteCheck($dateID);
    if (false === $filteredDateID) {
      return;
    }

    $user = User::current();

    // Check if date is locked for editing (historical record locking)
    if (WorkEntryLockService::isLocked($filteredDateID, $user->user_uuid)) {
      \PayCal\Domain\Response::error(
        self::entryLockedMessage(),
        ['status' => 'ENTRY_LOCKED', 'date' => $filteredDateID],
        \PayCal\Domain\Enums\HttpStatus::HTTP_FORBIDDEN
      );

      return;
    }

    $pattern = Keys::WORK . ':' . User::currentUUID() . ":{$filteredDateID}:*";
    $success = WorkEntry::deleteWorkEntriesByPattern($pattern);

    if (!$success) {
      \PayCal\Domain\Response::error('[CC] Deletion unprocessible.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_UNPROCESSABLE);
      return;
    }

    // Always recalculate affected week after delete so overtime distribution updates in real time
    Work::processWorkWeekContainingDate($user->user_uuid, $filteredDateID);
    \PayCal\Observability\Lens::add('Calendar Week Recalculation', [
      'mode' => 'delete',
      'day_id' => $filteredDateID,
    ], 'recalc');

    // Return updated week payload for grid refresh
    $week = ('cli' === PHP_SAPI) ? [] : $this->buildWeekPayload($filteredDateID, $user->user_uuid);
    $responsePayload = [];
    if ([] !== $week) {
      $responsePayload['week'] = $week;
    }

    Response::success('[CC] Deletion successful.', $responsePayload, \PayCal\Domain\Enums\HttpStatus::HTTP_OK);
  }

  /**
   * Returns calendar data as JSON for AJAX rendering.
   *
   * @route GET /api/calendar?year=YYYY&month=MM
   */
  #[Route('calendar', ['GET'])]
  /**
   * Handles getCalendar operation.
   */
  public function getCalendar(): void
  {
    try {
      $correlationProbe = self::siteFinancialCorrelationComposeProbe();
      if (($correlationProbe['status'] ?? '') !== 'success') {
        Response::error('[CC] Correlation context denied.', [
          'context' => self::correlationContext(),
          'reason' => 'metadata_correlation_denied',
          'decision' => $correlationProbe['decision'] ?? null,
        ], HttpStatus::HTTP_FORBIDDEN);

        return;
      }

      session_start();
      $actor = User::current();
      $user = $this->resolveCalendarSubjectUser($actor);
      if ($user === null) {
        Response::error('[CC] Forbidden target user for calendar view.', [], HttpStatus::HTTP_FORBIDDEN);
        return;
      }
      // Get year/month from query params, default to current date
      $yearStr = InputSanitizer::getString('year');
      $monthStr = InputSanitizer::getString('month');
      $year = null !== $yearStr ? intval($yearStr) : intval(date('Y'));
      $month = null !== $monthStr ? intval($monthStr) : intval(date('m'));
      // Validate parameters
      $yearMin = (int) SystemConfig::get('year_min');
      $yearMax = (int) SystemConfig::get('year_max');
      if ($year < $yearMin || $year > $yearMax || $month < self::MIN_MONTH || $month > self::MAX_MONTH) {
        \PayCal\Domain\Response::error('[CC] Invalid year or month.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_BAD_REQUEST);
        return;
      }
      $calendar = Calendar::fromDate(new \DateTime("{$year}-{$month}-01"), self::WEEK_START_SUNDAY, $user);
      // Generate calendar data
      $calendarData = $this->generateCalendarData($calendar, $year, $month, $user);
      Response::success('[CC] Calendar data retrieved.', $calendarData, \PayCal\Domain\Enums\HttpStatus::HTTP_OK);
    } catch (Throwable $e) {
      Log::error('[CC] getCalendar exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
      Response::error('[CC] Failed to retrieve calendar data.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Build a week payload for a given day.
   *
   * @param string $dayId    YYYY-MM-DD date
   * @param string $userUUID User UUID
   *
   * @return array<string, mixed>
   */
  private function buildWeekPayload(string $dayId, string $userUUID): array
  {
    if (!self::canCorrelateSiteMetadataWithFinancialPayload()) {
      return [];
    }

    \PayCal\Domain\Log::debug("CalendarController::buildWeekPayload for dayId: {$dayId}");

    $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dayId);
    if (!$date) {
      return [];
    }

    $dayOfWeek = (int) $date->format('w'); // 0 = Sunday
    $weekStart = $date->modify("-{$dayOfWeek} days");
    $weekEnd = $weekStart->modify('+6 days');

    $days = [];
    $work = new Work($userUUID);
    $cursor = $weekStart;

    for ($i = 0; $i < 7; ++$i) {
      $dateKey = $cursor->format('Y-m-d');
      $days[$dateKey] = [];

      $workForDate = $work->getWorkForDate($dateKey);
      $entryCount = count(get_object_vars($workForDate));
      \PayCal\Domain\Log::debug("buildWeekPayload: date {$dateKey} has {$entryCount} entries");

      foreach (get_object_vars($workForDate) as $key => $entry) {
        if (!is_array($entry)) {
          continue;
        }

        // Handle both regular and archived key formats
        // Regular: work:UUID:DATE:SITE_ID (indices 0,1,2,3)
        // Archived: work:archived:UUID:DATE:SITE_ID (indices 0,1,2,3,4)
        $keyParts = explode(':', $key);
        $isArchived = (isset($keyParts[1]) && 'archived' === $keyParts[1]);
        $siteId = $isArchived ? ($keyParts[4] ?? '') : ($keyParts[3] ?? '');
        
        $workEntry = [
          'site_id' => self::scalarString($entry['site_id'] ?? $entry['s'] ?? $siteId),
          'site_name' => self::scalarString($entry['site_name'] ?? $entry['n'] ?? ''),
          'hours' => self::numericFloat($entry['hours'] ?? $entry['h'] ?? 0),
          'regular_hours' => self::numericFloat($entry['regular_hours'] ?? $entry['r'] ?? 0),
          'overtime_hours' => self::numericFloat($entry['overtime_hours'] ?? $entry['o'] ?? 0),
          'living_out_allowance' => self::numericFloat($entry['living_out_allowance'] ?? $entry['l'] ?? 0),
          'travel_hours' => self::numericFloat($entry['travel_hours'] ?? $entry['t'] ?? 0),
          'wage' => self::numericFloat($entry['wage'] ?? $entry['w'] ?? 0),
        ];
        // Phase 2: Include encrypted_blob if present
        if (isset($entry['encrypted_blob']) && is_string($entry['encrypted_blob'])) {
          $workEntry['encrypted_blob'] = $entry['encrypted_blob'];
        }
        $days[$dateKey][] = $workEntry;
      }

      $cursor = $cursor->modify('+1 day');
    }

    ksort($days);

    // Get lock boundary for historical record locking
    $lockBoundary = WorkEntryLockService::getLockBoundaryDate($userUUID);

    return [
        'start' => $weekStart->format('Y-m-d'),
        'end' => $weekEnd->format('Y-m-d'),
        'days' => $days,
        'lockBoundary' => $lockBoundary,
        'updated_at' => date('c'),
    ];
  }

  /**
   * Generates calendar data structure for JSON response.
    * @return array<string, mixed>
   */
  private function generateCalendarData(Calendar $calendar, int $year, int $month, User $user): array
  {
    // Get navigation dates
    $prevDate = $calendar->getPreviousMonthDate();
    $nextDate = $calendar->getNextMonthDate();
    [$prevYear, $prevMonth] = explode('-', $prevDate);
    [$nextYear, $nextMonth] = explode('-', $nextDate);

    // Get week day headers
    $weekHeaders = [];
    foreach ($calendar->generateWeekDayLabels() as $day) {
      $dayNameFormat = $user->calendar_day_name_format;
      $weekHeaders[] = [
          'abbr' => $day[$dayNameFormat],
          'full' => $day['long'],
      ];
    }

    // Pre-fetch work data
    $firstOfMonth = $calendar->getFirstDay();
    $firstWeekday = ((int) $firstOfMonth->format('w') + 7 - self::WEEK_START_SUNDAY) % 7;
    $gridStart = (clone $firstOfMonth)->modify("- {$firstWeekday} days");
    $gridEnd = (clone $gridStart)->modify('+' . self::GRID_SPAN_DAYS . ' days');
    $start = \DateTimeImmutable::createFromMutable($gridStart);
    $end = \DateTimeImmutable::createFromMutable($gridEnd);

    $workByDay = [];
    foreach (Work::getWorkInRange($start, $end->modify('+1 day'), $user->user_uuid) as $data) {
      $ymd = self::scalarString($data['date'] ?? '');
      if ('' === $ymd) {
        continue;
      }
      $workByDay[$ymd][] = $data;
    }

    // Generate days array
    $days = [];
    foreach ($calendar->getDayIDs() as $ymd) {
      $date = new \DateTime($ymd);
      $dayYear = $date->format('Y');
      $dayMonth = $date->format('m');
      $dayNum = $date->format('d');
      $isToday = date('Y-m-d') === $ymd;
      $workEntries = $workByDay[$ymd] ?? [];

      $dayClasses = ['calendar_day'];
      if ($isToday) {
        $dayClasses[] = 'calendar_day_today';
      }
      if ($dayMonth !== str_pad((string) $month, 2, '0', STR_PAD_LEFT)) {
        $dayClasses[] = 'calendar_day_adjacent';
      }

      // Add position classes based on user settings
      $dateLabelPosition = $user->calendar_date_label_position;
      $workEntryPosition = $user->calendar_work_entry_position;
      $dayClasses[] = 'date-label-'.$dateLabelPosition;
      $dayClasses[] = 'work-entry-'.$workEntryPosition;

      $workHtml = '';
      $totalHours = 0;
      if (!empty($workEntries)) {
        $totalHours = (float) count($workEntries);
        $dayClasses[] = 'calendar_day_has_work';
      }

      // Generate formatted date label and aria label
      $dateLabel = Strings::formatFulldateWithOrdinal($ymd);
      $audioLabelType = self::scalarString($user->calendar_audio_labels);
      if (!in_array($audioLabelType, ['number', 'short', 'long'], true)) {
        $audioLabelType = 'number';
      }
      $ariaLabel = Strings::formatDateAria($ymd, $audioLabelType);

      $days[] = [
          'id' => $ymd,
          'date' => $ymd,
          'day' => intval($dayNum),
          'month' => intval($dayMonth),
          'year' => intval($dayYear),
          'isToday' => $isToday,
          'isAdjacent' => $dayMonth !== str_pad((string) $month, 2, '0', STR_PAD_LEFT),
          'classes' => implode(' ', $dayClasses),
          'dateLabel' => $dateLabel,
          'ariaLabel' => $ariaLabel,
          'workHtml' => $workHtml,
          'workEntries' => $workEntries,
          'totalHours' => $totalHours,
      ];
    }

    // Get month name
    $userLocale = strtolower($user->language).'_'.strtoupper($user->language);
    $formatter = new IntlDateFormatter($userLocale, IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
    $formatter->setPattern('MMMM');
    $timestamp = mktime(0, 0, 0, $month, 1, $year);
    if ($timestamp === false) {
      $timestamp = time(); // Fallback to current time
    }
    $monthNameFormatted = $formatter->format($timestamp);
    $monthName = is_string($monthNameFormatted) ? strtoupper($monthNameFormatted) : 'UNKNOWN';

    // Get lock boundary for historical record locking
    $lockBoundary = WorkEntryLockService::getLockBoundaryDate($user->user_uuid);

    return [
        'year' => $year,
        'month' => $month,
        'monthName' => $monthName,
        'lockBoundary' => $lockBoundary,
        'navigation' => [
            'prev' => ['year' => intval($prevYear), 'month' => intval($prevMonth), 'url' => "?{$prevYear}-{$prevMonth}"],
            'next' => ['year' => intval($nextYear), 'month' => intval($nextMonth), 'url' => "?{$nextYear}-{$nextMonth}"],
        ],
        'weekHeaders' => $weekHeaders,
        'days' => $days,
    ];
  }

  /**
   * Returns pure calendar data for a month with normalized work entries.
   *
   * New API endpoint optimized for front-end rendering:
   * - Returns strict 42-cell grid (always)
   * - Normalized work data (ID-based references)
   * - No HTML, CSS classes, or UI strings
   * - No computed totals (frontend responsibility)
   * - Minimal payload size
   *
   * @route GET /api/v1/data/calendar/month/get?month=YYYY-MM
   */
  #[Route('data/calendar/month/get', ['GET'])]
  /**
   * Handles getMonthData operation.
   */
  public function getMonthData(): void
  {
    $actor = User::current();
    $user = $this->resolveCalendarSubjectUser($actor);
    if ($user === null) {
      Response::error('[CC] Forbidden target user for calendar view.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    if ($user->user_uuid === SystemConfig::PUBLIC_UUID) {
      Response::error('[CC] Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $correlationProbe = self::siteFinancialCorrelationComposeProbe();
    if (($correlationProbe['status'] ?? '') !== 'success') {
      Response::error('[CC] Correlation context denied.', [
        'context' => self::correlationContext(),
        'reason' => 'metadata_correlation_denied',
        'decision' => $correlationProbe['decision'] ?? null,
      ], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    // Get month from query params, default to current month
    $monthStr = InputSanitizer::getString('month');
    
    if (null === $monthStr || '' === $monthStr) {
      $monthStr = date('Y-m');
    }

    // Validate month format (YYYY-MM)
    if (!preg_match('/^\d{4}-\d{2}$/', $monthStr)) {
      Response::error('[CC] Invalid month format. Expected YYYY-MM.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    [$yearStr, $monthNumStr] = explode('-', $monthStr);
    $year = (int) $yearStr;
    $month = (int) $monthNumStr;

    // Validate year and month ranges
    $yearMin = (int) SystemConfig::get('year_min');
    $yearMax = (int) SystemConfig::get('year_max');
    if ($year < $yearMin || $year > $yearMax || $month < self::MIN_MONTH || $month > self::MAX_MONTH) {
      Response::error('[CC] Invalid year or month.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    try {
      $calendar = Calendar::fromDate(new \DateTime("{$year}-{$month}-01"), self::WEEK_START_SUNDAY, $user);
      $monthData = $this->generateMonthData($calendar, $user);
      
      Response::success('[CC] Month data retrieved.', $monthData, HttpStatus::HTTP_OK);
    } catch (\Throwable $e) {
      Log::debug('[CC] Exception in getMonthData: ' . $e->getMessage());
      Response::error('[CC] Failed to generate month data.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }


  /**
   * Generates pure month data with normalized work entries.
   *
   * Returns strict structure:
   * {
   *   "month": "YYYY-MM",
   *   "today": "YYYY-MM-DD",
   *   "cells": [
   *     {"d": "YYYY-MM-DD", "a": 0|1, "w": ["w1", "w2"]}
   *   ],
   *   "work": {
   *     "w1": {"site_id": "...", "regular": 8, "overtime": 0, ...}
   *   }
   * }
   *
   * @param Calendar $calendar Calendar instance for the target month
   * @param User     $user     Current user
   *
   * @return array<string, mixed>
   */
  private function generateMonthData(Calendar $calendar, User $user): array
  {
    $targetMonth = $calendar->getFirstDay()->format('Y-m');
    $today = date('Y-m-d');

    // Pre-fetch work data for the entire 42-day grid
    $firstOfMonth = $calendar->getFirstDay();
    $firstWeekday = ((int) $firstOfMonth->format('w') + 7 - self::WEEK_START_SUNDAY) % 7;
    $gridStart = (clone $firstOfMonth)->modify("- {$firstWeekday} days");
    $gridEnd = (clone $gridStart)->modify('+' . self::GRID_SPAN_DAYS . ' days');
    
    $start = \DateTimeImmutable::createFromMutable($gridStart);
    $end = \DateTimeImmutable::createFromMutable($gridEnd);

    // Fetch all work entries in the grid range
    $workByDay = [];
    foreach (Work::getWorkInRange($start, $end->modify('+1 day'), $user->user_uuid) as $data) {
      $ymd = self::scalarString($data['date'] ?? '');
      if ('' === $ymd) {
        continue;
      }
      if (!isset($workByDay[$ymd])) {
        $workByDay[$ymd] = [];
      }
      $workByDay[$ymd][] = $data;
    }

    // Build normalized work dictionary and generate work IDs
    $workDict = [];
    $workIdCounter = 1;
    $dateToWorkIds = [];

    foreach ($workByDay as $ymd => $entries) {
      $dateToWorkIds[$ymd] = [];
      
      foreach ($entries as $entry) {
        $workId = 'w' . $workIdCounter++;

        $regular = self::numericFloat($entry['regular_hours'] ?? $entry['r'] ?? 0);
        $overtime = self::numericFloat($entry['overtime_hours'] ?? $entry['o'] ?? 0);
        $loa = self::numericFloat($entry['living_out_allowance'] ?? $entry['living_out'] ?? $entry['l'] ?? 0);
        $travel = self::numericFloat($entry['travel_hours'] ?? $entry['travel'] ?? $entry['t'] ?? 0);
        $hours = self::numericFloat($entry['hours'] ?? $entry['h'] ?? ($regular + $overtime));
        $wage = self::numericFloat($entry['wage'] ?? $entry['w'] ?? 0);
        
        // Store normalized work data (include site_name for frontend display)
        $workDict[$workId] = [
          'site_id' => self::scalarString($entry['site_id'] ?? ''),
          'site_name' => self::scalarString($entry['site_name'] ?? ''),
          'regular' => $regular,
          'r' => $regular,
          'regular_hours' => $regular,
          'overtime' => $overtime,
          'o' => $overtime,
          'overtime_hours' => $overtime,
          'loa' => $loa,
          'living_out' => $loa,
          'l' => $loa,
          'living_out_allowance' => $loa,
          'travel' => $travel,
          't' => $travel,
          'travel_hours' => $travel,
          'hours' => $hours,
          'h' => $hours,
          'wage' => $wage,
          'w' => $wage,
        ];
        
        $dateToWorkIds[$ymd][] = $workId;
      }
    }

    // Generate 42 cells
    $cells = [];
    foreach ($calendar->getDayIDs() as $ymd) {
      $date = new \DateTime($ymd);
      $cellMonth = $date->format('Y-m');
      $isAdjacent = ($cellMonth !== $targetMonth) ? 1 : 0;
      
      $cells[] = [
        'd' => $ymd,
        'a' => $isAdjacent,
        'w' => $dateToWorkIds[$ymd] ?? [],
      ];
    }

    return [
      'month' => $targetMonth,
      'today' => $today,
      'cells' => $cells,
      'work' => $workDict,
    ];
  }

  /**
   * Repair corrupt encrypted entries by clearing them.
   *
   * When encrypted entries cannot be decrypted with a valid DEK, they are
   * irrecoverably corrupt (wrong schema, test data, or corrupted write).
   * This endpoint safely clears such entries by deleting all work entries
   * for the specified dates, allowing the calendar to render cleanly.
   *
   * @return void
   */
  #[Route('calendar/repair-corrupt', ['POST'])]
  /**
   * Handles repairCorruptEntries operation.
   */
  public function repairCorruptEntries(): void
  {
    $user = User::current();

    Log::info('[CC] Repair corrupt entries endpoint called', 'user_uuid=' . $user->user_uuid);
    Lens::add('Calendar Repair Request', [
      'user_uuid' => $user->user_uuid,
      'endpoint' => 'repair-corrupt',
      'post_data' => $_POST,
    ], 'repair');

    // Get the array of dates to repair
    $datesJson = InputSanitizer::postRaw('dates');
    Log::info('[CC] Dates JSON received', 'json=' . ($datesJson ?: 'null'));
    
    if (!$datesJson) {
      Log::warning('[CC] No dates provided for repair');
      Response::error('[CC] No dates provided for repair.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $dates = json_decode($datesJson, true);
    Lens::add('Calendar Repair Dates Decoded', [
      'dates' => $dates,
      'is_array' => is_array($dates),
      'count' => is_array($dates) ? count($dates) : 0,
    ], 'repair');
    
    if (!is_array($dates) || empty($dates)) {
      Log::warning('[CC] Invalid dates format: ' . $datesJson);
      Response::error('[CC] Invalid dates format.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    // Validate and sanitize dates (YYYY-MM-DD format)
    $validDates = [];
    foreach ($dates as $date) {
      if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $validDates[] = $date;
      } else {
        Log::warning('[CC] Invalid date format: ' . json_encode($date));
      }
    }

    Lens::add('Calendar Repair Valid Dates', [
      'validDates' => $validDates,
      'count' => count($validDates),
    ], 'repair');

    if (empty($validDates)) {
      Log::warning('[CC] No valid dates to repair after validation');
      Response::error('[CC] No valid dates to repair.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    // Safety limit: prevent accidental large-scale wipes
    if (count($validDates) > self::MAX_AUTOREPAIR_DATES) {
      Log::error('[CC] Repair request exceeds safety limit', 'requested=' . count($validDates) . ' max=' . self::MAX_AUTOREPAIR_DATES);
      Lens::add('Calendar Repair SAFETY ABORT', [
        'requested_count' => count($validDates),
        'max_allowed' => self::MAX_AUTOREPAIR_DATES,
        'user_uuid' => $user->user_uuid,
        'dates' => $validDates,
      ], 'critical');
      Response::error(
        '[CC] Repair request exceeds safety limit. Manual intervention required.',
        [
          'requested_count' => count($validDates),
          'max_allowed' => self::MAX_AUTOREPAIR_DATES,
        ],
        HttpStatus::HTTP_FORBIDDEN
      );
      return;
    }

    $repairedCount = 0;
    $failedDates = [];

    foreach ($validDates as $date) {
      Log::info('[CC] Processing repair for date', $date);
      try {
        // Check if date is locked (respect historical record locking)
        if (WorkEntryLockService::isLocked($date, $user->user_uuid)) {
          Log::warning("[CC] Skipping repair for locked date: {$date}");
          $failedDates[] = $date;
          continue;
        }

        // Delete all work entries for this date
        $pattern = Keys::WORK . ":{$user->user_uuid}:{$date}:*";
        Log::info('[CC] Deleting work entries with pattern', $pattern);
        $deleted = WorkEntry::deleteWorkEntriesByPattern($pattern);
        
        Log::info("[CC] Repaired corrupt entries for date: {$date}, deleted: {$deleted}");
        Lens::add('Calendar Repair Completed', [
          'date' => $date,
          'deleted_count' => $deleted,
          'user_uuid' => $user->user_uuid,
          'pattern' => $pattern,
        ], 'repair');

        $repairedCount++;
      } catch (Throwable $e) {
        Log::error("[CC] Failed to repair date {$date}: " . $e->getMessage());
        Lens::add('Calendar Repair Error', [
          'date' => $date,
          'exception' => get_class($e),
          'message' => $e->getMessage(),
        ], 'error');
        $failedDates[] = $date;
      }
    }

    Lens::add('Calendar Repair Process Completed', [
      'repaired_count' => $repairedCount,
      'failed_count' => count($failedDates),
      'failed_dates' => $failedDates,
    ], 'repair');

    Response::success('[CC] Corrupt entries repaired.', [
      'repaired_count' => $repairedCount,
      'requested_count' => count($validDates),
      'failed_dates' => $failedDates,
    ]);
  }
}


