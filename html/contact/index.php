<?php

declare(strict_types=1);

use PayCal\Domain\EmailGarum;
use PayCal\Domain\Database;
use PayCal\Domain\ContactSupportTelemetry;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Response;
use PayCal\Domain\Strings;
use PayCal\Domain\User;
use PayCal\Domain\Config\SystemConfig;

const CONTACT_SEND_COOLDOWN_SECONDS = 300;
const CONTACT_FORM_TOKEN_TTL_SECONDS = 1800;

/**
 * Contact page controller.
 */
$currentPage = 'PAGE_CONTACT';

require_once '../config.php';

function contactI18n(string $key): string
{
  static $i18n = [];
  if (!array_key_exists($key, $i18n)) {
    $i18n[$key] = Strings::i18n($key);
  }

  return $i18n[$key];
}

$pageTitle = contactI18n('CONTACT_US') . ' - [' . contactI18n('SITE_NAME') . ']';
$pageLabel = contactI18n('CONTACT');

\PayCal\Observability\Lens::boot('contact');
if (\PayCal\Domain\InputSanitizer::getString('lens') === '1') {
  \PayCal\Observability\Lens::add('Contact Backend Snapshot', [
    'page' => $currentPage,
    'language' => (string) USER_LANGUAGE,
    'template' => 'contact/'.USER_LANGUAGE,
  ]);
}

/**
 * @return array{0: bool, 1: string, 2: array<string, string>, 3: array<string, string>, 4: int}
 */
function handleContactSubmission(): array
{
  $name = trim(InputSanitizer::sanitizeName(InputSanitizer::postRaw('name')));
  $email = trim(InputSanitizer::sanitizeEmail(InputSanitizer::postRaw('email')));
  $subject = trim(InputSanitizer::sanitizeNotes(InputSanitizer::postRaw('subject')));
  $message = sanitizeContactMessage(InputSanitizer::postRaw('message'));
  $reason = trim(InputSanitizer::postRaw('reason'));
  $formToken = trim(InputSanitizer::postString('contact_form_token'));
  $includeBrowserDevice = InputSanitizer::postString('include_browser_device') === '1';
  $includeIpAddress = InputSanitizer::postString('include_ip_address') === '1';
  $includeLanguageRegion = InputSanitizer::postString('include_language_region') === '1';

  $fieldValues = [
    'name' => $name,
    'email' => $email,
    'subject' => $subject,
    'message' => $message,
    'reason' => $reason,
  ];

  $baseTelemetry = buildContactTelemetryPayload($fieldValues, [
    'include_browser_device' => $includeBrowserDevice,
    'include_ip_address' => $includeIpAddress,
    'include_language_region' => $includeLanguageRegion,
  ]);

  $clientIpAddress = resolveContactClientIpAddress();
  $clientBrowserDevice = resolveContactBrowserAndDevice();

  // Honeypot: reject if filled (bots usually fill all fields)
  $honeypot = InputSanitizer::postString('contact_website');
  if ($honeypot !== '') {
    // Silent fail - log but don't reveal to user
    ContactSupportTelemetry::recordSubmission($baseTelemetry + [
      'outcome' => 'blocked_honeypot',
      'is_success' => 0,
    ]);
    return [false, contactI18n('CONTACT_ERROR_SEND_FAILED'), [], $fieldValues, 0];
  }

  // Check minimum submit time (3 seconds is typical human interaction)
  $submitTime = (int) InputSanitizer::postString('contact_form_time');
  if ($submitTime < 3000) {
    // Form filled too quickly - likely bot
    ContactSupportTelemetry::recordSubmission($baseTelemetry + [
      'outcome' => 'blocked_fast_submit',
      'is_success' => 0,
    ]);
    return [false, contactI18n('CONTACT_ERROR_RAPID_SUBMIT'), [], $fieldValues, 0];
  }

  if (!consumeContactFormToken($formToken)) {
    ContactSupportTelemetry::recordSubmission($baseTelemetry + [
      'outcome' => 'blocked_invalid_form_token',
      'is_success' => 0,
    ]);
    return [false, contactI18n('CONTACT_ERROR_FORM_EXPIRED'), [], $fieldValues, 0];
  }

  $cooldownRemaining = getContactSendCooldownRemainingSeconds();
  if ($cooldownRemaining > 0) {
    ContactSupportTelemetry::recordSubmission($baseTelemetry + [
      'outcome' => 'blocked_cooldown',
      'is_success' => 0,
    ]);
    return [
      false,
      sprintf(contactI18n('CONTACT_ERROR_COOLDOWN_WAIT'), formatContactCooldownDuration($cooldownRemaining)),
      [],
      $fieldValues,
      $cooldownRemaining,
    ];
  }

  $fieldErrors = [];
  if ($name === '') {
    $fieldErrors['name'] = contactI18n('CONTACT_ERROR_NAME_REQUIRED');
  }
  if ($email === '') {
    $fieldErrors['email'] = contactI18n('CONTACT_ERROR_EMAIL_REQUIRED');
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $fieldErrors['email'] = contactI18n('CONTACT_ERROR_EMAIL_INVALID');
  }
  if ($subject === '') {
    $fieldErrors['subject'] = contactI18n('CONTACT_ERROR_SUBJECT_REQUIRED');
  }
  if ($message === '') {
    $fieldErrors['message'] = contactI18n('CONTACT_ERROR_MESSAGE_REQUIRED');
  }
  if ($reason === '') {
    $fieldErrors['reason'] = contactI18n('CONTACT_ERROR_REASON_REQUIRED');
  }

  if ($fieldErrors !== []) {
    ContactSupportTelemetry::recordSubmission($baseTelemetry + [
      'outcome' => 'validation_failed',
      'is_success' => 0,
    ]);
    return [false, contactI18n('CONTACT_ERROR_CORRECT_FIELDS'), $fieldErrors, $fieldValues, 0];
  }

  $emailMessage = $message;
  $contextLines = [
    contactI18n('CONTACT_CONTEXT_BROWSER') . ': ' . $clientBrowserDevice,
    contactI18n('CONTACT_CONTEXT_PAGE') . ': ' . $clientIpAddress,
  ];
  if ($includeLanguageRegion) {
    $contextLines[] = contactI18n('CONTACT_CONTEXT_LANGUAGE') . ': ' . resolveContactLanguageAndRegion();
  }

  $emailMessage = rtrim($emailMessage) . "\n\n--- " . contactI18n('CONTACT_CONTEXT_HEADER') . " ---\n" . implode("\n", $contextLines);

  $topicLabel = normalizeContactReasonLabel($reason);

  $sendResult = EmailGarum::pcSendContactEmail($name, $email, $subject, $emailMessage, $topicLabel);
  $isSuccess = stripos($sendResult, 'successfully') !== false;

  if (!$isSuccess) {
    ContactSupportTelemetry::recordSubmission($baseTelemetry + [
      'outcome' => 'email_send_failed',
      'is_success' => 0,
    ]);
    return [false, contactI18n('CONTACT_ERROR_SEND_FAILED'), [], $fieldValues, 0];
  }

  ContactSupportTelemetry::recordSubmission($baseTelemetry + [
    'outcome' => 'email_send_success',
    'is_success' => 1,
  ]);

  applyContactCooldownLocks(CONTACT_SEND_COOLDOWN_SECONDS);

  return [
    true,
    sprintf(
      contactI18n('CONTACT_SUCCESS_COOLDOWN_MESSAGE'),
      formatContactCooldownDuration(CONTACT_SEND_COOLDOWN_SECONDS)
    ),
    [],
    [
      'name' => '',
      'email' => '',
      'subject' => '',
      'message' => '',
      'reason' => '',
    ],
    CONTACT_SEND_COOLDOWN_SECONDS,
  ];
}

/**
 * @param array<string, string> $fieldValues
 * @param array<string, bool> $options
 * @return array<string, int|string>
 */
function buildContactTelemetryPayload(array $fieldValues, array $options): array
{
  $sessionRaw = session_id();
  $sessionId = is_string($sessionRaw) ? $sessionRaw : '';

  return [
    'sender_name' => $fieldValues['name'] ?? '',
    'sender_email' => $fieldValues['email'] ?? '',
    'subject' => $fieldValues['subject'] ?? '',
    'reason' => normalizeContactReasonLabel($fieldValues['reason'] ?? ''),
    'message_preview' => substr($fieldValues['message'] ?? '', 0, 500),
    'ip_address' => resolveContactClientIpAddress(),
    'browser_user_agent' => resolveContactBrowserAndDevice(),
    'session_id' => $sessionId,
    'fingerprint' => buildContactClientFingerprintHash(),
    'include_browser_device' => ($options['include_browser_device'] ?? false) ? 1 : 0,
    'include_ip_address' => ($options['include_ip_address'] ?? false) ? 1 : 0,
    'include_language_region' => ($options['include_language_region'] ?? false) ? 1 : 0,
  ];
}

/**
 * @return array<string>
 */
function buildContactCooldownKeys(): array
{
  $keys = [];

  $currentUserUuid = User::currentUUID();
  if ($currentUserUuid !== '' && $currentUserUuid !== SystemConfig::PUBLIC_UUID) {
    $keys[] = 'contact:cooldown:user:' . $currentUserUuid;
  }

  $sessionId = session_id();
  if ($sessionId !== '') {
    $keys[] = 'contact:cooldown:session:' . $sessionId;
  }

  $fingerprint = buildContactClientFingerprintHash();
  if ($fingerprint !== '') {
    $keys[] = 'contact:cooldown:fingerprint:' . $fingerprint;
  }

  return array_values(array_unique($keys));
}

function applyContactCooldownLocks(int $ttlSeconds): void
{
  $ttl = max(1, $ttlSeconds);
  $now = (string) time();

  try {
    foreach (buildContactCooldownKeys() as $key) {
      Database::set($key, $now, $ttl);
    }
  } catch (\Throwable) {
    // Preserve UX even when Redis is unavailable.
  }

  $_SESSION['contact_last_sent_at'] = time();
}

function getContactSendCooldownRemainingSeconds(): int
{
  $remaining = 0;

  try {
    foreach (buildContactCooldownKeys() as $key) {
      $ttl = Database::ttl($key);
      if ($ttl > $remaining) {
        $remaining = $ttl;
      }
    }
  } catch (\Throwable) {
    // Fallback to session clock when Redis is unavailable.
  }

  if ($remaining > 0) {
    return $remaining;
  }

  if (!isset($_SESSION['contact_last_sent_at'])) {
    return 0;
  }

  $lastSentAtRaw = $_SESSION['contact_last_sent_at'];
  $lastSentAt = is_scalar($lastSentAtRaw) ? (int) $lastSentAtRaw : 0;
  if ($lastSentAt <= 0) {
    unset($_SESSION['contact_last_sent_at']);
    return 0;
  }

  $elapsed = time() - $lastSentAt;
  $sessionRemaining = CONTACT_SEND_COOLDOWN_SECONDS - $elapsed;

  if ($sessionRemaining <= 0) {
    unset($_SESSION['contact_last_sent_at']);
    return 0;
  }

  return $sessionRemaining;
}

function buildContactClientFingerprintHash(): string
{
  $ip = resolveContactClientIpAddress();
  $agent = resolveContactBrowserAndDevice();
  $language = resolveContactLanguageAndRegion();

  return hash('sha256', implode('|', [$ip, $agent, $language]));
}

function issueContactFormToken(): string
{
  $token = bin2hex(random_bytes(32));

  try {
    Database::set(buildContactFormTokenKey($token), (string) time(), CONTACT_FORM_TOKEN_TTL_SECONDS);
  } catch (\Throwable) {
    $_SESSION['contact_form_token_fallback'] = $token;
  }

  return $token;
}

function consumeContactFormToken(string $token): bool
{
  if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    return false;
  }

  $key = buildContactFormTokenKey($token);

  try {
    if (!Database::exists($key)) {
      return false;
    }

    Database::unlink($key);
    return true;
  } catch (\Throwable) {
    $fallbackRaw = $_SESSION['contact_form_token_fallback'] ?? '';
    $fallback = is_scalar($fallbackRaw) ? (string) $fallbackRaw : '';
    if ($fallback === '' || !hash_equals($fallback, $token)) {
      return false;
    }

    unset($_SESSION['contact_form_token_fallback']);
    return true;
  }
}

function buildContactFormTokenKey(string $token): string
{
  $sessionId = session_id();
  if ($sessionId === '') {
    $sessionId = hash('sha256', buildContactClientFingerprintHash());
  }

  return 'contact:form_token:' . $sessionId . ':' . $token;
}

function formatContactCooldownDuration(int $seconds): string
{
  $normalized = max(0, $seconds);
  $minutes = intdiv($normalized, 60);
  $remainder = $normalized % 60;

  return sprintf('%d:%02d', $minutes, $remainder);
}

function resolveContactClientIpAddress(): string
{
  $serverCandidates = [
    $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
    $_SERVER['HTTP_X_REAL_IP'] ?? null,
    $_SERVER['REMOTE_ADDR'] ?? null,
  ];

  $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
  if (is_string($forwardedFor) && $forwardedFor !== '') {
    foreach (explode(',', $forwardedFor) as $entry) {
      $serverCandidates[] = trim($entry);
    }
  }

  foreach ($serverCandidates as $candidate) {
    $sanitized = InputSanitizer::sanitizeIPAddress($candidate);
    if ($sanitized !== 'unknown') {
      return $sanitized;
    }
  }

  return 'unknown';
}

function resolveContactBrowserAndDevice(): string
{
  $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
  if (!is_string($agent) || trim($agent) === '') {
    return 'unknown';
  }

  return trim(InputSanitizer::stripControls($agent));
}

function resolveContactLanguageAndRegion(): string
{
  $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
  if (!is_string($acceptLanguage) || trim($acceptLanguage) === '') {
    return 'unknown';
  }

  // Keep the first preferred language tag for concise diagnostics.
  $first = trim(explode(',', $acceptLanguage)[0]);
  if ($first === '') {
    return 'unknown';
  }

  return trim(InputSanitizer::stripControls($first));
}

function normalizeContactReasonLabel(string $reason): string
{
  return match (strtolower(trim($reason))) {
    'account' => contactI18n('CONTACT_REASON_ACCOUNT_LABEL'),
    'bug' => contactI18n('CONTACT_REASON_BUG_LABEL'),
    'feature' => contactI18n('CONTACT_REASON_FEATURE_LABEL'),
    default => contactI18n('CONTACT_REASON_GENERAL_LABEL'),
  };
}

function sanitizeContactMessage(string $input): string
{
  $normalized = preg_replace('/\r\n?/', "\n", $input) ?? '';
  $normalized = InputSanitizer::stripControls($normalized);

  return trim($normalized);
}

/**
 * @return array{name:string,email:string,subject:string,message:string,reason:string}
 */
function bootstrapContactFieldValuesFromGet(): array
{
  $allowedReasons = ['general', 'account', 'bug', 'feature'];

  $reasonRaw = InputSanitizer::getString('reason');
  $reason = is_string($reasonRaw)
    ? strtolower(trim($reasonRaw))
    : '';
  if (!in_array($reason, $allowedReasons, true)) {
    $reason = '';
  }

  $subjectRaw = InputSanitizer::getString('subject');
  $subject = is_string($subjectRaw)
    ? trim($subjectRaw)
    : '';
  $subject = mb_substr(InputSanitizer::stripControls($subject), 0, 180);

  $messageRaw = InputSanitizer::getString('message');
  $message = sanitizeContactMessage(is_string($messageRaw) ? $messageRaw : '');
  $message = mb_substr($message, 0, 6000);

  return [
    'name' => '',
    'email' => '',
    'subject' => $subject,
    'message' => $message,
    'reason' => $reason,
  ];
}

$formStatus = '';
$formStatusType = 'info';
$formFieldErrors = [];
$formFieldValues = bootstrapContactFieldValuesFromGet();
$contactCooldownDuration = CONTACT_SEND_COOLDOWN_SECONDS;
$contactCooldownRemaining = getContactSendCooldownRemainingSeconds();
$contactFormToken = issueContactFormToken();

$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$isXHR = InputSanitizer::postString('pc_method') === 'xhr';

if ($isPost) {
  [$ok, $statusMessage, $fieldErrors, $fieldValues, $cooldownRemaining] = handleContactSubmission();
  $formStatus = $statusMessage;
  $formStatusType = $ok ? 'success' : 'error';
  $formFieldErrors = $fieldErrors;
  $formFieldValues = $fieldValues;
  $contactCooldownRemaining = $cooldownRemaining > 0 ? $cooldownRemaining : getContactSendCooldownRemainingSeconds();
  $contactFormToken = issueContactFormToken();

  if ($isXHR) {
    if ($ok) {
      Response::success($statusMessage, [
        'fieldErrors' => [],
        'cooldownRemaining' => $contactCooldownRemaining,
        'cooldownDuration' => $contactCooldownDuration,
        'formToken' => $contactFormToken,
      ]);
    }

    Response::error($statusMessage, [
      'fieldErrors' => $fieldErrors,
      'cooldownRemaining' => $contactCooldownRemaining,
      'cooldownDuration' => $contactCooldownDuration,
      'formToken' => $contactFormToken,
    ]);
  }
}

// --- Load Page ---
require_once HTML.'/header.php';

require_once dirname(__DIR__, 2).'/templates/contact-page.php';

echo \PayCal\Domain\Render::jsScript('contact');

require_once HTML.'/footer.php';
