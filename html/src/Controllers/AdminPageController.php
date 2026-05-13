<?php declare(strict_types=1);

namespace PayCal\Controllers;

use Throwable;
use PayCal\Domain\Authentication;
use PayCal\Domain\Database;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Language;
use PayCal\Domain\MetricsService;
use PayCal\Domain\Render;
use PayCal\Domain\Security\CorrelationBroker;
use PayCal\Domain\Security\CorrelationContext;
use PayCal\Domain\Strings;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Taxes;
use PayCal\Domain\User;

/**
 * AdminPageController.php
 *
 * Purpose: Web-page controller for admin dashboards, diagnostics surfaces, and
 * server-rendered internal monitoring views.
 *
 * Developer notes:
 * - This controller renders admin-facing pages rather than API responses.
 * - Keep correlation-sensitive metrics and diagnostics behind the existing
 *   admin and broker checks before exposing them to templates.
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
 * Admin page controller.
 *
 * Responsibilities:
 * - Build server-rendered admin pages and diagnostics views.
 * - Coordinate admin-only metrics, summaries, and supporting template data.
 * - Keep presentation concerns separate from the admin API mutation layer.
 */
class AdminPageController
{

  /**
   * Handles batchI18n operation.
   */
  private static function batchI18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
  /**
   * Handles asString operation.
   */
  private static function asString(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * Handles asInt operation.
   */
  private static function asInt(mixed $value, int $default = 0): int
  {
    if (is_int($value)) {
      return $value;
    }

    if (is_numeric($value)) {
      return (int) $value;
    }

    return $default;
  }

  /**
   * Mask an IP address for display in admin UI.
   * Example: 192.168.1.100 -> 192.168.X.X
   *
   * @param string $ip IP address to mask
   * @return string Masked IP address
   */
  private static function maskIPAddress(string $ip): string
  {
    $ip = trim($ip);
    if (empty($ip)) {
      return 'Unknown';
    }

    // Handle IPv4
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      $parts = explode('.', $ip);
      if (count($parts) === 4) {
        return $parts[0] . '.' . $parts[1] . '.X.X';
      }
    }

    // Handle IPv6 or other formats
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      $parts = explode(':', $ip);
      if (count($parts) >= 4) {
        // Clear last 4 parts of IPv6 address
        $parts[4] = 'X';
        $parts[5] = 'X';
        $parts[6] = 'X';
        $parts[7] = 'X';
        return implode(':', $parts);
      }
    }

    // Unknown format
    return 'X.X.X.X';
  }

  /**
   * Mask a phone number for display in admin UI.
   * Example: 555-123-4567 -> 555-XXX-4567
   *
   * @param string $phone Phone number to mask
   * @return string Masked phone number
   */
  private static function maskPhoneNumber(string $phone): string
  {
    $phone = trim($phone);
    if (empty($phone)) {
      return 'Unknown';
    }

    // Remove all non-digit characters
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    if (!is_string($cleaned)) {
      return 'Unknown';
    }

    if (strlen($cleaned) < 4) {
      return 'Unknown';
    }

    // Format as XXX-XXX-XXXX with last 4 digits visible
    $lastFour = substr($cleaned, -4);
    return 'XXX-XXX-' . $lastFour;
  }

  /**
   * Validate admin access and redirect safely when possible.
   */
  private static function ensureAdminAccess(): bool
  {
    if (Authentication::validateAndTouchSession() && \PayCal\Domain\AdminSurface::userCanAccess()) {
      return true;
    }

    if (!headers_sent()) {
      header('Location: ' . \PayCal\Domain\Config\Environment::appURL('/'));
      exit;
    }

    return false;
  }

  /**
   * Handles canCorrelateAdminAccountSecurityMetadata operation.
   */
  private static function canCorrelateAdminAccountSecurityMetadata(): bool
  {
    $contextName = self::adminCorrelationContext();

    $context = new CorrelationContext(
      $contextName,
      User::currentUUID(),
      User::isAdmin() ? 'security-admin' : 'unknown',
      'admin-dashboard-enrichment',
      ['user_profile:session_metadata', 'user_profile:credential_metadata'],
      'admin_dashboard_enrichment'
    );

    return CorrelationBroker::evaluate($context)->allowed();
  }

  /**
   * Handles adminCorrelationContext operation.
   */
  private static function adminCorrelationContext(): string
  {
    $raw = InputSanitizer::sanitizeString(InputSanitizer::getString('correlation_context'));
    return $raw === '' ? 'admin-dashboard-default' : strtolower($raw);
  }

  /**
   * Build per-user session metadata snapshot from active session keys.
   *
   * @return array<string, array<string, string>>
   */
  private static function buildSessionSnapshotMap(): array
  {
    $snapshotMap = [];
    $sessionKeys = Database::scanKeys(Keys::SESSION . ':*');

    foreach ($sessionKeys as $sessionKey) {
      $sessionData = Database::hgetall($sessionKey);
      if (empty($sessionData)) {
        continue;
      }

      $userUUID = self::asString($sessionData['user_uuid'] ?? '');
      if ($userUUID === '') {
        continue;
      }

      if (!isset($snapshotMap[$userUUID])) {
        $snapshotMap[$userUUID] = [
          'registered_at' => '',
          'registered_ip' => '',
          'last_login_at' => '',
          'last_login_ip' => '',
          'last_session_at' => '',
          'last_session_hash' => '',
        ];
      }

      $createdAt = self::asString($sessionData['created_at'] ?? '');
      $firstIp = self::asString($sessionData['first_ip'] ?? '');
      $lastSignin = self::asString($sessionData['last_signin'] ?? '');
      $lastIp = self::asString($sessionData['last_ip'] ?? '');
      $lastActivity = self::asString($sessionData['last_activity'] ?? $lastSignin);

      $currentRegistered = $snapshotMap[$userUUID]['registered_at'];
      if ($createdAt !== '' && ($currentRegistered === '' || (int) $createdAt < (int) $currentRegistered)) {
        $snapshotMap[$userUUID]['registered_at'] = $createdAt;
        $snapshotMap[$userUUID]['registered_ip'] = $firstIp;
      }

      $currentLastLogin = $snapshotMap[$userUUID]['last_login_at'];
      if ($lastSignin !== '' && (int) $lastSignin >= (int) ($currentLastLogin === '' ? '0' : $currentLastLogin)) {
        $snapshotMap[$userUUID]['last_login_at'] = $lastSignin;
        $snapshotMap[$userUUID]['last_login_ip'] = $lastIp;
      }

      $currentLastSession = $snapshotMap[$userUUID]['last_session_at'];
      if ($lastActivity !== '' && (int) $lastActivity >= (int) ($currentLastSession === '' ? '0' : $currentLastSession)) {
        $snapshotMap[$userUUID]['last_session_at'] = $lastActivity;
        $snapshotMap[$userUUID]['last_session_hash'] = self::asString(Strings::extractPiece($sessionKey, 1));
      }
    }

    return $snapshotMap;
  }

  /**
   * Renders the admin dashboard page.
   * Handles authentication, data fetching, and template rendering.
   */
  public static function dashboard(): void
  {
    if (!self::ensureAdminAccess())
      return;

    $userKeys = Database::scanKeys('user:*');
    $userKeys = array_filter($userKeys, fn ($k) => 1 === substr_count($k, ':')); // filter out subkeys
    $includeSecurityCorrelation = self::canCorrelateAdminAccountSecurityMetadata();
    $sessionSnapshotMap = $includeSecurityCorrelation ? self::buildSessionSnapshotMap() : [];
    $userListHtml = '';
    foreach ($userKeys as $key) {
      $userData = Database::hgetall($key);
      if (!empty($userData)) {
        $userUUID = self::asString(Strings::extractPiece($key, 1));
        $sessionSnapshot = $sessionSnapshotMap[$userUUID] ?? [
          'registered_at' => '',
          'registered_ip' => '',
          'last_login_at' => '',
          'last_login_ip' => '',
          'last_session_at' => '',
          'last_session_hash' => '',
        ];
        $fullName = self::asString($userData['full_name'] ?? 'Unknown');
        $email = self::asString($userData['email'] ?? 'No Email');
        $authLevel = self::asString($userData['auth_level'] ?? 'guest');
        $phone = self::asString($userData['phone'] ?? '');

        $emailCreatedAt = '';
        if ($email !== '' && $email !== 'No Email') {
          $emailCreatedAt = self::asString(Database::hget(Keys::EMAIL . ':' . $email, 'created'));
          if ($emailCreatedAt === '') {
            $emailCreatedAt = self::asString(Database::hget(Keys::EMAIL . $email, 'created'));
          }
        }

        $registeredAt = self::asString($userData['created_at'] ?? $emailCreatedAt);
        if ($registeredAt === '') {
          $registeredAt = $sessionSnapshot['registered_at'];
        }

        $registeredIp = self::asString($userData['registration_ip'] ?? ($userData['created_ip'] ?? ''));
        if ($registeredIp === '') {
          $registeredIp = $sessionSnapshot['registered_ip'];
        }

        $lastLoginAt = self::asString($userData['last_signin'] ?? $sessionSnapshot['last_login_at']);
        $lastLoginIp = self::asString($userData['last_signin_ip'] ?? $sessionSnapshot['last_login_ip']);
        $lastSessionAt = $sessionSnapshot['last_session_at'];
        $lastSessionHash = self::asString($userData['last_session_hash'] ?? $sessionSnapshot['last_session_hash']);

        $lastAuthMethod = self::asString($userData['last_auth_method'] ?? 'unknown');
        $credentialCount = '0';
        $lastPasskeyUsedAt = '';
        if ($includeSecurityCorrelation) {
          $credentialIds = Database::smembers(Keys::webauthnUserCredentials($userUUID));
          $credentialCount = (string) count($credentialIds);
          foreach ($credentialIds as $credentialIdRaw) {
            $credentialId = self::asString($credentialIdRaw);
            if ($credentialId === '') {
              continue;
            }

            $credentialLastUsed = self::asString(Database::hget(Keys::webauthnCredential($credentialId), 'last_used_at'));
            if ($credentialLastUsed === '') {
              continue;
            }

            if ($lastPasskeyUsedAt === '' || (int) $credentialLastUsed > (int) $lastPasskeyUsedAt) {
              $lastPasskeyUsedAt = $credentialLastUsed;
            }
          }
        }

        $emailVerified = self::asString($userData['email_verified'] ?? '0') === '1' ? 'yes' : 'no';
        $webauthnEnabledRaw = self::asString($userData['webauthn_enabled'] ?? '');
        if ($webauthnEnabledRaw === '') {
          $webauthnEnabledRaw = ((int) $credentialCount > 0) ? '1' : '0';
        }
        $webauthnEnabled = $webauthnEnabledRaw === '1' ? 'yes' : 'no';
        $accountStateFlags = 'email_verified=' . $emailVerified
          . ', webauthn_enabled=' . $webauthnEnabled
          . ', auth_level=' . $authLevel;

        $safeUUID = htmlspecialchars($userUUID, ENT_QUOTES, 'UTF-8');
        $safeFullName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safeAuthLevel = htmlspecialchars($authLevel, ENT_QUOTES, 'UTF-8');
        $safeMaskedPhone = htmlspecialchars(self::maskPhoneNumber($phone), ENT_QUOTES, 'UTF-8');
        $safeRegisteredAt = htmlspecialchars($registeredAt, ENT_QUOTES, 'UTF-8');
        $safeMaskedRegisteredIp = htmlspecialchars(self::maskIPAddress($registeredIp), ENT_QUOTES, 'UTF-8');
        $safeLastLoginAt = htmlspecialchars($lastLoginAt, ENT_QUOTES, 'UTF-8');
        $safeMaskedLastLoginIp = htmlspecialchars(self::maskIPAddress($lastLoginIp), ENT_QUOTES, 'UTF-8');
        $safeLastSessionAt = htmlspecialchars($lastSessionAt, ENT_QUOTES, 'UTF-8');
        $safeLastSessionHash = htmlspecialchars($lastSessionHash, ENT_QUOTES, 'UTF-8');
        $safeLastAuthMethod = htmlspecialchars($lastAuthMethod, ENT_QUOTES, 'UTF-8');
        $safeCredentialCount = htmlspecialchars($credentialCount, ENT_QUOTES, 'UTF-8');
        $safeLastPasskeyUsedAt = htmlspecialchars($lastPasskeyUsedAt, ENT_QUOTES, 'UTF-8');
        $safeAccountStateFlags = htmlspecialchars($accountStateFlags, ENT_QUOTES, 'UTF-8');
        $userListHtml .= "<button class='btn btn_edit w100' aria-label='" . htmlspecialchars(self::batchI18n('ADMIN_EDIT_USER_ARIA'), ENT_QUOTES, 'UTF-8') . "' data-uuid='{$safeUUID}' data-full-name='{$safeFullName}' data-email='{$safeEmail}' data-auth-level='{$safeAuthLevel}' data-phone='{$safeMaskedPhone}' data-registered-at='{$safeRegisteredAt}' data-registered-ip='{$safeMaskedRegisteredIp}' data-last-login-at='{$safeLastLoginAt}' data-last-login-ip='{$safeMaskedLastLoginIp}' data-last-session-at='{$safeLastSessionAt}' data-last-session-hash='{$safeLastSessionHash}' data-last-auth-method='{$safeLastAuthMethod}' data-credential-count='{$safeCredentialCount}' data-last-passkey-used-at='{$safeLastPasskeyUsedAt}' data-account-state-flags='{$safeAccountStateFlags}'>{$safeFullName}&nbsp;{$safeEmail}</button>";
      }
    }

    // Fetch current invite code
    $currentInviteCode = Database::get(Keys::SYSTEM . ':invite_code') ?: \PayCal\Domain\Config\Environment::inviteCode();

    // Generate system limits HTML
    $systemLimitsHtml = self::generateSystemLimitsHtml();

    $contactMetrics = MetricsService::getContactSupportMetrics();
    $totalSubmissions = self::asInt($contactMetrics['total_submissions'] ?? 0);
    $todayTotal = self::asInt($contactMetrics['today_total'] ?? 0);
    $todayFailure = self::asInt($contactMetrics['today_failure'] ?? 0);
    $lastSubmissionAt = self::asInt($contactMetrics['last_submission_at'] ?? 0);
    $lastSubmissionText = $lastSubmissionAt > 0 ? date('Y-m-d H:i:s', $lastSubmissionAt) : self::batchI18n('NEVER');
    $failureClass = $todayFailure > 0 ? 'danger' : 'success';

    $contactHealthHtml = ""
      . "<div class='admin-card panel'>"
      . "<div class='admin-card-header'><h3>" . htmlspecialchars(self::batchI18n('ADMIN_CONTACT_PIPELINE_HEALTH'), ENT_QUOTES, 'UTF-8') . "</h3></div>"
      . "<div class='admin-card-body'>"
      . "<p>" . htmlspecialchars(self::batchI18n('ADMIN_TOTAL_SUBMISSIONS'), ENT_QUOTES, 'UTF-8') . ": <strong>" . Strings::formatLocalizedNumber($totalSubmissions, 0, 0) . "</strong></p>"
      . "<p>" . htmlspecialchars(self::batchI18n('TODAY'), ENT_QUOTES, 'UTF-8') . ": <strong>" . Strings::formatLocalizedNumber($todayTotal, 0, 0) . "</strong></p>"
      . "<p class='" . $failureClass . "'>" . htmlspecialchars(self::batchI18n('ADMIN_TODAY_FAILURES'), ENT_QUOTES, 'UTF-8') . ": <strong>" . Strings::formatLocalizedNumber($todayFailure, 0, 0) . "</strong></p>"
      . "<p>" . htmlspecialchars(self::batchI18n('ADMIN_LAST_SUBMISSION'), ENT_QUOTES, 'UTF-8') . ": <strong>" . htmlspecialchars($lastSubmissionText, ENT_QUOTES, 'UTF-8') . "</strong></p>"
      . "</div>"
      . "<div class='admin-card-footer'><a href='/admin/metrics' class='btn btn_secondary'>" . htmlspecialchars(self::batchI18n('ADMIN_OPEN_DETAILED_HEALTH'), ENT_QUOTES, 'UTF-8') . "</a></div>"
      . "</div>";

    $billingMetrics = MetricsService::getBillingWebhookMetrics();
    $billingOutcomes = is_array($billingMetrics['outcomes'] ?? null) ? $billingMetrics['outcomes'] : [];
    $billingEvents = is_array($billingMetrics['event_types'] ?? null) ? $billingMetrics['event_types'] : [];
    $processedWebhooks = self::asInt($billingOutcomes['processed'] ?? 0);
    $verificationFailures = self::asInt($billingOutcomes['verification_failed'] ?? 0);
    $rejectedWebhooks = self::asInt($billingOutcomes['event_rejected'] ?? 0);
    $trackedEventTypes = 0;
    foreach ($billingEvents as $summary) {
      if (!is_array($summary)) {
        continue;
      }

      $processedCount = self::asInt($summary['processed'] ?? 0);
      $duplicateCount = self::asInt($summary['duplicate'] ?? 0);
      if ($processedCount > 0 || $duplicateCount > 0) {
        $trackedEventTypes++;
      }
    }
    $stripeHealthClass = ($verificationFailures > 0 || $rejectedWebhooks > 0) ? 'danger' : 'success';
    $stripeHealthHtml = ""
      . "<div class='admin-card panel'>"
      . "<div class='admin-card-header'><h3>" . htmlspecialchars(self::batchI18n('ADMIN_STRIPE_OVERVIEW'), ENT_QUOTES, 'UTF-8') . "</h3></div>"
      . "<div class='admin-card-body'>" . Strings::formatLocalizedNumber($processedWebhooks, 0, 0) . "</strong></p>"
      . "<p class='" . $stripeHealthClass . "'>" . htmlspecialchars(self::batchI18n('ADMIN_VERIFICATION_FAILURES_REJECTED_EVENTS'), ENT_QUOTES, 'UTF-8') . ": <strong>"
      . Strings::formatLocalizedNumber($verificationFailures, 0, 0) . " / " . Strings::formatLocalizedNumber($rejectedWebhooks, 0, 0) . "</strong></p>"
      . "<p>" . htmlspecialchars(self::batchI18n('ADMIN_TRACKED_EVENT_TYPES_WITH_ACTIVITY'), ENT_QUOTES, 'UTF-8') . ": <strong>" . Strings::formatLocalizedNumber($trackedEventTypes, 0, 0) . "</strong></p>"
      . "</div>"
      . "<div class='admin-card-footer'>"
      . "<a href='/admin/stripe/' class='btn btn_primary'>" . htmlspecialchars(self::batchI18n('ADMIN_OPEN_STRIPE_DASHBOARD'), ENT_QUOTES, 'UTF-8') . "</a>"
      . "<a href='https://dashboard.stripe.com/' target='_blank' rel='noopener noreferrer' class='btn btn_secondary'>" . htmlspecialchars(self::batchI18n('ADMIN_OPEN_STRIPE_EXTERNAL'), ENT_QUOTES, 'UTF-8') . "</a>"
      . "</div>"
      . "</div>";

    // Render the template with placeholders
    $cspNonce = self::asString($_SERVER['CSP_NONCE'] ?? '');
    $dashboardI18nKeys = [
      'ADMIN',
      'ADMIN_DASHBOARD_GENERAL_HEALTH_ARIA',
      'ADMIN_DASHBOARD_GENERAL_HEALTH_SUBTITLE',
      'ADMIN_DASHBOARD_GENERAL_HEALTH_TITLE',
      'ADMIN_DASHBOARD_GENERATE_TEST_DATA',
      'ADMIN_DASHBOARD_INVITE_CODE',
      'ADMIN_DASHBOARD_METRICS_DASHBOARD_BODY',
      'ADMIN_DASHBOARD_METRICS_DASHBOARD_TITLE',
      'ADMIN_DASHBOARD_ORPHANED_WORK_TEST_BODY',
      'ADMIN_DASHBOARD_ORPHANED_WORK_TEST_TITLE',
      'ADMIN_DASHBOARD_PLATFORM_METRICS_ARIA',
      'ADMIN_DASHBOARD_PLATFORM_METRICS_SUBTITLE',
      'ADMIN_DASHBOARD_PLATFORM_METRICS_TITLE',
      'ADMIN_DASHBOARD_REGISTRATION_SETTINGS',
      'ADMIN_DASHBOARD_SAVE_CHANGES',
      'ADMIN_DASHBOARD_SYSTEM_SETTINGS_ARIA',
      'ADMIN_DASHBOARD_TESTING_TOOLS_ARIA',
      'ADMIN_DASHBOARD_TESTING_TOOLS_SUBTITLE',
      'ADMIN_DASHBOARD_TESTING_TOOLS_TITLE',
      'ADMIN_DASHBOARD_TRANSPARENCY_PAGE_BODY',
      'ADMIN_DASHBOARD_TRANSPARENCY_PAGE_TITLE',
      'ADMIN_DASHBOARD_USER_MANAGEMENT_ARIA',
      'ADMIN_DASHBOARD_VIEW_DASHBOARD',
      'ADMIN_DASHBOARD_VIEW_PUBLIC_PAGE',
      'ADMIN_EDIT_USER_CLOSE_ARIA',
      'ADMIN_EDIT_USER_MODAL_ARIA',
      'ADMIN_EDIT_USER_MODAL_DESC',
      'ADMIN_EDIT_USER_MODAL_META',
      'ADMIN_EDIT_USER_TITLE',
      'ADMIN_USER_ACTIVITY',
      'ADMIN_USER_ACTIVITY_SECURITY_ARIA',
      'ADMIN_USER_AUTH_LEVEL',
      'ADMIN_USER_GUEST',
      'ADMIN_USER_NOTES',
      'ADMIN_USER_PHONE',
      'ADMIN_USER_REGISTERED',
      'ADMIN_USER_SECURITY_DASHBOARD',
      'ADMIN_USER_SECURITY_DASHBOARD_ARIA',
      'ADMIN_USER_SESSION_REGISTRATION_ARIA',
      'ADMIN_USER_UNKNOWN',
      'ADMIN_USER_UNVERIFIED',
      'ADMIN_USER_VERIFIED',
      'UPDATE',
      'USERS',
    ];
    $dashboardI18nRenders = [];
    foreach ($dashboardI18nKeys as $dashboardI18nKey) {
      $dashboardI18nRenders['__' . $dashboardI18nKey . '__'] = Strings::i18n($dashboardI18nKey);
    }

    echo Render::template('admin-dashboard', array_merge([
      '__PAGE_LABEL__' => self::batchI18n('ADMIN_PANEL'),
      '__PAGE_LANGUAGE__' => self::asString(User::current()->language),
      '__USER_LIST_HTML__' => $userListHtml,
      '__CURRENT_INVITE_CODE__' => self::asString($currentInviteCode),
      '__SYSTEM_LIMITS_HTML__' => $systemLimitsHtml,
      '__CONTACT_HEALTH_HTML__' => $contactHealthHtml,
      '__STRIPE_HEALTH_HTML__' => $stripeHealthHtml,
      '__SITE__' => '/',
      '__CSP_NONCE__' => $cspNonce,
    ], $dashboardI18nRenders));
  }

  /**
   * Renders the tax brackets editor page.
   * Handles authentication and template rendering for editing tax brackets.
   */
  public static function taxBrackets(): void
  {
    if (!self::ensureAdminAccess())
      return;

    // List of provinces and territories
    $provinces = [
        'Alberta', 'British Columbia', 'Manitoba', 'New Brunswick', 'Newfoundland and Labrador',
        'Northwest Territories', 'Nova Scotia', 'Nunavut', 'Ontario', 'Prince Edward Island',
        'Quebec', 'Saskatchewan', 'Yukon',
    ];

    // Selected province (default to Alberta)
    $selectedProvince = InputSanitizer::getString('province') ?? 'Alberta';

    // Country (only Canada for now)
    $countries = ['Canada'];

    // Load current federal brackets from Redis hash or defaults
    $federalStored = Database::hget(Keys::SYSTEM . ':tax_brackets', 'canada:federal');
    if ($federalStored) {
      $decoded = json_decode($federalStored, true);
      if (is_array($decoded) && !empty($decoded)) {
        /** @var array<int, array{0: float, 1: float, 2: float}> $federalBrackets */
        $federalBrackets = $decoded;
      } else {
        $taxes = new Taxes();
        $federalBrackets = $taxes->getDefaultFederalBrackets()->toArrays();
      }
    } else {
      $taxes = new Taxes();
      $federalBrackets = $taxes->getDefaultFederalBrackets()->toArrays();
    }

    // Load current provincial brackets for selected province from Redis hash or defaults
    $provincialStored = Database::hget(Keys::SYSTEM . ':tax_brackets', "canada:{$selectedProvince}");
    if ($provincialStored) {
      $decoded = json_decode($provincialStored, true);
      if (is_array($decoded) && !empty($decoded)) {
        /** @var array<int, array{0: float, 1: float, 2: float}> $provincialBrackets */
        $provincialBrackets = $decoded;
      } else {
        $taxes = new Taxes();
        $provincialBrackets = $taxes->getDefaultProvincialBrackets($selectedProvince)->toArrays();
      }
    } else {
      $taxes = new Taxes();
      $provincialBrackets = $taxes->getDefaultProvincialBrackets($selectedProvince)->toArrays();
    }

    // Generate HTML for federal brackets
    $federalHtml = '';
    foreach ($federalBrackets as $i => $bracket) {
      $min = (string) $bracket[0];
      $max = (string) $bracket[1];
      $rate = (string) $bracket[2];
      $federalHtml .= "<div class='bracket-row'>";
      $federalHtml .= "<input type='number' name='federal_min_{$i}' value='{$min}' placeholder='" . htmlspecialchars(self::batchI18n('MIN'), ENT_QUOTES, 'UTF-8') . "' />";
      $federalHtml .= "<input type='number' name='federal_max_{$i}' value='{$max}' placeholder='" . htmlspecialchars(self::batchI18n('MAX'), ENT_QUOTES, 'UTF-8') . "' />";
      $federalHtml .= "<input type='number' step='0.01' name='federal_rate_{$i}' value='{$rate}' placeholder='" . htmlspecialchars(self::batchI18n('RATE'), ENT_QUOTES, 'UTF-8') . "' />";
      $federalHtml .= '</div>';
    }

    // Generate HTML for provincial brackets
    $provincialHtml = '';
    foreach ($provincialBrackets as $i => $bracket) {
      $min = (string) $bracket[0];
      $max = (string) $bracket[1];
      $rate = (string) $bracket[2];
      $provincialHtml .= "<div class='bracket-row'>";
      $provincialHtml .= "<input type='number' name='provincial_min_{$i}' value='{$min}' placeholder='" . htmlspecialchars(self::batchI18n('MIN'), ENT_QUOTES, 'UTF-8') . "' />";
      $provincialHtml .= "<input type='number' name='provincial_max_{$i}' value='{$max}' placeholder='" . htmlspecialchars(self::batchI18n('MAX'), ENT_QUOTES, 'UTF-8') . "' />";
      $provincialHtml .= "<input type='number' step='0.01' name='provincial_rate_{$i}' value='{$rate}' placeholder='" . htmlspecialchars(self::batchI18n('RATE'), ENT_QUOTES, 'UTF-8') . "' />";
      $provincialHtml .= '</div>';
    }

    // Generate province select HTML
    $provinceSelectHtml = "<select id='province_select' name='province'>";
    foreach ($provinces as $province) {
      $selected = $province === $selectedProvince ? 'selected' : '';
      $provinceSelectHtml .= "<option value='{$province}' {$selected}>{$province}</option>";
    }
    $provinceSelectHtml .= '</select>';

    // Country select (only Canada)
    $countrySelectHtml = "<select id='country_select' name='country'>";
    foreach ($countries as $country) {
      $countrySelectHtml .= "<option value='{$country}'>{$country}</option>";
    }
    $countrySelectHtml .= '</select>';

    // Render the template
    $cspNonce = self::asString($_SERVER['CSP_NONCE'] ?? '');

    echo Render::template('admin-tax-brackets', [
      '__PAGE_LABEL__' => self::batchI18n('TAX_BRACKETS_EDITOR'),
      '__PAGE_LANGUAGE__' => self::asString(USER_LANGUAGE),
      '__ADMIN_TAX_BRACKETS_EDITOR_ARIA__' => self::batchI18n('ADMIN_TAX_BRACKETS_EDITOR_ARIA'),
      '__ADMIN_TAX_BRACKETS_SELECTIONS_ARIA__' => self::batchI18n('ADMIN_TAX_BRACKETS_SELECTIONS_ARIA'),
      '__ADMIN_TAX_COUNTRY_LABEL__' => self::batchI18n('ADMIN_TAX_COUNTRY_LABEL'),
      '__ADMIN_TAX_PROVINCE_LABEL__' => self::batchI18n('ADMIN_TAX_PROVINCE_LABEL'),
      '__ADMIN_TAX_FEDERAL_ARIA__' => self::batchI18n('ADMIN_TAX_FEDERAL_ARIA'),
      '__ADMIN_TAX_FEDERAL_TITLE__' => self::batchI18n('ADMIN_TAX_FEDERAL_TITLE'),
      '__ADMIN_TAX_MIN_INCOME__' => self::batchI18n('ADMIN_TAX_MIN_INCOME'),
      '__ADMIN_TAX_MAX_INCOME__' => self::batchI18n('ADMIN_TAX_MAX_INCOME'),
      '__ADMIN_TAX_RATE_PERCENT__' => self::batchI18n('ADMIN_TAX_RATE_PERCENT'),
      '__ADMIN_TAX_SAVE_FEDERAL__' => self::batchI18n('ADMIN_TAX_SAVE_FEDERAL'),
      '__ADMIN_TAX_PROVINCIAL_ARIA__' => self::batchI18n('ADMIN_TAX_PROVINCIAL_ARIA'),
      '__ADMIN_TAX_PROVINCIAL_TITLE_PREFIX__' => self::batchI18n('ADMIN_TAX_PROVINCIAL_TITLE_PREFIX'),
      '__ADMIN_TAX_SAVE_PROVINCIAL__' => self::batchI18n('ADMIN_TAX_SAVE_PROVINCIAL'),
      '__FEDERAL_BRACKETS_HTML__' => $federalHtml,
      '__PROVINCIAL_BRACKETS_HTML__' => $provincialHtml,
      '__PROVINCE_SELECT_HTML__' => $provinceSelectHtml,
      '__COUNTRY_SELECT_HTML__' => $countrySelectHtml,
      '__SELECTED_PROVINCE__' => self::asString($selectedProvince),
      '__API_BASE_URL__' => self::asString(Environment::appURL('api/' . Environment::apiVersion())),
      '__ADMIN_TAX_BRACKETS_JS_URL__' => self::asString(Environment::appURL('js/admin-tax-brackets/') . '?v=' . Environment::appVersion()),
      '__CSP_NONCE__' => $cspNonce,
    ]);
  }

  /**
   * Generate HTML for system limits editor (organized by category panels).
   */
  private static function generateSystemLimitsHtml(): string
  {
    try {
      $grouped = SystemConfig::getByCategory();
      $categoryLabels = SystemConfig::getCategoryLabels();
      $values = SystemConfig::getAll();
      $html = '';

      foreach ($grouped as $category => $limits) {
        $categoryKey = (string) $category;
        $categoryLabelRaw = $categoryLabels[$categoryKey] ?? ucfirst($categoryKey);
        $categoryLabel = self::asString($categoryLabelRaw, ucfirst($categoryKey));

        $html .= "<section class='admin_panel panel' data-category='{$categoryKey}'>";
        $html .= "<h2 class='admin_panel_title'>".htmlspecialchars($categoryLabel).'</h2>';

        foreach ($limits as $key => $spec) {
          $keyString = (string) $key;
          $currentValue = $values[$keyString] ?? ($spec['default'] ?? '');
          $isDefault = $currentValue === ($spec['default'] ?? null);
          $type = self::asString($spec['type'] ?? 'string', 'string');
          $label = self::asString($spec['label'] ?? $keyString, $keyString);
          $currentValueString = self::asString($currentValue);

          $html .= "<div class='admin_row' data-limit-key='{$keyString}'>";
          $html .= "<div class='admin_label'>";
          $html .= htmlspecialchars($label);
          $html .= '</div>';
          $html .= "<div class='admin_control'>";

          if ('int' === $type) {
            $min = self::asString($spec['min'] ?? 0, '0');
            $max = self::asString($spec['max'] ?? 999999, '999999');
            $html .= "<input type='number' class='limit_value' data-key='{$keyString}' value='{$currentValueString}' min='{$min}' max='{$max}' step='1' />";
          } elseif ('float' === $type) {
            $min = self::asString($spec['min'] ?? 0, '0');
            $max = self::asString($spec['max'] ?? 999999, '999999');
            $html .= "<input type='number' class='limit_value' data-key='{$keyString}' value='{$currentValueString}' min='{$min}' max='{$max}' step='0.1' />";
          } elseif ('bool' === $type) {
            $checked = $currentValue ? 'checked' : '';
            $html .= "<label class='switch'>";
            $html .= "<input type='checkbox' class='limit_value' data-key='{$keyString}' {$checked} />";
            $html .= "<span class='slider'></span>";
            $html .= '</label>';
          } elseif ('string' === $type) {
            if (isset($spec['options']) && is_array($spec['options'])) {
              // Dropdown for string with options
              $html .= "<select class='limit_value' data-key='{$keyString}'>";
              foreach ($spec['options'] as $option) {
                $optionString = self::asString($option);
                $selected = ($currentValue === $option) ? 'selected' : '';
                $html .= "<option value='{$optionString}' {$selected}>".htmlspecialchars($optionString).'</option>';
              }
              $html .= '</select>';
            } else {
              // Text input for free-form strings
              $html .= "<input type='text' class='limit_value' data-key='{$keyString}' value='".htmlspecialchars($currentValueString)."' />";
            }
          }

          if (!$isDefault) {
            $resetLabel = htmlspecialchars(self::batchI18n('ADMIN_LIMITS_RESET_TO_DEFAULT') . ' ' . $keyString, ENT_QUOTES, 'UTF-8');
            $resetTitle = htmlspecialchars(self::batchI18n('ADMIN_LIMITS_RESET_TO_DEFAULT'), ENT_QUOTES, 'UTF-8');
            $html .= "<button class='btn btn_sm btn_secondary limit_reset' data-key='{$keyString}' aria-label='{$resetLabel}' title='{$resetTitle}'>↺</button>";
          }
          $html .= '</div>';
          $html .= '</div>';
        }

        $html .= "<div class='admin_footer'>";
        $html .= "<button class='btn btn_sm btn_danger category_reset_all' data-category='{$categoryKey}'>" . htmlspecialchars(self::batchI18n('RESET_ALL'), ENT_QUOTES, 'UTF-8') . "</button>";
        $html .= '</div>';
        $html .= '</section>'; // admin_panel
      }

      return $html;
    } catch (Throwable $e) {
      error_log('SystemLimits HTML generation error: '.$e->getMessage());

      return "<p class='error'>" . htmlspecialchars(self::batchI18n('ADMIN_SYSTEM_LIMITS_LOAD_ERROR'), ENT_QUOTES, 'UTF-8') . "</p>";
    }
  }
}

