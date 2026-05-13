<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Authentication;
use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Database;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Enums\Currency;
use PayCal\Domain\Enums\FormTTL;
use PayCal\Observability\Lens;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Log;
use PayCal\Domain\Language;
use PayCal\Domain\OrganizationDiscoveryService;
use PayCal\Domain\PayPeriodGenerator;
use PayCal\Domain\Enums\PayFrequency;
use PayCal\Infrastructure\Resilience\RedisReliabilityService;
use PayCal\Domain\RequestGuard;
use PayCal\Domain\Response;
use PayCal\Infrastructure\Telemetry\SecurityLog;
use PayCal\Domain\Enums\SessionTimeout;
use PayCal\Domain\Enums\Timezone;
use PayCal\Domain\SystemLimits;
use PayCal\Domain\User;
use PayCal\Domain\UserPreferenceDefaults;
use PayCal\Domain\UserRepository;
use PayCal\Domain\UserSettings;
use PayCal\Domain\Work;
use PayCal\Domain\WorkEntryLockService;

/**
 * SettingsController.php
 *
 * Purpose: Authenticated profile/settings API layer for user preferences,
 * pay-period settings, destructive account actions, and related recovery hooks.
 *
 * Developer notes:
 * - Settings writes here can cascade into pay-period regeneration, lock
 *   boundary changes, and organization-sync behavior.
 * - Keep field validation and normalization aligned with UserRepository and
 *   UserSettings rather than duplicating rules in controller branches.
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
 * Settings API surface.
 *
 * Responsibilities:
 * - Read and update user-facing settings consumed by the profile/settings UI.
 * - Coordinate destructive account operations through the correct safeguards.
 * - Trigger downstream recalculation flows when settings affect schedule/locks.
 */
class SettingsController
{
  private const DATA_TRANSFER_SCHEMA_VERSION = 2;

  /**
   * Constructor. Aborts with 401 if the request is not authenticated.
   */
  public function __construct()
  {
    Authentication::abortIfUnauthenticated();
  }
  private const DATA_IMPORT_TTL_SECONDS = 1800;
  private const DATA_EXPORT_PLAINTEXT_WARNING = 'Export payload includes plaintext account and work data. Treat the file as sensitive.';
  private const SUPPORTED_LOCALES = [
    'en-CA',
    'fr-CA',
    'en-US',
    'en-GB',
    'fr-FR',
    'de-DE',
    'es-ES',
    'pt-BR',
  ];

  /**
   * GET profile/settings
   *
   * Returns the current user's profile settings, sourced from their personal
   * organization record (pay frequency, timezone, currency, etc.).
   */
  #[Route('profile/settings', ['GET'])]
  /**
   * Handles getProfileSettings operation.
   */
  public function getProfileSettings(): void
  {
    $service = new OrganizationDiscoveryService();
    $personal = $service->ensurePersonalOrganization(User::currentUUID());
    if ($personal['success'] !== true) {
      $message = $personal['message'] !== ''
        ? $personal['message']
        : 'Unable to load profile settings.';
      Response::error('[SC] ' . $message, [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $personalData = $personal['data'];
    $orgIdRaw = $personalData['organization_id'] ?? '';
    $orgId = is_scalar($orgIdRaw) ? (string) $orgIdRaw : '';
    if ($orgId === '') {
      Response::error('[SC] Personal organization id is missing.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $result = $service->getOrganizationSettings(User::currentUUID(), $orgId);
    if ($result['success']) {
      Response::success('[SC] Profile settings retrieved.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[SC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST profile/settings/update
   *
   * Persists updated profile settings (pay frequency, anchor date, wage, timezone,
   * etc.) against the current user's personal organization.
   */
  #[Route('profile/settings/update', ['POST'])]
  /**
   * Handles updateProfileSettings operation.
   */
  public function updateProfileSettings(): void
  {
    $allowedStrings = ['name', 'pay_frequency', 'pay_anchor', 'pay_period_start', 'pay_period_length', 'editing_grace_days', 'default_wage', 'timezone', 'currency', 'language', 'locale'];
    $filtered = RequestGuard::filterPost($allowedStrings, []);

    if (false === $filtered) {
      Response::error('[SC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $service = new OrganizationDiscoveryService();
    $personal = $service->ensurePersonalOrganization(User::currentUUID());
    if ($personal['success'] !== true) {
      $message = $personal['message'] !== ''
        ? $personal['message']
        : 'Unable to update profile settings.';
      Response::error('[SC] ' . $message, [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $personalData = $personal['data'];
    $orgIdRaw = $personalData['organization_id'] ?? '';
    $orgId = is_scalar($orgIdRaw) ? (string) $orgIdRaw : '';
    if ($orgId === '') {
      Response::error('[SC] Personal organization id is missing.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $result = $service->updateOrganizationSettings(User::currentUUID(), $orgId, $filtered);
    if ($result['success']) {
      Response::success('[SC] Profile settings updated.', $result['data'], HttpStatus::HTTP_OK);
    } else {
      Response::error('[SC] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * POST account/data/delete
   *
   * Permanently deletes all work, site, and earnings data for the authenticated
   * user.  Requires a matching CSRF token and the exact confirmation phrase.
   */
  #[Route('account/data/delete', ['POST'])]
  /**
   * Handles deleteAllData operation.
   */
  public function deleteAllData(): void
  {
    $mutationGate = RedisReliabilityService::allowMutations();
    if ($mutationGate['allowed'] !== true) {
      Response::error(
        '[Settings] Redis reliability guard blocked mutation.',
        ['redis_guard' => $mutationGate],
        HttpStatus::HTTP_SERVICE_UNAVAILABLE
      );

      return;
    }

    $userUUID = User::currentUUID();
    $user = UserRepository::getByUUID($userUUID);
    if (!$user) {
      Response::error('[SC] Unknown user.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
      return;
    }

    $csrfToken = InputSanitizer::postString('csrf_token');
    if (!$user->verifyFormNonce('settings', $csrfToken)) {
      Response::error('Invalid CSRF token.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $confirmPhrase = strtoupper(trim(InputSanitizer::postString('confirm_phrase')));

    if ($confirmPhrase !== 'DELETE ALL DATA') {
      Response::error('Type DELETE ALL DATA exactly to confirm.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    foreach (Database::scanKeys(Keys::WORK . ':' . $userUUID . ':*') as $key) {
      Database::unlink($key);
    }
    foreach (Database::scanKeys(Keys::WORK . ':archived:' . $userUUID . ':*') as $key) {
      Database::unlink($key);
    }

    foreach (Database::scanKeys(Keys::SITE . ':' . $userUUID . ':*') as $key) {
      Database::unlink($key);
    }

    foreach (Database::scanKeys(Keys::EARNING . ':' . $userUUID . ':*') as $key) {
      Database::unlink($key);
    }

    foreach (Database::scanKeys(Keys::LOCK_BOUNDARY . ':' . $userUUID . ':*') as $key) {
      Database::unlink($key);
    }
    Database::unlink(Keys::LOCK_BOUNDARY . ':' . $userUUID);

    Response::success('All account data deleted.', [], HttpStatus::HTTP_OK);
  }

  /**
   * POST account/data/export
   *
   * Export the authenticated user's portable account dataset containing
   * profile-safe user fields, sites, and work entries.
   */
  #[Route('account/data/export', ['POST'])]
  /**
   * Handles exportAccountData operation.
   */
  public function exportAccountData(): void
  {
    Authentication::abortIfUnauthenticated();

    $userUUID = User::currentUUID();
    $user = UserRepository::getByUUID($userUUID);
    if (!$user) {
      Response::error('[SC] Unknown user.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
      return;
    }

    $sites = [];
    foreach (\PayCal\Domain\Sites::getSites($userUUID, 'all') as $siteId => $siteData) {
      $sites[] = [
        'id' => $this->scalarString($siteId),
        'site_name' => $this->scalarString($siteData['site_name'] ?? ''),
        'wage' => $this->scalarString($siteData['wage'] ?? ''),
        'living_out_allowance' => $this->scalarString($siteData['living_out_allowance'] ?? ''),
        'travel_hours' => $this->scalarString($siteData['travel_hours'] ?? ''),
        'status' => $this->scalarString($siteData['status'] ?? 'active'),
        'province' => $this->scalarString($siteData['province'] ?? ''),
      ];
    }

    $workEntries = [];
    $start = new \DateTimeImmutable('1970-01-01');
    $end = new \DateTimeImmutable('2100-01-01');
    foreach (Work::getWorkInRange($start, $end, $userUUID) as $key => $workData) {
      $isArchived = str_starts_with($this->scalarString($key), Keys::WORK . ':archived:');
      $workEntries[] = [
        'date' => $this->scalarString($workData['date'] ?? ''),
        'site_id' => $this->scalarString($workData['site_id'] ?? ''),
        'site_name' => $this->scalarString($workData['site_name'] ?? ''),
        'hours' => $this->scalarString($workData['hours'] ?? ''),
        'regular_hours' => $this->scalarString($workData['regular_hours'] ?? ''),
        'overtime_hours' => $this->scalarString($workData['overtime_hours'] ?? ''),
        'living_out_allowance' => $this->scalarString($workData['living_out_allowance'] ?? ''),
        'travel_hours' => $this->scalarString($workData['travel_hours'] ?? ''),
        'wage' => $this->scalarString($workData['wage'] ?? ''),
        'gross' => isset($workData['gross']) ? $this->scalarString($workData['gross']) : '',
        'tax' => isset($workData['tax']) ? $this->scalarString($workData['tax']) : '',
        'net' => isset($workData['net']) ? $this->scalarString($workData['net']) : '',
        'other' => isset($workData['other']) ? $this->scalarString($workData['other']) : '',
        'encrypted_blob' => $this->scalarString($workData['encrypted_blob'] ?? ''),
        'archived' => $isArchived,
      ];
    }

    $payload = [
      'schema_version' => self::DATA_TRANSFER_SCHEMA_VERSION,
      'exported_at' => gmdate('c'),
      'reference' => $this->generateReferenceCode('EXU'),
      'security' => [
        'contains_plaintext' => true,
        'warning' => self::DATA_EXPORT_PLAINTEXT_WARNING,
      ],
      'user' => $this->buildPortableUserData($user),
      'sites' => $sites,
      'work_entries' => $workEntries,
    ];

    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($payloadJson)) {
      Response::error('[SC] Unable to encode export payload.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
      return;
    }

    SecurityLog::log('account_data_export', [
      'user_uuid' => $userUUID,
      'schema_version' => self::DATA_TRANSFER_SCHEMA_VERSION,
      'contains_plaintext' => true,
      'site_count' => count($sites),
      'work_entry_count' => count($workEntries),
    ]);

    Response::success('[SC] Account data export prepared.', [
      'schema_version' => self::DATA_TRANSFER_SCHEMA_VERSION,
      'reference' => $payload['reference'],
      'contains_plaintext' => true,
      'warning' => self::DATA_EXPORT_PLAINTEXT_WARNING,
      'checksum_sha256' => hash('sha256', $payloadJson),
      'counts' => [
        'sites' => count($sites),
        'work_entries' => count($workEntries),
      ],
      'payload' => $payload,
    ], HttpStatus::HTTP_OK);
  }

  /**
   * POST account/data/import/prepare
   *
   * Validate an uploaded export payload and stage it for commit.
   */
  #[Route('account/data/import/prepare', ['POST'])]
  /**
   * Handles prepareAccountDataImport operation.
   */
  public function prepareAccountDataImport(): void
  {
    Authentication::abortIfUnauthenticated();

    $allowedStrings = ['payload_json'];
    $droppedKeys = [];
    $base64ImageStrings = [];
    $rawStrings = ['payload_json'];
    $filtered = RequestGuard::filterPost($allowedStrings, [], $droppedKeys, $base64ImageStrings, $rawStrings);
    if (false === $filtered) {
      Response::error('[SC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $payloadRaw = $filtered['payload_json'] ?? '';
    $payloadJson = is_scalar($payloadRaw) ? trim((string) $payloadRaw) : '';
    if ($payloadJson === '') {
      Response::error('[SC] Import payload is required.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    try {
      $decoded = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
      Response::error('[SC] Invalid import JSON payload.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if (!is_array($decoded)) {
      Response::error('[SC] Import payload must be a JSON object.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $schemaVersion = $this->scalarInt($decoded['schema_version'] ?? 0);
    if ($schemaVersion !== self::DATA_TRANSFER_SCHEMA_VERSION) {
      Response::error('[SC] Unsupported import schema version.', [
        'supported_schema_version' => self::DATA_TRANSFER_SCHEMA_VERSION,
        'received_schema_version' => $schemaVersion,
      ], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $sites = $decoded['sites'] ?? [];
    $workEntries = $decoded['work_entries'] ?? [];
    $userData = $decoded['user'] ?? [];
    if (!is_array($sites) || !is_array($workEntries) || !is_array($userData)) {
      Response::error('[SC] Import payload shape is invalid.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $importId = $this->generateReferenceCode('IMP');
    $importKey = $this->importSessionKey($importId);
    Database::hsetex($importKey, [
      'actor_uuid' => User::currentUUID(),
      'created_at' => (string) time(),
      'schema_version' => (string) $schemaVersion,
      'checksum_sha256' => hash('sha256', $payloadJson),
      'payload_json' => $payloadJson,
    ], self::DATA_IMPORT_TTL_SECONDS);

    SecurityLog::log('account_data_import_prepared', [
      'user_uuid' => User::currentUUID(),
      'import_id' => $importId,
      'schema_version' => $schemaVersion,
      'site_count' => count($sites),
      'work_entry_count' => count($workEntries),
    ]);

    Response::success('[SC] Account data import prepared.', [
      'import_id' => $importId,
      'expires_in_seconds' => self::DATA_IMPORT_TTL_SECONDS,
      'checksum_sha256' => hash('sha256', $payloadJson),
      'counts' => [
        'sites' => count($sites),
        'work_entries' => count($workEntries),
      ],
    ], HttpStatus::HTTP_OK);
  }

  /**
   * POST account/data/import/commit
   *
   * Commit a previously prepared account data import.
   */
  #[Route('account/data/import/commit', ['POST'])]
  /**
   * Handles commitAccountDataImport operation.
   */
  public function commitAccountDataImport(): void
  {
    Authentication::abortIfUnauthenticated();

    $mutationGate = RedisReliabilityService::allowMutations();
    if ($mutationGate['allowed'] !== true) {
      Response::error(
        '[Settings] Redis reliability guard blocked mutation.',
        ['redis_guard' => $mutationGate],
        HttpStatus::HTTP_SERVICE_UNAVAILABLE
      );
      return;
    }

    $allowedStrings = ['import_id'];
    $filtered = RequestGuard::filterPost($allowedStrings, []);
    if (false === $filtered) {
      Response::error('[SC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $importIdRaw = $filtered['import_id'] ?? '';
    $importId = is_scalar($importIdRaw) ? trim((string) $importIdRaw) : '';
    if ($importId === '') {
      Response::error('[SC] Import id is required.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $importKey = $this->importSessionKey($importId);
    $session = Database::hgetall($importKey);
    if (empty($session)) {
      Response::error('[SC] Import session not found or expired.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $actorUUID = User::currentUUID();
    if ((string) ($session['actor_uuid'] ?? '') !== $actorUUID) {
      Response::error('[SC] Import session does not belong to this user.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $payloadJson = (string) ($session['payload_json'] ?? '');
    if ($payloadJson === '') {
      Response::error('[SC] Import payload is missing from the session.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    try {
      $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
      Response::error('[SC] Import payload decoding failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if (!is_array($payload)) {
      Response::error('[SC] Import payload is malformed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $importedUsers = 0;
    $importedSites = 0;
    $importedWorkEntries = 0;

    $userPayloadRaw = $payload['user'] ?? [];
    $userPayload = is_array($userPayloadRaw) ? $userPayloadRaw : [];
    $userWrite = $this->sanitizePortableUserImport($userPayload);
    if ($userWrite !== []) {
      Database::hset(Keys::USER . ':' . $actorUUID, $userWrite);
      $importedUsers = 1;
    }

    $sitesRaw = $payload['sites'] ?? [];
    $sites = is_array($sitesRaw) ? $sitesRaw : [];
    foreach ($sites as $siteRow) {
      if (!is_array($siteRow)) {
        continue;
      }
      $siteId = trim($this->scalarString($siteRow['id'] ?? ''));
      if ($siteId === '') {
        continue;
      }

      $siteKey = Keys::SITE . ':' . $actorUUID . ':' . $siteId;
      $write = [
        'site_name' => trim($this->scalarString($siteRow['site_name'] ?? '')),
        'wage' => trim($this->scalarString($siteRow['wage'] ?? '')),
        'living_out_allowance' => trim($this->scalarString($siteRow['living_out_allowance'] ?? '')),
        'travel_hours' => trim($this->scalarString($siteRow['travel_hours'] ?? '')),
        'status' => trim($this->scalarString($siteRow['status'] ?? 'active')),
      ];
      $province = trim($this->scalarString($siteRow['province'] ?? ''));
      if ($province !== '') {
        $write['province'] = $province;
      }

      Database::hset($siteKey, $write);
      $importedSites += 1;
    }

    $workEntriesRaw = $payload['work_entries'] ?? [];
    $workEntries = is_array($workEntriesRaw) ? $workEntriesRaw : [];
    foreach ($workEntries as $workRow) {
      if (!is_array($workRow)) {
        continue;
      }

      $date = trim($this->scalarString($workRow['date'] ?? ''));
      $siteId = trim($this->scalarString($workRow['site_id'] ?? ''));
      if ($date === '' || $siteId === '') {
        continue;
      }

      $isArchived = (bool) ($workRow['archived'] ?? false);
      $workKeyPrefix = $isArchived
        ? (Keys::WORK . ':archived:' . $actorUUID)
        : (Keys::WORK . ':' . $actorUUID);
      $workKey = $workKeyPrefix . ':' . $date . ':' . $siteId;

      $write = [
        'date' => $date,
        'site_id' => $siteId,
        'site_name' => trim($this->scalarString($workRow['site_name'] ?? '')),
        'hours' => trim($this->scalarString($workRow['hours'] ?? '')),
        'regular_hours' => trim($this->scalarString($workRow['regular_hours'] ?? '')),
        'overtime_hours' => trim($this->scalarString($workRow['overtime_hours'] ?? '')),
        'living_out_allowance' => trim($this->scalarString($workRow['living_out_allowance'] ?? '')),
        'travel_hours' => trim($this->scalarString($workRow['travel_hours'] ?? '')),
        'wage' => trim($this->scalarString($workRow['wage'] ?? '')),
      ];

      foreach (['gross', 'tax', 'net', 'other', 'encrypted_blob'] as $optionalField) {
        $value = trim($this->scalarString($workRow[$optionalField] ?? ''));
        if ($value !== '') {
          $write[$optionalField] = $value;
        }
      }

      Database::hset($workKey, $write);
      $importedWorkEntries += 1;
    }

    Database::unlink($importKey);

    SecurityLog::log('account_data_import_committed', [
      'user_uuid' => $actorUUID,
      'import_id' => $importId,
      'imported_user' => $importedUsers,
      'imported_sites' => $importedSites,
      'imported_work_entries' => $importedWorkEntries,
    ]);

    Response::success('[SC] Account data import committed.', [
      'import_id' => $importId,
      'counts' => [
        'user' => $importedUsers,
        'sites' => $importedSites,
        'work_entries' => $importedWorkEntries,
      ],
    ], HttpStatus::HTTP_OK);
  }

  /**
   * @return array<string, mixed>
   */
  private function buildPortableUserData(User $user): array
  {
    return [
      'full_name' => (string) $user->full_name,
      'phone' => (string) $user->phone,
      'province' => (string) $user->province,
      'timezone' => (string) $user->timezone,
      'currency' => (string) $user->currency,
      'language' => (string) $user->language,
      'locale' => (string) ($user->locale !== '' ? $user->locale : 'en-CA'),
      'pay_frequency' => (string) ($user->pay_frequency ?? ''),
      'pay_anchor' => (string) ($user->pay_anchor ?? ''),
      'pay_period_start' => (string) $user->pay_period_start,
      'pay_period_length' => (string) $user->pay_period_length,
      'default_site_id' => (string) $user->default_site_id,
      'default_hours' => (string) $user->default_hours,
      'default_living_out_allowance' => (string) $user->default_living_out_allowance,
      'default_travel_hours' => (string) $user->default_travel_hours,
      'theme' => (string) $user->theme,
      'variant' => (string) $user->variant,
      'text' => (string) $user->text,
      'spacing' => (string) $user->spacing,
      'dyslexia_typography' => (string) $user->dyslexia_typography,
      'voice' => (string) $user->voice,
      'audio_feedback' => (string) $user->audio_feedback,
      'editing_grace_days' => (string) $user->editing_grace_days,
      'calendar_work_entry_fields_hours' => $user->calendar_work_entry_fields_hours,
      'calendar_work_entry_fields_overtime' => $user->calendar_work_entry_fields_overtime,
      'calendar_work_entry_fields_living_out' => $user->calendar_work_entry_fields_living_out,
      'calendar_work_entry_fields_travel' => $user->calendar_work_entry_fields_travel,
    ];
  }

  /**
   * @param array<string, mixed> $userPayload
   * @return array<string, string>
   */
  private function sanitizePortableUserImport(array $userPayload): array
  {
    $allowList = [
      'full_name',
      'phone',
      'province',
      'timezone',
      'currency',
      'language',
      'locale',
      'pay_frequency',
      'pay_anchor',
      'pay_period_start',
      'pay_period_length',
      'default_site_id',
      'default_hours',
      'default_living_out_allowance',
      'default_travel_hours',
      'theme',
      'variant',
      'text',
      'spacing',
      'dyslexia_typography',
      'voice',
      'audio_feedback',
      'editing_grace_days',
      'calendar_work_entry_fields_hours',
      'calendar_work_entry_fields_overtime',
      'calendar_work_entry_fields_living_out',
      'calendar_work_entry_fields_travel',
    ];

    $write = [];
    foreach ($allowList as $field) {
      if (!array_key_exists($field, $userPayload)) {
        continue;
      }

      $value = $userPayload[$field];
      if (in_array($field, [
        'calendar_work_entry_fields_hours',
        'calendar_work_entry_fields_overtime',
        'calendar_work_entry_fields_living_out',
        'calendar_work_entry_fields_travel',
      ], true)) {
        $write[$field] = (bool) $value ? '1' : '0';
      } else {
        $write[$field] = trim($this->scalarString($value));
      }
    }

    return $write;
  }

  private function scalarString(mixed $value): string
  {
    return is_scalar($value) ? (string) $value : '';
  }

  private function scalarInt(mixed $value, int $default = 0): int
  {
    if (is_int($value)) {
      return $value;
    }

    if (is_float($value) || is_string($value)) {
      return (int) $value;
    }

    return $default;
  }

  private function generateReferenceCode(string $prefix): string
  {
    return $prefix . substr(hash('sha256', User::currentUUID() . '|' . microtime(true) . '|' . random_int(1000, 9999)), 0, 24);
  }

  private function importSessionKey(string $importId): string
  {
    return 'user:data_import:prepare:' . $importId;
  }

  /**
   * POST account/delete
   *
   * Permanently deletes the authenticated user's account and all associated data
   * (work, sites, earnings, sessions, passkeys, email index).  Requires the exact
   * confirmation phrase before proceeding.  Clears the auth cookie and redirects
   * to the sign-in screen.
   */
  #[Route('account/delete', ['POST'])]
  /**
   * Handles deleteAccount operation.
   */
  public function deleteAccount(): void
  {
    $mutationGate = RedisReliabilityService::allowMutations();
    if ($mutationGate['allowed'] !== true) {
      Response::error(
        '[Settings] Redis reliability guard blocked mutation.',
        ['redis_guard' => $mutationGate],
        HttpStatus::HTTP_SERVICE_UNAVAILABLE
      );

      return;
    }

    $userUUID = User::currentUUID();
    $confirmPhrase = strtoupper(trim(InputSanitizer::postString('confirm_phrase')));
    $userKey = Keys::USER . ':' . $userUUID;
    $userData = Database::hgetall($userKey);
    $email = InputSanitizer::sanitizeEmail((string) ($userData['email'] ?? ''));
    $newEmail = InputSanitizer::sanitizeEmail((string) ($userData['new_email'] ?? ''));

    if ($confirmPhrase !== 'DELETE MY ACCOUNT') {
      Response::error('Type DELETE MY ACCOUNT exactly to confirm account deletion.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    // Remove all work entries for user (active + archived)
    foreach (Database::scanKeys(Keys::WORK . ':' . $userUUID . ':*') as $key) {
      Database::unlink($key);
    }
    foreach (Database::scanKeys(Keys::WORK . ':archived:' . $userUUID . ':*') as $key) {
      Database::unlink($key);
    }

    // Remove all site records for user
    foreach (Database::scanKeys(Keys::SITE . ':' . $userUUID . ':*') as $key) {
      Database::unlink($key);
    }

    // Remove earnings/cache records for user if present
    foreach (Database::scanKeys(Keys::EARNING . ':' . $userUUID . ':*') as $key) {
      Database::unlink($key);
    }
    foreach (Database::scanKeys(Keys::LOCK_BOUNDARY . ':' . $userUUID . ':*') as $key) {
      Database::unlink($key);
    }
    Database::unlink(Keys::LOCK_BOUNDARY . ':' . $userUUID);
    Database::unlink(Keys::VERIFICATION_CODES . ':' . $userUUID);

    // Remove email index records (current + pending email; legacy and current key formats)
    if ($email !== '') {
      Database::unlink(Keys::EMAIL . ':' . $email);
      Database::unlink(Keys::EMAIL . $email);
    }
    if ($newEmail !== '') {
      Database::unlink(Keys::EMAIL . ':' . $newEmail);
      Database::unlink(Keys::EMAIL . $newEmail);
    }

    // Remove passkey wrappers and credential records
    $credentialSetKey = Keys::webauthnUserCredentials($userUUID);
    foreach (Database::smembers($credentialSetKey) as $credentialRaw) {
      $credentialId = (string) $credentialRaw;
      if ($credentialId === '') {
        continue;
      }
      Database::unlink(Keys::webauthnCredential($credentialId));
    }
    Database::unlink($credentialSetKey);
    Database::unlink(Keys::USER . ':' . $userUUID . ':passkey_wrapped_deks');

    // Remove nonce keys and user record
    Database::unlink(Keys::USER . ':' . $userUUID . ':nonce');
    foreach (Database::scanKeys(Keys::USER . ':' . $userUUID . ':csrf:*') as $key) {
      Database::unlink($key);
    }
    Database::unlink($userKey);

    // Remove all sessions belonging to this user
    foreach (Database::scanKeys(Keys::SESSION . ':*') as $sessionKey) {
      $sessionUserUUID = (string) Database::hget($sessionKey, 'user_uuid');
      if ($sessionUserUUID === $userUUID) {
        Database::unlink($sessionKey);
      }
    }

    // Final defensive sweep: remove any key that still embeds this UUID.
    $uuidSweepPatterns = [
      '*' . $userUUID . ':*',
      '*:' . $userUUID . '*',
      $userUUID . '*',
    ];
    foreach ($uuidSweepPatterns as $pattern) {
      foreach (Database::scanKeys($pattern) as $key) {
        Database::unlink($key);
      }
    }

    // Clear auth cookie and redirect to auth screen
    $cookieDomain = parse_url((string) Environment::appPublicURL(), PHP_URL_HOST);
    $cookieDomain = is_string($cookieDomain) ? $cookieDomain : '';

    $cookieParams = [
      'expires' => time() - FormTTL::ONE_HOUR->value,
      'path' => '/',
      'domain' => $cookieDomain,
      'secure' => (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS']),
      'httponly' => true,
      'samesite' => 'Lax',
    ];
    setcookie('PAYCAL_AUTH', '', $cookieParams);
    unset($_COOKIE['PAYCAL_AUTH']);

    header('Location: ' . Environment::appURL('auth/?account_deleted=1'), true, 302);
    exit;
  }

  /**
   * Handles:
   *   /v1/api/account/info/update
   *   /v1/api/settings/style/update
   *   /v1/api/settings/audio/update
   *   /v1/api/settings/calendar/update
   *   /v1/api/settings/pay_period/update
   *
   * All of these update *user settings*, so the allow-list is user settings only.
   */
  #[Route('account/info/update', ['POST'])]
  #[Route('account/personal/update', ['POST'])] // Legacy alias retained for graceful fallback submits
  #[Route('account/security/update', ['POST'])]
  #[Route('account/audio/update', ['POST'])] // Legacy alias retained for graceful fallback submits
  #[Route('account/pay_period/update', ['POST'])] // Legacy alias retained for graceful fallback submits
  #[Route('settings/audio/update', ['POST'])]
  #[Route('settings/calendar/update', ['POST'])]
  #[Route('settings/debug/update', ['POST'])]
  #[Route('settings/pay_period/update', ['POST'])]
  #[Route('settings/style/update', ['POST'])]
  #[Route('profile/update', ['POST'])] // Canonical profile-page route
  /**
   * Handles updateSettings operation.
   */
  public function updateSettings(): void
  {
      // Boot Lens for diagnostics
      $lens_context = 'settings/update';
      try {
        $requestUri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
          ? $_SERVER['REQUEST_URI']
          : '/unknown';
        if (strpos($requestUri, 'profile') !== false) {
          $lens_context = 'profile/update';
        }
      } catch (\Throwable $_) {
        // Ignore
      }
      Lens::boot($lens_context);

    $rawPayPeriodDebug = [
      'pay_period_start' => InputSanitizer::postString('pay_period_start'),
      'pay_frequency' => InputSanitizer::postString('pay_frequency'),
      'pay_anchor' => InputSanitizer::postString('pay_anchor'),
      'editing_grace_days' => InputSanitizer::postString('editing_grace_days'),
      'pay_period_length' => InputSanitizer::postString('pay_period_length'),
      'pay_epoch' => InputSanitizer::postString('pay_epoch'),
    ];
    $hasPayPeriodInput = false;
    foreach ($rawPayPeriodDebug as $value) {
      if ('' !== $value) {
        $hasPayPeriodInput = true;
        break;
      }
    }
    if ($hasPayPeriodInput) {
      Log::debug('[PAYPERIOD_DEBUG] raw_post=' . json_encode($rawPayPeriodDebug));
    }

    // DEBUG: Log incoming POST data for work entry fields
    $debugHours = InputSanitizer::postString('calendar_work_entry_fields_hours');
    if ('' !== $debugHours) {
      $debugData = [
          'hours' => $debugHours,
          'overtime' => InputSanitizer::postString('calendar_work_entry_fields_overtime') ?: 'MISSING',
          'living_out' => InputSanitizer::postString('calendar_work_entry_fields_living_out') ?: 'MISSING',
          'travel' => InputSanitizer::postString('calendar_work_entry_fields_travel') ?: 'MISSING',
      ];
      Log::debug('[SC] POST received: '.json_encode($debugData));
    }

    // Define what we allow for user settings
    $allowedStrings = UserSettings::allowedStrings();
    $allowedStrings[] = 'variant';

    // (User settings do not include arrays)
    $allowedArrays = [];

    // Filter + sanitize + auth-check
    $droppedKeys = [];
    $filtered = RequestGuard::filterPost($allowedStrings, $allowedArrays, $droppedKeys);

    if (false === $filtered) {
      \PayCal\Domain\Response::error('[SC] RequestGuard failed.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_INTERNAL_SERVER_ERROR);

      return;
    }

    $filtered = self::normalizeThemeVariant($filtered);
    $filtered = self::normalizeStylePreferences($filtered);
    $filtered = self::normalizeCalendarPreferences($filtered);
    $filtered = self::normalizeNavigationPreferences($filtered);
    $filtered = self::normalizePayPeriodPreferences($filtered);
    $filtered = self::normalizeAccountInfoPreferences($filtered);

    $normalizedPayPeriodDebug = [];
    foreach (['pay_period_start', 'pay_frequency', 'pay_anchor', 'editing_grace_days', 'pay_period_length', 'pay_epoch'] as $field) {
      if (isset($filtered[$field])) {
        $normalizedPayPeriodDebug[$field] = $filtered[$field];
      }
    }
    if (!empty($normalizedPayPeriodDebug)) {
      Log::debug('[PAYPERIOD_DEBUG] normalized=' . json_encode($normalizedPayPeriodDebug));
    }

    // DEBUG: Log filtered data
    if (isset($filtered['calendar_work_entry_fields_hours'])) {
      $debugFiltered = [
          'hours' => $filtered['calendar_work_entry_fields_hours'],
          'overtime' => $filtered['calendar_work_entry_fields_overtime'] ?? 'MISSING',
          'living_out' => $filtered['calendar_work_entry_fields_living_out'] ?? 'MISSING',
          'travel' => $filtered['calendar_work_entry_fields_travel'] ?? 'MISSING',
      ];
      Log::debug('[SC] Filtered: '.json_encode($debugFiltered));
    }

    /** @var array<string, string> $filtered */
    $user = UserRepository::getByUUID(User::currentUUID());
    if (!$user) {
      \PayCal\Domain\Response::error('[SC] Unknown user.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_INTERNAL_SERVER_ERROR);

      return;
    }

    // CSRF verification
    $csrfToken = InputSanitizer::postString('csrf_token');
    if (!$user->verifyFormNonce('settings', $csrfToken)) {
      \PayCal\Domain\Response::error('Invalid CSRF token.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_FORBIDDEN);

      return;
    }

    $ok = $user->updateSettings($filtered);

    $payPeriodFields = ['pay_period_start', 'pay_frequency', 'pay_anchor', 'pay_period_length', 'editing_grace_days'];
    $payPeriodRegenerationFields = ['pay_period_start', 'pay_frequency', 'pay_anchor', 'pay_period_length'];
    $payPeriodUpdated = false;
    $payPeriodRequiresRegeneration = false;
    $payPeriodChangedFields = [];
    foreach ($payPeriodFields as $field) {
      if (!isset($filtered[$field])) {
        continue;
      }

      $incoming = (string) $filtered[$field];
      $current = isset($user->{$field}) ? (string) ($user->{$field} ?? '') : '';
      if ($incoming !== $current) {
        $payPeriodUpdated = true;
        $payPeriodChangedFields[] = $field;
        if (in_array($field, $payPeriodRegenerationFields, true)) {
          $payPeriodRequiresRegeneration = true;
        }
      }
    }

    $sideEffects = [
      'pay_period_regenerated' => false,
      'work_entry_lock_cache_cleared' => false,
      'pay_period_side_effect_error' => '',
    ];

    $persistedUser = UserRepository::getByUUID($user->user_uuid);

    if ($ok && $payPeriodUpdated && $persistedUser) {
      if ($payPeriodRequiresRegeneration) {
        try {
          PayPeriodGenerator::regenerateForUser($persistedUser);
          $sideEffects['pay_period_regenerated'] = true;
        } catch (\Throwable $e) {
          $sideEffects['pay_period_side_effect_error'] = $e->getMessage();
          Log::error('[SC] Pay period side effect failure: ' . $e->getMessage());
        }
      }

      try {
        $sideEffects['work_entry_lock_cache_cleared'] = WorkEntryLockService::clearCache($persistedUser->user_uuid);
      } catch (\Throwable $e) {
        $sideEffects['pay_period_side_effect_error'] = trim(
          ($sideEffects['pay_period_side_effect_error'] === '' ? '' : $sideEffects['pay_period_side_effect_error'] . ' | ')
          . 'lock_cache_clear: ' . $e->getMessage()
        );
        Log::error('[SC] Lock cache clear failed: ' . $e->getMessage());
      }

      $savedPayPeriodRaw = Database::hgetall(Keys::USER . ':' . $persistedUser->user_uuid);
      $savedPayPeriodDebug = [];
      foreach (['pay_period_start', 'pay_frequency', 'pay_anchor', 'editing_grace_days', 'pay_period_length', 'pay_epoch'] as $field) {
        $savedPayPeriodDebug[$field] = (string) ($savedPayPeriodRaw[$field] ?? '');
      }
      Log::debug('[PAYPERIOD_DEBUG] saved=' . json_encode($savedPayPeriodDebug));
    }

    if ($hasPayPeriodInput || !empty($normalizedPayPeriodDebug)) {
      Log::debug('[PAYPERIOD_DEBUG] outcome=' . json_encode([
        'ok' => $ok,
        'pay_period_updated' => $payPeriodUpdated,
        'pay_period_requires_regeneration' => $payPeriodRequiresRegeneration,
        'pay_period_changed_fields' => $payPeriodChangedFields,
      ]));
    }

    // DEBUG: Log what got saved
    if ($ok && isset($filtered['calendar_work_entry_fields_hours'])) {
      $saved = Database::hgetall(Keys::USER.':'.$user->user_uuid);
      $debugSaved = [
          'hours' => $saved['calendar_work_entry_fields_hours'] ?? 'MISSING',
          'overtime' => $saved['calendar_work_entry_fields_overtime'] ?? 'MISSING',
          'living_out' => $saved['calendar_work_entry_fields_living_out'] ?? 'MISSING',
          'travel' => $saved['calendar_work_entry_fields_travel'] ?? 'MISSING',
      ];
      Log::debug('[SC] Saved to Redis: '.json_encode($debugSaved));
    }

    if (!$ok) {
      \PayCal\Domain\Response::error('[SC] Update failed.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
      return;
    }

    // Refresh the nonce TTL for subsequent requests
    $timeout = $user->getSessionTimeoutSeconds();
    $ttl = (int) min((int) SessionTimeout::TWO_HOURS->value, max((int) SessionTimeout::ONE_MIN->value, $timeout));
    $key = Keys::USER.':'.$user->user_uuid.':nonce';
    if (Database::exists($key)) {
      Database::expire($key, $ttl);
    }

    // Return updated lock boundary if grace days changed (prevents stale cache)
    $responseData = [];
    if ($payPeriodUpdated) {
      try {
        $responseData['lockBoundary'] = WorkEntryLockService::getLockBoundaryDate($user->user_uuid);
      } catch (\Throwable $e) {
        $sideEffects['pay_period_side_effect_error'] = trim(
          ($sideEffects['pay_period_side_effect_error'] === '' ? '' : $sideEffects['pay_period_side_effect_error'] . ' | ')
          . 'lock_boundary: ' . $e->getMessage()
        );
        Log::error('[SC] Lock boundary refresh failed: ' . $e->getMessage());
        Lens::add('Lock boundary refresh failed', ['error' => $e->getMessage()], 'error');
      }
    }

    $canonicalUser = UserRepository::getByUUID($user->user_uuid);
    $savedEcho = [];
    $canonicalEcho = [];
    foreach ($filtered as $key => $value) {
      $savedEcho[$key] = (string) $value;
      if ($canonicalUser && isset($canonicalUser->{$key})) {
        $canonicalValue = $canonicalUser->{$key};
        if (is_bool($canonicalValue)) {
          $canonicalEcho[$key] = $canonicalValue ? '1' : '0';
        } else {
          $canonicalEcho[$key] = is_scalar($canonicalValue) ? (string) $canonicalValue : '';
        }
      }
    }

    $responseData['saved'] = $savedEcho;
    $responseData['canonical'] = $canonicalEcho;
    $responseData['side_effects'] = $sideEffects;

    if (Environment::devSecurityDisabled()) {
      $responseData['dropped_fields'] = array_values(array_unique($droppedKeys));
    }

    Response::success('[SC] Update success.', $responseData, \PayCal\Domain\Enums\HttpStatus::HTTP_OK);
  }

  /**
   * Normalize incoming theme/variant fields with backward compatibility.
   *
   * @param array<string, string> $filtered
   * @return array<string, string>
   */
  /**
   * Normalize theme and variant values for backward compatibility.
   *
   * @param array<string, mixed> $filtered
   * @return array<string, mixed>
   */
  private static function normalizeThemeVariant(array $filtered): array
  {
    $allowedThemes = [
      'macos', 'macos9', 'system8', 'system7',
      'linux', 'mint', 'fedora', 'debian',
      'beos', 'zeta', 'haiku',
      'win10', 'win95', 'win98', 'winxp',
      'blade_runner', 'space_odyssey', 'tron', 'fifth_element', 'dune', 'matrix', 'alien', 'akira',
      'star_trek', 'star_wars', 'paycal_blue', 'paycal_black', 'paycal_red', 'paycal_green', 'paycal_white', 'paycal', 'retro', 'bluejeans', 'garden', 'arcade'
    ];
    $allowedVariants = ['light', 'dark'];

    // Do not inject defaults when this request is unrelated to theme settings.
    if (!isset($filtered['theme']) && !isset($filtered['variant'])) {
      return $filtered;
    }

    if (!isset($filtered['theme']) && isset($filtered['variant'])) {
      $filtered['theme'] = 'paycal';
    }

    if (isset($filtered['theme']) && !isset($filtered['variant'])) {
      $themeRaw = $filtered['theme'];
      $themeValue = is_scalar($themeRaw) ? (string) $themeRaw : '';
      if (preg_match('/^([a-zA-Z0-9_]+)_(dark|light)$/', $themeValue, $matches)) {
        $filtered['theme'] = $matches[1];
        $filtered['variant'] = $matches[2];
      } else {
        $filtered['variant'] = 'dark';
      }
    }

    if (isset($filtered['theme']) && $filtered['theme'] === '80s_retro') {
      $filtered['theme'] = 'retro';
    }

    if (isset($filtered['theme']) && $filtered['theme'] === 'sweater_weather') {
      $filtered['theme'] = 'garden';
    }

    if (isset($filtered['theme']) && $filtered['theme'] === 'denim_dream') {
      $filtered['theme'] = 'bluejeans';
    }

    // Back-compat: legacy "paycal" now points to the black variant family.
    if (isset($filtered['theme']) && $filtered['theme'] === 'paycal') {
      $filtered['theme'] = 'paycal_black';
    }

    if (isset($filtered['theme']) && !in_array($filtered['theme'], $allowedThemes, true)) {
      $filtered['theme'] = 'paycal_blue';
    }
    if (isset($filtered['variant']) && !in_array($filtered['variant'], $allowedVariants, true)) {
      $filtered['variant'] = 'dark';
    }

    return $filtered;
  }

  /**
   * Normalize style preferences.
   *
   * @param array<string, mixed> $filtered
   * @return array<string, mixed>
   */
  private static function normalizeStylePreferences(array $filtered): array
  {
    $normalizeSliderAdjustment = static function (mixed $raw, array $legacyMap, int $fallback = 0): string {
      $value = is_scalar($raw) ? strtolower(trim((string) $raw)) : '';
      if ($value === '') {
        return (string) max(-5, min(5, $fallback));
      }

      if (isset($legacyMap[$value])) {
        return (string) $legacyMap[$value];
      }

      if (preg_match('/^-?\d+$/', $value) === 1) {
        $numeric = (int) $value;

        return (string) max(-5, min(5, $numeric));
      }

      return (string) max(-5, min(5, $fallback));
    };

    if (isset($filtered['text'])) {
      $filtered['text'] = $normalizeSliderAdjustment($filtered['text'], [
        'small' => -2,
        'medium' => 0,
        'large' => 2,
        'x-large' => 5,
      ], 0);
    }

    if (isset($filtered['spacing'])) {
      $filtered['spacing'] = $normalizeSliderAdjustment($filtered['spacing'], [
        'tight' => -5,
        'compact' => -5,
        'comfy' => 0,
        'spacious' => 5,
        'zen' => 5,
      ], 0);
    }

    if (isset($filtered['dyslexia_typography'])) {
      $valueRaw = $filtered['dyslexia_typography'];
      $value = is_scalar($valueRaw) ? strtolower((string) $valueRaw) : '';
      $filtered['dyslexia_typography'] = in_array($value, ['off', 'on'], true)
        ? $value
        : UserPreferenceDefaults::DEFAULT_DYSLEXIA_TYPOGRAPHY;
    }

    return $filtered;
  }

  /**
   * Normalize calendar preference values for backward compatibility.
   *
   * @param array<string, mixed> $filtered
   * @return array<string, mixed>
   */
  private static function normalizeCalendarPreferences(array $filtered): array
  {
    if (isset($filtered['calendar_autofocus'])) {
      if ('current' === $filtered['calendar_autofocus']) {
        $filtered['calendar_autofocus'] = 'today';
      }
      if (!in_array($filtered['calendar_autofocus'], ['first', 'today', 'last'], true)) {
        $filtered['calendar_autofocus'] = 'today';
      }
    }

    if (isset($filtered['calendar_date_label_position'])) {
      if ('center' === $filtered['calendar_date_label_position']) {
        $filtered['calendar_date_label_position'] = 'middle';
      }
      if (!in_array($filtered['calendar_date_label_position'], ['left', 'middle', 'right'], true)) {
        $filtered['calendar_date_label_position'] = 'left';
      }
    }

    if (isset($filtered['calendar_work_entry_position'])) {
      if ('center' === $filtered['calendar_work_entry_position']) {
        $filtered['calendar_work_entry_position'] = 'middle';
      }
      if (!in_array($filtered['calendar_work_entry_position'], ['left', 'middle', 'right'], true)) {
        $filtered['calendar_work_entry_position'] = 'left';
      }
    }

    // Validate editing_grace_days range
    if (isset($filtered['editing_grace_days'])) {
      $graceRaw = $filtered['editing_grace_days'];
      $graceDays = is_numeric($graceRaw) ? (int) $graceRaw : 0;
      $minDays = (int) SystemLimits::get('editing_grace_days_min');
      $maxDays = (int) SystemLimits::get('editing_grace_days_max');
      
      if ($graceDays < $minDays || $graceDays > $maxDays) {
        $filtered['editing_grace_days'] = (string) max($minDays, min($maxDays, $graceDays));
      } else {
        $filtered['editing_grace_days'] = (string) $graceDays;
      }
    }

    return $filtered;
  }

  /**
   * Normalize navigation position preferences.
   *
   * @param array<string, mixed> $filtered
   * @return array<string, mixed>
   */
  private static function normalizeNavigationPreferences(array $filtered): array
  {
    $allowedPositions = ['left', 'right'];
    $allowedStates = ['collapsed', 'pinned'];

    if (isset($filtered['nav_position_primary'])) {
      $primaryRaw = $filtered['nav_position_primary'];
      $primary = is_scalar($primaryRaw) ? strtolower((string) $primaryRaw) : '';
      $filtered['nav_position_primary'] = in_array($primary, $allowedPositions, true)
        ? $primary
        : UserPreferenceDefaults::DEFAULT_NAV_POSITION_PRIMARY;
    }

    if (isset($filtered['nav_state_primary'])) {
      $stateRaw = $filtered['nav_state_primary'];
      $state = is_scalar($stateRaw) ? strtolower((string) $stateRaw) : '';
      $filtered['nav_state_primary'] = in_array($state, $allowedStates, true)
        ? $state
        : UserPreferenceDefaults::DEFAULT_NAV_STATE_PRIMARY;
    }

    return $filtered;
  }

  /**
   * Normalize pay period settings and keep legacy fields in sync.
   *
   * @param array<string, string> $filtered
   * @return array<string, string>
  /**
   * Normalize pay period preferences.
   *
   * @param array<string, mixed> $filtered
   * @return array<string, mixed>
   */
  /**
  * Validate and normalize account-info fields: timezone, currency, pay_rate, language, locale.
   *
   * @param array<string, mixed> $filtered
   * @return array<string, mixed>
   */
  private static function normalizeAccountInfoPreferences(array $filtered): array
  {
    if (isset($filtered['timezone'])) {
      $tz = is_scalar($filtered['timezone']) ? trim((string) $filtered['timezone']) : '';
      if ($tz === '' || !Timezone::isValid($tz)) {
        unset($filtered['timezone']);
      } else {
        $filtered['timezone'] = $tz;
      }
    }

    if (isset($filtered['currency'])) {
      $cur = is_scalar($filtered['currency']) ? strtoupper(trim((string) $filtered['currency'])) : '';
      if ($cur === '' || !Currency::isValid($cur)) {
        unset($filtered['currency']);
      } else {
        $filtered['currency'] = $cur;
      }
    }

    if (isset($filtered['pay_rate'])) {
      $rate = is_scalar($filtered['pay_rate']) ? trim((string) $filtered['pay_rate']) : '';
      if ($rate !== '' && !is_numeric($rate)) {
        unset($filtered['pay_rate']);
      } else {
        $filtered['pay_rate'] = $rate;
      }
    }

    if (isset($filtered['language'])) {
      $language = is_scalar($filtered['language']) ? strtolower(trim((string) $filtered['language'])) : '';
      if ($language === '' || !Language::isSupported($language)) {
        unset($filtered['language']);
      } else {
        $filtered['language'] = $language;
      }
    }

    if (isset($filtered['locale'])) {
      $locale = is_scalar($filtered['locale']) ? trim((string) $filtered['locale']) : '';
      if ($locale === '' || !in_array($locale, self::SUPPORTED_LOCALES, true)) {
        unset($filtered['locale']);
      } else {
        $filtered['locale'] = $locale;
      }
    }

    return $filtered;
  }

  /**
   * @param array<string, mixed> $filtered
   * @return array<string, mixed>
   */
  private static function normalizePayPeriodPreferences(array $filtered): array
  {
    if (isset($filtered['pay_frequency'])) {
      $allowed = [
        PayFrequency::WEEKLY->value,
        PayFrequency::BIWEEKLY->value,
        PayFrequency::SEMIMONTHLY->value,
        PayFrequency::MONTHLY->value,
      ];
      if (!in_array($filtered['pay_frequency'], $allowed, true)) {
        $filtered['pay_frequency'] = PayFrequency::BIWEEKLY->value;
      }
      $filtered['pay_period_length'] = match ($filtered['pay_frequency']) {
        PayFrequency::WEEKLY->value => '7',
        PayFrequency::BIWEEKLY->value => '14',
        PayFrequency::SEMIMONTHLY->value => '15',
        PayFrequency::MONTHLY->value => '30',
      };
    }

    if (isset($filtered['pay_anchor'])) {
      $allowedAnchors = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
      if (!in_array($filtered['pay_anchor'], $allowedAnchors, true)) {
        $filtered['pay_anchor'] = 'Monday';
      }
    }

    if (isset($filtered['pay_period_start'])) {
      $isValid = false;
      $payPeriodStartRaw = $filtered['pay_period_start'];
      $payPeriodStart = is_scalar($payPeriodStartRaw) ? (string) $payPeriodStartRaw : '';
      if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $payPeriodStart) === 1) {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $payPeriodStart);
        $isValid = false !== $dt && $dt->format('Y-m-d') === $payPeriodStart;
      }
      if (!$isValid) {
        // Do not clobber an existing saved start date when incoming input is invalid.
        unset($filtered['pay_period_start']);
      } else {
        $filtered['pay_epoch'] = $filtered['pay_period_start'];
      }
    }

    if (isset($filtered['pay_period_range'])) {
      $allowedRange = [
        'past_3_months',
        'past_6_months',
        'past_1_year',
        'past_2_years',
        'past_3_years',
        'past_4_years',
        'past_5_years',
        'all_time',
      ];
      if (!in_array($filtered['pay_period_range'], $allowedRange, true)) {
        $filtered['pay_period_range'] = 'past_6_months';
      }
    }

    return $filtered;
  }
}

