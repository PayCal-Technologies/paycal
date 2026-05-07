<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Infrastructure\Auth\CapabilityTokenService;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Authentication;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\Log;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\InvalidArgumentException;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Language;
use PayCal\Domain\Money;
use PayCal\Domain\RequestGuard;
use PayCal\Infrastructure\Resilience\RedisReliabilityService;
use PayCal\Domain\Response;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\TaxBracketCollection;
use PayCal\Domain\User;
use PayCal\Domain\WorkEntryRepository;
use PayCal\Domain\UserRepository;
use PayCal\Domain\UserSettings;
use PayCal\Domain\ValueError;

/**
 * AdminController.php
 *
 * Purpose: Administrative API layer for privileged maintenance actions,
 * system configuration changes, and internal operational tools.
 *
 * Developer notes:
 * - Admin endpoints carry broad blast radius; keep authorization and input
 *   validation strict and explicit.
 * - Prefer domain/service helpers for durable business rules so admin actions
 *   do not become alternate policy paths.
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
 * Administrative API surface.
 *
 * Responsibilities:
 * - Enforce admin-only access for maintenance and control-plane actions.
 * - Adapt admin requests into domain/service mutations.
 * - Return operational responses suitable for internal tooling and dashboards.
 */


class AdminController
{
  private bool $authorized = true;

  /** @var array<int, string> */
  private const CAPABILITY_ACTIONS = [
    'admin.redis.freeze',
    'admin.redis.breaker.open',
    'admin.redis.breaker.reset',
    'admin.user.update',
    'admin.user.delete',
    'admin.testing.create-orphaned-work',
    'admin.tests.run',
    'admin.languages.update',
    'admin.settings.update-invite',
    'admin.tax-brackets.federal',
    'admin.tax-brackets.provincial',
    'admin.limits.update',
    'admin.limits.reset',
    'admin.limits.reset-all',
  ];

  /**
   * Constructor. Validates authentication and admin-level access.
   * Sets $authorized to false if the user is not an admin, so all
   * subsequent capability checks can short-circuit cleanly.
   */
  public function __construct()
  {
    Authentication::abortIfUnauthenticated();

    if (!AdminSurface::userCanAccess()) {
      $this->authorized = false;
      return;
    }
  }

  /**
   * Updates user information from admin panel.
   */
  #[Route('admin/user/update', ['POST'])]
  /**
   * Handles updateUser operation.
   */
  public function updateUser(): void
  {
    if (!$this->requireCapability('admin.user.update')) {
      return;
    }

    $allowedStrings = array_values(array_unique(array_merge(
      UserSettings::allowedStrings(),
      ['user_uuid', 'full_name', 'email', 'auth_level', 'phone']
    )));
    $allowedArrays  = [];
    $filteredArray  = RequestGuard::filterPost($allowedStrings, $allowedArrays);
    if (false === $filteredArray) {
      Response::error('[AdminController] RequestGuard failed.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);

      return;
    }

    /** @var array<string, string> $filteredArray */
    $userUUID = (string) ($filteredArray['user_uuid'] ?? '');
    if (!$userUUID) {
      Response::error('User UUID required.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $user = UserRepository::getByUUID($userUUID);
    if (null === $user) {
      Response::error('User not found.', [], HttpStatus::HTTP_NOT_FOUND);

      return;
    }

    $fullName = InputSanitizer::sanitizeString((string) ($filteredArray['full_name'] ?? $user->full_name));
    $email = InputSanitizer::sanitizeString((string) ($filteredArray['email'] ?? $user->email));
    $authLevelStr = (string) ($filteredArray['auth_level'] ?? $user->auth_level->value);
    $phone = InputSanitizer::sanitizeString($filteredArray['phone'] ?? $user->phone);

    $lastSessionHash = $user->last_session_hash;

    // Validate and convert auth_level
    try {
      $authLevel = AuthLevel::from($authLevelStr);
    } catch (\ValueError $e) {
      Response::error('Invalid auth level: '.$authLevelStr, [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $actor = User::current();
    if (!self::canManageAuthLevelChange($actor->auth_level, $user->auth_level, $authLevel)) {
      Response::error('Only superadmin may modify privileged auth levels.', [], HttpStatus::HTTP_FORBIDDEN);

      return;
    }

    // Additional validation (e.g., email format)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      Response::error('Invalid email format.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    // Check if email is already in use by another user
    $existingUUID = UserRepository::getUUIDFromEmail($email);
    if ($existingUUID && $existingUUID !== $userUUID) {
      Response::error('Email already in use.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    // Update using User method
    $previousAuthLevel = $user->auth_level->value;
    UserRepository::setUser($userUUID, $user->password_hash, $email, $authLevel, $fullName, $lastSessionHash, $phone);

    if ($previousAuthLevel !== $authLevel->value) {
      \PayCal\Infrastructure\Audit\SystemAuditRepository::append('user.auth_level.changed', $actor->user_uuid, [
        'target_uuid'    => $userUUID,
        'previous_level' => $previousAuthLevel,
        'new_level'      => $authLevel->value,
      ]);
    }

    Response::success('User updated successfully.', [], HttpStatus::HTTP_OK);
  }

  /**
   * Handles canManageAuthLevelChange operation.
   */
  private static function canManageAuthLevelChange(
    AuthLevel $actorLevel,
    AuthLevel $targetCurrentLevel,
    AuthLevel $targetRequestedLevel
  ): bool {
    if ($actorLevel === AuthLevel::SUPERADMIN) {
      return true;
    }

    // Non-superadmin admins cannot manage admin/superadmin identities or assign privileged levels.
    if ($targetCurrentLevel->atLeast(AuthLevel::ADMIN)) {
      return false;
    }

    if ($targetRequestedLevel->atLeast(AuthLevel::ADMIN)) {
      return false;
    }

    return true;
  }

  /**
   * Delete a user account and all known account-linked records.
   */
  #[Route('admin/user/delete', ['POST'])]
  /**
   * Handles deleteUser operation.
   */
  public function deleteUser(): void
  {
    if (!$this->requireCapability('admin.user.delete')) {
      return;
    }

    $allowedStrings = ['user_uuid'];
    $allowedArrays = [];
    $filteredArray = RequestGuard::filterPost($allowedStrings, $allowedArrays);
    if (false === $filteredArray) {
      Response::error('[AdminController] RequestGuard failed.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);

      return;
    }

    /** @var array<string, string> $filteredArray */
    $userUUID = InputSanitizer::sanitizeString((string) ($filteredArray['user_uuid'] ?? ''));
    if ($userUUID === '') {
      Response::error('User UUID required.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    if ($userUUID === User::currentUUID()) {
      Response::error('Refusing to delete current admin session user.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $userKey = Keys::USER . ':' . $userUUID;
    $userData = Database::hgetall($userKey);
    if (empty($userData)) {
      Response::error('User not found.', [], HttpStatus::HTTP_NOT_FOUND);

      return;
    }

    $email = InputSanitizer::sanitizeEmail((string) ($userData['email'] ?? ''));
    $newEmail = InputSanitizer::sanitizeEmail((string) ($userData['new_email'] ?? ''));

    $deletedAuthLevel = (string) ($userData['auth_level'] ?? '');
    self::purgeUserData($userUUID, $email, $newEmail);

    \PayCal\Infrastructure\Audit\SystemAuditRepository::append('user.account.deleted', User::currentUUID(), [
      'target_uuid'  => $userUUID,
      'target_email' => $email,
      'auth_level'   => $deletedAuthLevel,
    ]);

    Response::success('User deleted successfully.', [], HttpStatus::HTTP_OK);
  }

  /**
   * Updates a language file with new content.
   */
  #[Route('admin/languages/update', ['POST'])]
  /**
   * Handles updateLanguages operation.
   */
  public function updateLanguages(): void
  {
    if (!$this->requireCapability('admin.languages.update')) {
      return;
    }

    // Only allow POST
    if ('POST' !== $_SERVER['REQUEST_METHOD']) {
      Response::error('Method not allowed.', [], HttpStatus::HTTP_METHOD_NOT_ALLOWED);

      return;
    }

    $allowedStrings = ['lang', 'content'];
    $allowedArrays = [];
    $filteredArray = RequestGuard::filterPost($allowedStrings, $allowedArrays);
    if (false === $filteredArray) {
      Response::error('RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    /** @var array<string, string> $filteredArray */
    $lang = $filteredArray['lang'] ?? '';
    $content = html_entity_decode(html_entity_decode($filteredArray['content'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Validate lang
    $validLangs = Language::getCodes();
    if (!Language::isSupported($lang)) {
      Response::error('Invalid language code.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    // File path
    $file = "/var/www/strings/{$lang}.txt";

    // Backup existing file
    if (file_exists($file)) {
      copy($file, $file.'.backup');
    }

    // Write new content
    if (false === file_put_contents($file, $content)) {
      Response::error('Failed to save language file.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);

      return;
    }

    // Reload Redis for this language
    $this->reloadLanguage($lang);

    \PayCal\Infrastructure\Audit\SystemAuditRepository::append('admin.languages.updated', User::currentUUID(), [
      'lang' => $lang,
    ]);

    Response::success('Language file updated successfully.', [], HttpStatus::HTTP_OK);
  }

  /**
   * Updates the registration invite code.
   */
  #[Route('admin/settings/update-invite', ['POST'])]
  /**
   * Handles updateInviteCode operation.
   */
  public function updateInviteCode(): void
  {
    if (!$this->requireCapability('admin.settings.update-invite')) {
      return;
    }

    // Only allow POST
    if ('POST' !== $_SERVER['REQUEST_METHOD']) {
      Response::error('Method not allowed.', [], HttpStatus::HTTP_METHOD_NOT_ALLOWED);

      return;
    }

    $allowedStrings = ['invite_code'];
    $allowedArrays = [];
    $filteredArray = RequestGuard::filterPost($allowedStrings, $allowedArrays);
    if (false === $filteredArray) {
      Response::error('RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    /** @var array<string, string> $filteredArray */
    $inviteCode = InputSanitizer::sanitizeString($filteredArray['invite_code'] ?? '');

    // Validate invite code (e.g., not empty, length)
    if (empty($inviteCode) || strlen($inviteCode) < 3) {
      Response::error('Invite code must be at least 3 characters.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    // Store in Redis
    Database::set(Keys::SYSTEM . ':invite_code', $inviteCode);

    \PayCal\Infrastructure\Audit\SystemAuditRepository::append('admin.settings.invite_code_updated', User::currentUUID(), [
      'invite_code_length' => (string) strlen($inviteCode),
    ]);

    Response::success('Invite code updated successfully.', [], HttpStatus::HTTP_OK);
  }

  /**
   * Updates federal tax brackets.
   */
  #[Route('admin/tax-brackets/federal', ['POST'])]
  /**
   * Handles updateFederalTaxBrackets operation.
   */
  public function updateFederalTaxBrackets(): void
  {
    if (!$this->requireCapability('admin.tax-brackets.federal')) {
      return;
    }

    // Only allow POST
    if ('POST' !== $_SERVER['REQUEST_METHOD']) {
      Response::error('Method not allowed.', [], HttpStatus::HTTP_METHOD_NOT_ALLOWED);

      return;
    }

    // Admin check
    if (!Authentication::validateAndTouchSession() || !User::isAdmin()) {
      Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    $brackets = [];
    $i = 0;
    while ('' !== InputSanitizer::postString("federal_min_{$i}")) {
      $min = (float) InputSanitizer::postString("federal_min_{$i}");
      $max = (float) InputSanitizer::postString("federal_max_{$i}");
      $rate = (float) InputSanitizer::postString("federal_rate_{$i}");

      if ($min < 0 || $max < $min || $rate < 0 || $rate > 1) {
        Response::error('Invalid bracket data.', [], HttpStatus::HTTP_BAD_REQUEST);

        return;
      }

      $minCents = Money::dollarsToCents((string) $min);
      $maxCents = Money::dollarsToCents((string) $max);
      $rateBasisPoints = (int) round($rate * 10000);
      $brackets[] = [$minCents, $maxCents, $rateBasisPoints];
      ++$i;
    }

    if (empty($brackets)) {
      Response::error('No brackets provided.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    // Validate brackets using TaxBracketCollection
    try {
      $collection = TaxBracketCollection::fromArrays($brackets);
      $encoded = json_encode($collection);
      if (false === $encoded) {
        Response::error('Failed to encode brackets.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);

        return;
      }
      // Save to Redis hash
      Database::hset(Keys::SYSTEM . ':tax_brackets', [
				'canada:federal' => $encoded
			]);
      \PayCal\Infrastructure\Audit\SystemAuditRepository::append('admin.tax_brackets.updated', User::currentUUID(), [
        'jurisdiction'  => 'canada:federal',
        'bracket_count' => (string) count($brackets),
      ]);
      Response::success('Federal tax brackets updated successfully.', [], HttpStatus::HTTP_OK);
    } catch (\InvalidArgumentException $e) {
      Response::error('Invalid bracket data: '.$e->getMessage(), [], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Updates provincial tax brackets for a specific province.
   */
  #[Route('admin/tax-brackets/provincial', ['POST'])]
  /**
   * Handles updateProvincialTaxBrackets operation.
   */
  public function updateProvincialTaxBrackets(): void
  {
    if (!$this->requireCapability('admin.tax-brackets.provincial')) {
      return;
    }

    // Only allow POST
    if ('POST' !== $_SERVER['REQUEST_METHOD']) {
      Response::error('Method not allowed.', [], HttpStatus::HTTP_METHOD_NOT_ALLOWED);

      return;
    }

    // Admin check
    if (!Authentication::validateAndTouchSession() || !User::isAdmin()) {
      Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    $province = InputSanitizer::postString('province');
    if (empty($province)) {
      Response::error('Province required.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    // Validate province
    $validProvinces = [
        'Alberta', 'British Columbia', 'Manitoba', 'New Brunswick', 'Newfoundland and Labrador',
        'Northwest Territories', 'Nova Scotia', 'Nunavut', 'Ontario', 'Prince Edward Island',
        'Quebec', 'Saskatchewan', 'Yukon',
    ];
    if (!in_array($province, $validProvinces, true)) {
      Response::error('Invalid province.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $brackets = [];
    $i = 0;
    while ('' !== InputSanitizer::postString("provincial_min_{$i}")) {
      $min = (float) InputSanitizer::postString("provincial_min_{$i}");
      $max = (float) InputSanitizer::postString("provincial_max_{$i}");
      $rate = (float) InputSanitizer::postString("provincial_rate_{$i}");

      if ($min < 0 || $max < $min || $rate < 0 || $rate > 1) {
        Response::error('Invalid bracket data.', [], HttpStatus::HTTP_BAD_REQUEST);

        return;
      }

      $minCents = Money::dollarsToCents((string) $min);
      $maxCents = Money::dollarsToCents((string) $max);
      $rateBasisPoints = (int) round($rate * 10000);
      $brackets[] = [$minCents, $maxCents, $rateBasisPoints];
      ++$i;
    }

    if (empty($brackets)) {
      Response::error('No brackets provided.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    // Validate brackets using TaxBracketCollection
    try {
      $collection = TaxBracketCollection::fromArrays($brackets);
      $encoded = json_encode($collection);
      if (false === $encoded) {
        Response::error('Failed to encode brackets.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);

        return;
      }
      // Save to Redis hash
      Database::hset(Keys::SYSTEM . ':tax_brackets', [
				"canada:{$province}" => $encoded
			]);
      \PayCal\Infrastructure\Audit\SystemAuditRepository::append('admin.tax_brackets.updated', User::currentUUID(), [
        'jurisdiction'  => 'canada:' . $province,
        'bracket_count' => (string) count($brackets),
      ]);
      Response::success("Provincial tax brackets for {$province} updated successfully.", [], HttpStatus::HTTP_OK);
    } catch (\InvalidArgumentException $e) {
      Response::error('Invalid bracket data: '.$e->getMessage(), [], HttpStatus::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Get all system limits (schema + current values).
   */
  #[Route('admin/limits/get', ['GET'])]
  /**
   * Handles getSystemLimits operation.
   */
  public function getSystemLimits(): void
  {
    $schema = SystemConfig::getSchema();
    $values = SystemConfig::getAll();

    $combined = [];
    foreach ($schema as $key => $spec) {
      $combined[$key] = [
          'key' => $key,
          'type' => $spec['type'],
          'label' => $spec['label'],
          'help' => $spec['help'],
          'default' => $spec['default'],
          'min' => $spec['min'] ?? null,
          'max' => $spec['max'] ?? null,
          'options' => $spec['options'] ?? null,
          'value' => $values[$key] ?? $spec['default'],
          'is_default' => ($values[$key] ?? $spec['default']) === $spec['default'],
      ];
    }

    Response::success('System limits retrieved', ['limits' => array_values($combined)], HttpStatus::HTTP_OK);
  }

  /**
   * Update a system limit.
   */
  #[Route('admin/limits/update', ['POST'])]
  /**
   * Handles updateSystemLimit operation.
   */
  public function updateSystemLimit(): void
  {
    if (!$this->requireCapability('admin.limits.update')) {
      return;
    }

    $allowedStrings = ['key', 'value'];
    $allowedArrays = [];
    $filteredArray = RequestGuard::filterPost($allowedStrings, $allowedArrays);

    if (false === $filteredArray) {
      Response::error('[AdminController] RequestGuard failed.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);

      return;
    }

    /** @var array<string, string> $filteredArray */
    $key = (string) ($filteredArray['key'] ?? '');
    $value = $filteredArray['value'] ?? '';

    if (!$key) {
      Response::error('Limit key required.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $result = SystemConfig::set($key, $value);

    if (!($result['success'] ?? false)) {
      $errorRaw = $result['error'] ?? 'Failed to update limit';
      $errorMessage = is_scalar($errorRaw) ? (string) $errorRaw : 'Failed to update limit';
      Response::error($errorMessage, [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $message = 'Limit updated successfully';

    /** @var array{success: true, value: bool|int|string, error?: string, warning?: string} $result */
    if (isset($result['warning'])) {
      $message .= ' ('.$result['warning'].')';
    }

    \PayCal\Infrastructure\Audit\SystemAuditRepository::append('admin.system_limit.updated', User::currentUUID(), [
      'key'   => $key,
      'value' => (string) $result['value'],
    ]);

    Response::success($message, ['value' => $result['value']], HttpStatus::HTTP_OK);
  }

  /**
   * Reset a system limit to default.
   */
  #[Route('admin/limits/reset', ['POST'])]
  /**
   * Handles resetSystemLimit operation.
   */
  public function resetSystemLimit(): void
  {
    if (!$this->requireCapability('admin.limits.reset')) {
      return;
    }

    $allowedStrings = ['key'];
    $allowedArrays = [];
    $filteredArray = RequestGuard::filterPost($allowedStrings, $allowedArrays);

    if (false === $filteredArray) {
      Response::error('[AdminController] RequestGuard failed.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);

      return;
    }

    /** @var array<string, string> $filteredArray */
    $key = (string) ($filteredArray['key'] ?? '');

    if (!$key) {
      Response::error('Limit key required.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $success = SystemConfig::remove($key);

    if (!$success) {
      Response::error('Failed to reset limit', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $schema = SystemConfig::getSchema();
    $defaultValue = $schema[$key]['default'] ?? null;

    \PayCal\Infrastructure\Audit\SystemAuditRepository::append('admin.system_limit.reset', User::currentUUID(), [
      'key' => $key,
    ]);

    Response::success('Limit reset to default', ['value' => $defaultValue], HttpStatus::HTTP_OK);
  }

  /**
   * Generate test orphaned work entries for testing the recovery feature
   * ADMIN ONLY - For development/testing purposes.
   */
  #[Route('admin/testing/create-orphaned-work', ['POST'])]
  /**
   * Handles createOrphanedWork operation.
   */
  public function createOrphanedWork(): void
  {
    if (!$this->requireCapability('admin.testing.create-orphaned-work')) {
      return;
    }

    // Admin-only check
    if (!User::isAdmin()) {
      Response::error('Admin access required', [], HttpStatus::HTTP_FORBIDDEN);

      return;
    }

    $currentUser = User::current();

    $userUUID = $currentUser->user_uuid;

    // Generate unique orphaned site ID
    $orphanedSiteId = 'Sorphaned'.substr(md5(uniqid((string) mt_rand(), true)), 0, 7);

    // Create 5 test work entries with the orphaned site ID
    $entries = [];
    $dates = [
        '2026-01-20',
        '2026-01-27',
        '2026-02-03',
        '2026-02-10',
        '2026-02-17',
    ];

    $hours = [9, 10.5, 10, 11, 12];
    $wages = [350, 410, 400, 425, 450];

    for ($i = 0; $i < 5; ++$i) {
      $workData = [
          'site_id' => $orphanedSiteId,
          'site_name' => 'Orphaned Test Site',
          'hours' => (string) $hours[$i],
          'e' => (string) $wages[$i],
          'living_out_allowance' => '0',
          'travel_hours' => '0',
          'c' => '',
          'wage' => '30.00',
          'date' => $dates[$i],
      ];

      WorkEntryRepository::save($workData, $userUUID);
      $entries[] = $dates[$i];
    }

    Response::success(
      '[Admin] Created 5 orphaned work entries',
      [
            'orphaned_site_id' => $orphanedSiteId,
            'dates' => $entries,
            'total_hours' => array_sum($hours),
            'total_earnings' => array_sum($wages),
        ],
      HttpStatus::HTTP_OK
    );
  }

  /**
   * Reset all system limits to defaults.
   */
  #[Route('admin/limits/reset-all', ['POST'])]
  /**
   * Handles resetAllSystemLimits operation.
   */
  public function resetAllSystemLimits(): void
  {
    if (!$this->requireCapability('admin.limits.reset-all')) {
      return;
    }

    SystemConfig::resetAll();
    \PayCal\Infrastructure\Audit\SystemAuditRepository::append('admin.system_limits.reset_all', User::currentUUID(), []);
    Response::success('All limits reset to defaults', [], HttpStatus::HTTP_OK);
  }

  /**
   * Get Redis Tier-0 reliability snapshot.
   */
  #[Route('admin/redis/status', ['GET'])]
  /**
   * Handles getRedisReliabilityStatus operation.
   */
  public function getRedisReliabilityStatus(): void
  {
    try {
      Response::success(
        '[Admin] Redis Tier-0 reliability snapshot.',
        ['snapshot' => RedisReliabilityService::getSnapshot()],
        HttpStatus::HTTP_OK
      );
    } catch (\Throwable $e) {
      Log::error('[Admin] getRedisReliabilityStatus exception: ' . $e->getMessage());
      Response::error('[Admin] Failed to load Redis reliability snapshot.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * AST graph/data API passthrough.
   * Delegates to the existing admin AST data handler implementation.
   */
  #[Route('admin/ast', ['GET', 'POST'])]
  public function astData(): void
  {
    if (!$this->authorized) {
      Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    require __DIR__ . '/../../admin/ast/data/index.php';
  }

  /**
   * Issue a short-lived one-shot capability token for high-risk admin mutations.
   */
  #[Route('admin/capability/{action}', ['GET'])]
  /**
   * Handles mintCapabilityToken operation.
   */
  public function mintCapabilityToken(string $action): void
  {
    $normalized = CapabilityTokenService::normalizeAction($action);
    if ($normalized === '' || !in_array($normalized, self::CAPABILITY_ACTIONS, true)) {
      Response::error('[Admin] Unsupported capability action.', ['action' => $normalized], HttpStatus::HTTP_BAD_REQUEST);
    }

    $sessionHash = Authentication::getCookie();
    $issued = CapabilityTokenService::issue($normalized, User::currentUUID(), $sessionHash);

    Response::success('[Admin] Capability token issued.', ['capability' => $issued], HttpStatus::HTTP_OK);
  }

  /**
   * Toggle global mutation freeze mode.
   */
  #[Route('admin/redis/freeze', ['POST'])]
  /**
   * Handles updateRedisMutationFreeze operation.
   */
  public function updateRedisMutationFreeze(): void
  {
    if (!$this->requireCapability('admin.redis.freeze')) {
      return;
    }

    $enabled = InputSanitizer::postString('enabled');
    $reason = InputSanitizer::postString('reason');

    if ($enabled !== '0' && $enabled !== '1') {
      Response::error('[Admin] Invalid freeze value. Use 0 or 1.', [], HttpStatus::HTTP_BAD_REQUEST);
    }

    RedisReliabilityService::setMutationFreeze($enabled === '1', (string) $reason);

    \PayCal\Infrastructure\Audit\SystemAuditRepository::append('admin.redis.mutation_freeze.updated', User::currentUUID(), [
      'enabled' => $enabled,
      'reason'  => (string) $reason,
    ]);

    Response::success(
      '[Admin] Redis mutation freeze updated.',
      [
        'mutation_freeze' => $enabled === '1',
        'reason' => (string) $reason,
      ],
      HttpStatus::HTTP_OK
    );
  }

  /**
   * Manually open Redis write circuit breaker.
   */
  #[Route('admin/redis/breaker/open', ['POST'])]
  /**
   * Handles openRedisCircuitBreaker operation.
   */
  public function openRedisCircuitBreaker(): void
  {
    if (!$this->requireCapability('admin.redis.breaker.open')) {
      return;
    }

    $reason = InputSanitizer::postString('reason');
    if ($reason === '') {
      $reason = 'Manual admin open';
    }
    RedisReliabilityService::openCircuitBreaker((string) $reason);

    \PayCal\Infrastructure\Audit\SystemAuditRepository::append('admin.redis.circuit_breaker.opened', User::currentUUID(), [
      'reason' => (string) $reason,
    ]);

    Response::success('[Admin] Redis circuit breaker opened.', ['reason' => (string) $reason], HttpStatus::HTTP_OK);
  }

  /**
   * Reset Redis write circuit breaker.
   */
  #[Route('admin/redis/breaker/reset', ['POST'])]
  /**
   * Handles resetRedisCircuitBreaker operation.
   */
  public function resetRedisCircuitBreaker(): void
  {
    if (!$this->requireCapability('admin.redis.breaker.reset')) {
      return;
    }

    RedisReliabilityService::resetCircuitBreaker();
    \PayCal\Infrastructure\Audit\SystemAuditRepository::append('admin.redis.circuit_breaker.reset', User::currentUUID(), []);
    Response::success('[Admin] Redis circuit breaker reset.', [], HttpStatus::HTTP_OK);
  }

  /**
   * Handles requireCapability operation.
   */
  private function requireCapability(string $action): bool
  {
    if (!$this->authorized) {
      Response::error('[AdminController] Forbidden.', [], HttpStatus::HTTP_FORBIDDEN);
      return false;
    }

    $token = InputSanitizer::postString('capability_token');
    if ($token === '') {
      $headerValue = $_SERVER['HTTP_X_PAYCAL_CAPABILITY'] ?? '';
      $token = is_scalar($headerValue) ? trim((string) $headerValue) : '';
    }

    $decision = CapabilityTokenService::consume(
      $token,
      $action,
      User::currentUUID(),
      Authentication::getCookie()
    );

    if (!$decision['ok']) {
      Response::error(
        '[Admin] Capability token rejected.',
        [
          'capability_code' => $decision['code'],
          'capability_message' => $decision['message'],
        ],
        HttpStatus::HTTP_FORBIDDEN
      );

      return false;
    }

    return true;
  }

  /**
   * Remove all known data linked to a user UUID.
   */
  private static function purgeUserData(string $userUUID, string $email, string $newEmail): void
  {
    // Work entries and archived work
    foreach (Database::scanKeys(Keys::WORK . ':' . $userUUID . ':*') as $key) {
      Database::unlink($key);
    }
    foreach (Database::scanKeys(Keys::WORK . ':archived:' . $userUUID . ':*') as $key) {
      Database::unlink($key);
    }

    // Site and earnings/cache records
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
    Database::unlink(Keys::VERIFICATION_CODES . ':' . $userUUID);

    // Email index records (current + pending; legacy and current styles)
    if ($email !== '') {
      Database::unlink(Keys::EMAIL . ':' . $email);
      Database::unlink(Keys::EMAIL . $email);
    }
    if ($newEmail !== '') {
      Database::unlink(Keys::EMAIL . ':' . $newEmail);
      Database::unlink(Keys::EMAIL . $newEmail);
    }

    // Passkey and wrapped DEK records
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
    Database::unlink(Keys::userKekV1($userUUID));

    // User nonces/csrf and user hash
    Database::unlink(Keys::USER . ':' . $userUUID . ':nonce');
    foreach (Database::scanKeys(Keys::USER . ':' . $userUUID . ':csrf:*') as $key) {
      Database::unlink($key);
    }
    Database::unlink(Keys::USER . ':' . $userUUID);

    // Session keys
    foreach (Database::scanKeys(Keys::SESSION . ':*') as $sessionKey) {
      $sessionUserUUID = (string) Database::hget($sessionKey, 'user_uuid');
      if ($sessionUserUUID === $userUUID) {
        Database::unlink($sessionKey);
      }
    }

    // Defensive UUID sweep for legacy/unknown key layouts
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
  }

  /**
   * Reloads a specific language file into Redis.
   */
  private function reloadLanguage(string $lang): void
  {
    $file = "/var/www/strings/{$lang}.txt";
    if (!file_exists($file)) {
      return;
    }

    $content = file_get_contents($file);
    if (false === $content) {
      return;
    }

    $aLang = [];
    $aLines = explode("\n", $content);
    foreach ($aLines as $sLine) {
      $sLine = trim($sLine);
      if ('' === $sLine || str_starts_with($sLine, '#')) {
        continue;
      }
      $parts = explode(' ', $sLine, 2);
      if (2 === count($parts)) {
        $key = $parts[0];
        $value = $parts[1];
        if (str_starts_with($key, 'i_')) {
          $storedKey = substr($key, 2);
          $aLang[$storedKey] = $value;
        }
      }
    }

    // Store in Redis
    $sLangHashKey = 'system:i18n:'.$lang;
    foreach ($aLang as $sKey => $sValue) {
      Database::hset($sLangHashKey, [
				$sKey => $sValue
			]);
    }
  }
}


