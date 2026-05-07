<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Constants\Keys;
use PayCal\Infrastructure\Email\EmailTransport;
use PayCal\Infrastructure\Telemetry\SecurityLog;
use PayCal\Observability\Lens;

/**
 * EmailGarum.php
 *
 * Purpose: Provide the centralized outbound email orchestration layer for
 * account lifecycle and security-critical communication flows.
 *
 * Why this class exists:
 * - Keep all transactional email behavior in one place so templates,
 *   addressing, transport configuration, and fallback behavior are consistent.
 * - Reduce duplicated mail composition logic across controllers.
 * - Enforce predictable, auditable messaging for verification, recovery,
 *   change-email, and support workflows.
 *
 * What it offers:
 * - High-level send helpers for each account/security flow.
 * - Integration with templated HTML and plain-text render paths.
 * - Guard rails around delivery outcomes and operational logging.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * EmailGarum
 *
 * Domain mail courier for PayCal account and security notifications.
 *
 * Design intent:
 * - Separate business workflow decisions (controllers/services) from email
 *   composition and transport concerns.
 * - Keep customer-facing messages uniform across all auth/recovery journeys.
 * - Make delivery behavior easier to test by exposing stable, flow-specific
 *   methods instead of ad-hoc mail assembly.
 *
 * Operational value:
 * - Ensures critical mail flows use approved templates and links.
 * - Concentrates delivery logging and error paths for faster troubleshooting.
 * - Supports maintainable evolution of template standards without touching
 *   every caller.
 *
 * @see https://en.wikipedia.org/wiki/Chapar_Khaneh Chapar Khaneh — the original mail transport infrastructure
 * @see Herodotus, Histories 8.98 Historical inspiration for relay-post resilience.
 */
class EmailGarum
{
  /**
   * Resolve the application name without requiring legacy global constants.
   */
  private static function appName(): string
  {
    $value = $_ENV['PC_NAME'] ?? getenv('PC_NAME') ?: '';
    $name = is_string($value) ? trim($value) : '';

    if ($name !== '') {
      return $name;
    }

    if (defined('PC_NAME')) {
      $definedName = trim((string) constant('PC_NAME'));
      if ($definedName !== '') {
        return $definedName;
      }
    }

    return 'PayCal';
  }

  /**
   * Emit explicit verification email debug telemetry for troubleshooting delivery failures.
   *
   * @param array<string, mixed> $context
   */
  private static function logVerificationEmailDebug(string $stage, array $context = []): void
  {
    try {
      Lens::add('Verification email debug: ' . $stage, $context, 'verification_email_debug');
    } catch (\Throwable) {
      // Lens failures should never interrupt email flow.
    }

    try {
      $encoded = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      error_log('[VerificationEmailDebug] ' . $stage . ' ' . ($encoded === false ? '{}' : $encoded));
    } catch (\Throwable) {
      error_log('[VerificationEmailDebug] ' . $stage);
    }
  }

  /**
   * Verifies and updates a new user's email address.
   * This function performs the following steps:
   * 1. Checks the user session, the new email, and the Redis instance.
   * 2. Validates the provided password against the user's stored password hash.
   * 3. If the password is valid, updates the user's new email and sends a verification code via email.
   *
   * @note USED WHEN USER IS CHANGING THEIR EXISTING EMAIL TO A NEW ONE
   * Only after they receive and then submit their verification code will the system change their actual email address
   *
   * @param string $password        the user's current password
   * @param string $newEmail        the new email address to be verified and updated
   * @param string $confirmNewEmail the confirmation of the new email address
   *
   * @return \stdClass An object representing the status of the email verification and update process.
   *                  The object contains 'status' (SUCCESS or ERROR) and 'message' (details about the status).
   */
  public static function verifyNewUserEmail(string $password, string $newEmail, string $confirmNewEmail): \stdClass
  {
    $status = new \stdClass();

    // TODO: Create DEFINITIONS or admin configurable settings in place of hard coded text strings
    $status->stage = 'pre-init';
    $status->message = 'Not initiialized yet.';


    $hash = Authentication::getSessionHashFromCookie();
    if ($hash === null || !Authentication::sessionExists($hash) || empty($newEmail)) {
      return $status;
    }

    // Get user UUID defensively
    $userUuid = User::currentUUID();
    if ('' === $userUuid) {
      $status->stage = 'error';
      $status->message = 'No user UUID available.';

      return $status;
    }

    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL) || !filter_var($confirmNewEmail, FILTER_VALIDATE_EMAIL)) {
      $status->stage = 'error';
      $status->message = 'Invalid email format.';

      return $status;
    }

    $user = User::getByUUID($userUuid);
    if (null === $user) {
      $status->stage = 'error';
      $status->message = 'User not found.';

      return $status;
    }

    if (!password_verify($password, $user->password_hash)) {
      $status->stage = 'logged-in';
      $status->message = 'Invalid Password.';

      return $status;
    }

    if ($newEmail !== $confirmNewEmail) {
      return $status;
    }

    $verificationCode = Security::generateVerificationCode(6);
    User::addVerificationCode($verificationCode, $userUuid);
    $emailResult = self::emailVerificationCode(verificationCode: $verificationCode, emailTo: $newEmail);

    if (str_contains($emailResult, 'Successfully')) {
      Database::hset(Keys::USER.':'.$userUuid, ['new_email' => $newEmail]);
      $status->status = 'SUCCESS';
      $status->message = 'User Email update in progress. Verification code needed.';
      $status->stage = 'verification_code_input';
      $status->message = 'Waiting for verification code step.';
    } else {
      $status->stage = 'error';
      $status->message = 'Failed to send verification email.';
    }

    return $status;
  }

  /**
   * Sends an email containing a verification code.
   *
   * @param string $verificationCode the verification code to be included in the email
   * @param string $emailTo          the primary recipient's email address
   * @param string $emailCC          an optional CC recipient's email address
   *
   * @return string status message indicating success or failure of the email sending process
   */
  public static function emailVerificationCode(string $verificationCode, string $emailTo, string $emailCC = ''): string
  {
    $appName = self::appName();
    $subject = '['.$appName.'] - Email Verification Code';
    $issuedAt = gmdate('Y-m-d H:i:s') . ' UTC';
    $supportToken = strtoupper(substr(hash('sha256', $emailTo.'|'.$verificationCode.'|'.$issuedAt), 0, 12));
    self::logVerificationEmailDebug('code_email_prepare', [
      'email_to' => $emailTo,
      'email_cc' => $emailCC,
      'app_env' => Environment::appEnv(),
      'smtp_server' => Environment::smtpServer(),
      'smtp_port' => Environment::smtpPort(),
      'from' => Environment::emailReplyTo(),
      'subject' => $subject,
      'code_length' => strlen($verificationCode),
    ]);

    $templateData = [
      '__VERIFICATION_CODE__' => htmlspecialchars($verificationCode),
      '__ACCOUNT_EMAIL__' => htmlspecialchars($emailTo),
      '__SOURCE_URL__' => htmlspecialchars(self::resolveVerificationBaseUrl()),
      '__ISSUED_AT__' => htmlspecialchars($issuedAt),
      '__SUPPORT_TOKEN__' => htmlspecialchars($supportToken),
      '__PC_NAME__' => $appName,
    ];

    $htmlBody = Render::template('email-verification-code-html', $templateData);
    $textBody = Render::template('email-verification-code-text', $templateData);

    $transport = new EmailTransport();
    $bcc = !empty(Environment::emailDebug()) ? [Environment::emailDebug()] : [];
    
    $sent = $transport->send(
      to: $emailTo,
      subject: $subject,
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: Environment::emailReplyTo(),
      bcc: $bcc
    );

    if ($sent) {
      self::logVerificationEmailDebug('code_email_send_success', [
        'email_to' => $emailTo,
        'subject' => $subject,
      ]);

      return 'Email Sent Successfully.';
    }

    self::logVerificationEmailDebug('code_email_send_failed', [
      'email_to' => $emailTo,
      'subject' => $subject,
      'transport_error' => $transport->getLastError(),
    ]);

    SecurityLog::log('verification_code_email_send_failed', [
      'email_to' => $emailTo,
      'subject' => $subject,
      'transport_error' => $transport->getLastError(),
      'smtp_server' => Environment::smtpServer(),
      'smtp_port' => Environment::smtpPort(),
    ]);

    return 'Error sending email. Error Message: ' . $transport->getLastError();
  }

  /**
   * Sends a contact email using the provided sender information.
   *
   * @param string $fromName  the name of the person sending the email
   * @param string $fromEmail the email address of the sender
   * @param string $subject   the subject of the email
  * @param string $message   the message content of the email
  * @param string $topic     the selected contact topic label
   *
   * @return string status message indicating success or failure of the email sending process
   */
  public static function pcSendContactEmail(string $fromName, string $fromEmail, string $subject, string $message, string $topic = 'General'): string
  {
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
      return 'Invalid email format.';
    }

    $appName = self::appName();
    $topicShortName = self::contactTopicShortName($topic);

    $normalizedMessage = str_replace(["\r\n", "\r"], "\n", $message);

    $baseTemplateData = [
        '__SENDER_NAME__' => htmlspecialchars($fromName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        '__SENDER_EMAIL__' => htmlspecialchars($fromEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        '__PC_NAME__' => $appName,
        '__CONTACT_SUBJECT__' => htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      '__CONTACT_TOPIC__' => htmlspecialchars($topic, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
    ];

    $htmlTemplateData = $baseTemplateData + [
      '__MESSAGE_CONTENT__' => nl2br(htmlspecialchars($normalizedMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
    ];

    $textTemplateData = $baseTemplateData + [
      '__MESSAGE_CONTENT__' => $normalizedMessage,
    ];

    $htmlBody = Render::template('contact-email-html', $htmlTemplateData);
    $textBody = Render::template('contact-email-text', $textTemplateData);

    $transport = new EmailTransport();
    $bcc = !empty(Environment::emailDebug()) ? [Environment::emailDebug()] : [];
    
    $sent = $transport->send(
      to: Environment::emailContact(),
      subject: '['.$appName.'] ['.$topicShortName.'] '.$fromName.' - '.$subject,
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: Environment::emailReplyTo(),
      bcc: $bcc
    );

    if ($sent) {
      return 'Email Sent Successfully.';
    }

    return 'Error sending email. Error Message: ' . $transport->getLastError();
  }

  /**
   * Handles contactTopicShortName operation.
   */
  private static function contactTopicShortName(string $topic): string
  {
    $normalized = strtolower(trim($topic));

    return match (true) {
      $normalized === 'account' => 'ACCOUNT',
      $normalized === 'bug report',
      $normalized === 'bug' => 'BUG',
      $normalized === 'feature request',
      $normalized === 'feature' => 'FEATURE',
      default => 'GENERAL',
    };
  }

  /**
   * Send email verification link with magic token
   *
   * @param string $token    Verification token (raw, will be included in URL)
   * @param string $emailTo  Recipient email address
   * @param string $userName User's full name
   *
   * @return bool True if email sent successfully
   */
  public static function sendVerificationEmail(string $token, string $emailTo, string $userName = '', string $verificationCode = ''): bool
  {
    $appName = self::appName();
    $subject = '['.$appName.'] - Verify Your Email Address';
    $replyToRaw = trim(Environment::emailReplyTo());
    $fromAddress = filter_var($replyToRaw, FILTER_VALIDATE_EMAIL) ? $replyToRaw : 'info@paycal.app';
    $fromHeader = $appName . ' <' . $fromAddress . '>';

    // Build verification URL
    $baseUrl = self::resolveVerificationBaseUrl();
    $verificationUrl = $baseUrl.'/auth/verify-email/?token='.urlencode($token);

    $templateData = [
        '__USER_NAME__' => htmlspecialchars($userName ?: 'there'),
        '__VERIFICATION_URL__' => $verificationUrl,
      '__VERIFICATION_CODE__' => htmlspecialchars($verificationCode),
        '__PC_NAME__' => $appName,
    ];

    $htmlBody = Render::template('email-verification-link-html', $templateData);
    $textBody = Render::template('email-verification-link-text', $templateData);

    $transport = new EmailTransport();
    $bcc = !empty(Environment::emailDebug()) ? [Environment::emailDebug()] : [];
    $headers = [
      'Reply-To' => 'PayCal Support <support@paycal.app>',
      'List-Unsubscribe' => '<mailto:support@paycal.app?subject=unsubscribe>',
    ];

    self::logVerificationEmailDebug('link_email_prepare', [
      'email_to' => $emailTo,
      'app_env' => Environment::appEnv(),
      'smtp_server' => Environment::smtpServer(),
      'smtp_port' => Environment::smtpPort(),
      'from' => $fromHeader,
      'subject' => $subject,
      'code_length' => strlen($verificationCode),
      'token_length' => strlen($token),
    ]);
    
    $sent = $transport->send(
      to: $emailTo,
      subject: $subject,
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: $fromHeader,
      bcc: $bcc,
      headers: $headers
    );
    
    if ($sent) {
      self::logVerificationEmailDebug('link_email_send_success', [
        'email_to' => $emailTo,
        'subject' => $subject,
      ]);
      return $sent;
    }

    self::logVerificationEmailDebug('link_email_send_failed', [
      'email_to' => $emailTo,
      'subject' => $subject,
      'transport_error' => $transport->getLastError(),
    ]);
    SecurityLog::log('verification_link_email_send_failed', [
      'email_to' => $emailTo,
      'subject' => $subject,
      'transport_error' => $transport->getLastError(),
      'smtp_server' => Environment::smtpServer(),
      'smtp_port' => Environment::smtpPort(),
    ]);
    error_log('[Email] sendVerificationEmail failed: ' . $transport->getLastError());
    
    return $sent;
  }

  /**
   * Resolve the verification link base URL.
   *
   * Prefer the current request host to keep verification links environment-local
   * (for example: dev.paycal.app stays on dev). Fall back to configured app base URL
   * for CLI contexts where HTTP server variables are not available.
   */
  private static function resolveVerificationBaseUrl(): string
  {
    $forwardedHost = isset($_SERVER['HTTP_X_FORWARDED_HOST']) && is_string($_SERVER['HTTP_X_FORWARDED_HOST'])
      ? trim($_SERVER['HTTP_X_FORWARDED_HOST'])
      : '';
    $httpHost = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST'])
      ? trim($_SERVER['HTTP_HOST'])
      : '';
    $rawHost = $forwardedHost !== '' ? $forwardedHost : $httpHost;

    if ($rawHost === '') {
      return Environment::appBaseURL();
    }

    // If multiple hosts are provided by a proxy, use the first entry.
    $host = trim(explode(',', $rawHost)[0]);

    if (preg_match('/^[a-zA-Z0-9.:-]+$/', $host) !== 1) {
      return Environment::appBaseURL();
    }

    $forwardedProto = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && is_string($_SERVER['HTTP_X_FORWARDED_PROTO'])
      ? trim($_SERVER['HTTP_X_FORWARDED_PROTO'])
      : '';
    $https = isset($_SERVER['HTTPS']) && is_string($_SERVER['HTTPS'])
      ? strtolower($_SERVER['HTTPS'])
      : '';
    $scheme = $forwardedProto !== ''
      ? strtolower(explode(',', $forwardedProto)[0])
      : (($https !== '' && $https !== 'off') ? 'https' : 'http');

    if ($scheme !== 'http' && $scheme !== 'https') {
      $scheme = 'https';
    }

    return rtrim($scheme.'://'.$host, '/');
  }

  /**
   * Send recovery key via email
   *
   * @param string $recoveryKey Recovery key (formatted with dashes)
   * @param string $emailTo     Recipient email address
   * @param string $userName    User's full name
   *
   * @return bool True if email sent successfully
   */
  public static function sendRecoveryKeyEmail(string $recoveryKey, string $emailTo, string $userName = ''): bool
  {
    $appName = self::appName();
    $subject = '['.$appName.'] - Your Recovery Key';
    $issuedAt = gmdate('Y-m-d H:i:s') . ' UTC';
    $supportToken = strtoupper(substr(hash('sha256', $emailTo.'|'.$recoveryKey.'|'.$issuedAt), 0, 12));

    $templateData = [
        '__USER_NAME__' => htmlspecialchars($userName ?: 'there'),
        '__RECOVERY_KEY__' => htmlspecialchars($recoveryKey),
      '__ACCOUNT_EMAIL__' => htmlspecialchars($emailTo),
      '__SOURCE_URL__' => htmlspecialchars(self::resolveVerificationBaseUrl()),
      '__ISSUED_AT__' => htmlspecialchars($issuedAt),
      '__SUPPORT_TOKEN__' => htmlspecialchars($supportToken),
        '__PC_NAME__' => $appName,
    ];

    $htmlBody = Render::template('email-recovery-key-html', $templateData);
    $textBody = Render::template('email-recovery-key-text', $templateData);

    $transport = new EmailTransport();
    $bcc = !empty(Environment::emailDebug()) ? [Environment::emailDebug()] : [];
    
    return $transport->send(
      to: $emailTo,
      subject: $subject,
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: Environment::emailReplyTo(),
      bcc: $bcc
    );
  }

  /**
   * Handles sendAccountRecoveryCode operation.
   */
  public static function sendAccountRecoveryCode(string $emailTo, string $userName, string $code): bool
  {
    $appName = self::appName();
    $subject = '['.$appName.'] - Account Recovery Code';
    $ttlMinutes = (int) \PayCal\Domain\Config\SystemConfig::get('account_recovery_code_ttl_minutes');
    $templateData = [
      '__USER_NAME__' => htmlspecialchars($userName ?: 'there', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      '__VERIFICATION_CODE__' => htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      '__EXPIRES_IN_MINUTES__' => (string) $ttlMinutes,
      '__PC_NAME__' => $appName,
    ];

    $htmlBody = Render::template('email-account-recovery-code-html', $templateData);
    $textBody = Render::template('email-account-recovery-code-text', $templateData);
    $transport = new EmailTransport();
    $bcc = !empty(Environment::emailDebug()) ? [Environment::emailDebug()] : [];

    return $transport->send(
      to: $emailTo,
      subject: $subject,
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: Environment::emailReplyTo(),
      bcc: $bcc
    );
  }

  /**
   * Send account recovery magic link.
   */
  public static function sendAccountRecoveryMagicLink(string $emailTo, string $userName, string $magicLink, int $ttlMinutes): bool
  {
    $appName = self::appName();
    $subject = '[' . $appName . '] - Account Recovery Link';
    $templateData = [
      '__USER_NAME__' => htmlspecialchars($userName ?: 'there', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      '__RECOVERY_LINK__' => htmlspecialchars($magicLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      '__EXPIRES_IN_MINUTES__' => (string) max(1, $ttlMinutes),
      '__PC_NAME__' => $appName,
    ];

    $htmlBody = Render::template('email-account-recovery-magic-link-html', $templateData);
    $textBody = Render::template('email-account-recovery-magic-link-text', $templateData);
    $transport = new EmailTransport();
    $bcc = !empty(Environment::emailDebug()) ? [Environment::emailDebug()] : [];

    return $transport->send(
      to: $emailTo,
      subject: $subject,
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: Environment::emailReplyTo(),
      bcc: $bcc
    );
  }

  /**
   * Send recovery email verification code.
   *
   * @param string $emailTo  Recipient email address
   * @param string $userName User's full name
   * @param string $code     Verification code (4-6 digits)
   *
   * @return bool True if email sent successfully
   */
  public static function sendRecoveryEmailVerificationCode(string $emailTo, string $userName, string $code): bool
  {
    $appName = self::appName();
    $subject = '['.$appName.'] - Verify Recovery Email';
    $ttlMinutes = (int)\PayCal\Domain\Config\SystemConfig::get('recovery_email_code_ttl_minutes');

    $templateData = [
      '__USER_NAME__' => htmlspecialchars($userName ?: 'there'),
      '__VERIFICATION_CODE__' => htmlspecialchars($code),
      '__EXPIRES_IN_MINUTES__' => (string) $ttlMinutes,
      '__PC_NAME__' => $appName,
    ];

    $htmlBody = Render::template('email-recovery-email-code-html', $templateData);
    $textBody = Render::template('email-recovery-email-code-text', $templateData);

    $transport = new EmailTransport();
    $bcc = !empty(Environment::emailDebug()) ? [Environment::emailDebug()] : [];
    
    $sent = $transport->send(
      to: $emailTo,
      subject: $subject,
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: Environment::emailReplyTo(),
      bcc: $bcc
    );
    
    if (!$sent) {
      error_log('[Email] sendRecoveryEmailVerificationCode failed: ' . $transport->getLastError());
    }
    
    return $sent;
  }

  /**
   * Send email change verification code to either old or new email.
   *
   * @param string $emailTo   Recipient email address
   * @param string $userName  User's full name
   * @param string $emailType 'old' or 'new' to indicate which address is receiving the code
   * @param string $code      Verification code (4-6 digits)
   * @param string $txnId     Transaction ID for reference
   *
   * @return bool True if email sent successfully
   */
  public static function sendChangeEmailCode(string $emailTo, string $userName, string $emailType, string $code, string $txnId): bool
  {
    $appName = self::appName();
    $subject = '['.$appName.'] - Email Change Verification Code';
    $ttlMinutes = (int)\PayCal\Domain\Config\SystemConfig::get('email_change_code_ttl_minutes');
    $emailTypeLabel = $emailType === 'old' ? 'current' : 'new';

    $templateData = [
      '__USER_NAME__' => htmlspecialchars($userName ?: 'there'),
      '__EMAIL_TYPE__' => htmlspecialchars($emailTypeLabel),
      '__VERIFICATION_CODE__' => htmlspecialchars($code),
      '__EXPIRES_IN_MINUTES__' => (string) $ttlMinutes,
      '__TXN_ID__' => htmlspecialchars($txnId),
      '__PC_NAME__' => $appName,
    ];

    $templateName = 'email-change-code-' . $emailType . '-html';
    $templateNameText = 'email-change-code-' . $emailType . '-text';

    $htmlBody = Render::template($templateName, $templateData);
    $textBody = Render::template($templateNameText, $templateData);

    $transport = new EmailTransport();
    $bcc = !empty(Environment::emailDebug()) ? [Environment::emailDebug()] : [];
    
    $sent = $transport->send(
      to: $emailTo,
      subject: $subject,
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: Environment::emailReplyTo(),
      bcc: $bcc
    );
    
    if (!$sent) {
      error_log('[Email] sendChangeEmailCode failed: ' . $transport->getLastError());
    }
    
    return $sent;
  }

  /**
   * Send email change confirmation to old and new email addresses.
   *
   * @param string $emailTo        Recipient email address
   * @param string $userName       User's full name
   * @param string $otherEmail     The other email address involved in the change
   * @param string $emailContext   'old' or 'new' to customize message
   *
   * @return bool True if email sent successfully
   */
  public static function sendEmailChangeConfirmation(string $emailTo, string $userName, string $otherEmail, string $emailContext): bool
  {
    $appName = self::appName();
    $subject = '['.$appName.'] - Email Change Notification';
    $contextLabel = $emailContext === 'old' ? 'changed to' : 'changed from';

    $templateData = [
      '__USER_NAME__' => htmlspecialchars($userName ?: 'there'),
      '__CONTEXT_LABEL__' => $contextLabel,
      '__OTHER_EMAIL__' => htmlspecialchars($otherEmail),
      '__CHANGE_TIME__' => gmdate('Y-m-d H:i:s') . ' UTC',
      '__PC_NAME__' => $appName,
    ];

    $templateName = 'email-change-confirmation-' . $emailContext . '-html';
    $templateNameText = 'email-change-confirmation-' . $emailContext . '-text';

    $htmlBody = Render::template($templateName, $templateData);
    $textBody = Render::template($templateNameText, $templateData);

    $transport = new EmailTransport();
    $bcc = !empty(Environment::emailDebug()) ? [Environment::emailDebug()] : [];
    
    $sent = $transport->send(
      to: $emailTo,
      subject: $subject,
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: Environment::emailReplyTo(),
      bcc: $bcc
    );
    
    if (!$sent) {
      error_log('[Email] sendEmailChangeConfirmation failed: ' . $transport->getLastError());
    }
    
    return $sent;
  }

  /**
   * Send organization access invite email.
   *
   * @param string           $inviteToken      Invite acceptance token
   * @param string           $inviteeEmail     Recipient email address
   * @param string           $organizationName Organization display name
   * @param string           $inviterName      Name of inviting user
   * @param array<int,string> $scopes          Permission scopes requested
   */
  public static function sendOrganizationInvite(
    string $inviteToken,
    string $inviteeEmail,
    string $organizationName,
    string $inviterName,
    array $scopes,
    ?string $batchCode = null
  ): bool {
    $normalizedBatchCode = strtoupper(trim((string) ($batchCode ?? '')));
    if (preg_match('/^[A-Z0-9]{3}$/', $normalizedBatchCode) !== 1) {
      $normalizedBatchCode = self::generateInviteBatchCode();
    }

    $subject = '[PayCal] Organization Access Invite (Batch ' . $normalizedBatchCode . ')';

    $baseUrl = Environment::appBaseURL();
    $acceptUrl = rtrim($baseUrl, '/') . '/profile/?org_invite_token=' . urlencode($inviteToken);
    $scopeList = implode(', ', $scopes);

    $templateData = [
      '__PC_NAME__' => self::appName(),
      '__INVITER_NAME__' => htmlspecialchars($inviterName ?: 'A PayCal user', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      '__ORGANIZATION_NAME__' => htmlspecialchars($organizationName ?: 'Organization', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      '__SCOPE_LIST__' => htmlspecialchars($scopeList, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      '__ACCEPT_URL__' => htmlspecialchars($acceptUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
    ];

    $htmlBody = Render::template('email-organization-invite-html', $templateData);
    $textBody = Render::template('email-organization-invite-text', $templateData);

    $transport = new EmailTransport();
    $bcc = !empty(Environment::emailDebug()) ? [Environment::emailDebug()] : [];

    $sent = $transport->send(
      to: $inviteeEmail,
      subject: $subject,
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: Environment::emailReplyTo(),
      bcc: $bcc
    );

    if (!$sent) {
      error_log('[Email] sendOrganizationInvite failed: ' . $transport->getLastError());
    }

    return $sent;
  }

  /**
   * Notify an organization owner that a user requested access.
   */
  public static function sendOrganizationAccessRequest(
    string $ownerEmail,
    string $organizationName,
    string $requesterName,
    string $requesterEmail,
    string $requestId
  ): bool {
    $subject = '[PayCal] Organization Access Request';

    $reviewUrl = rtrim(Environment::appBaseURL(), '/') . '/organizations/';
    $templateData = [
      '__PC_NAME__' => self::appName(),
      '__ORGANIZATION_NAME__' => htmlspecialchars($organizationName ?: 'Organization', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      '__REQUESTER_NAME__' => htmlspecialchars($requesterName ?: 'PayCal user', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      '__REQUESTER_EMAIL__' => htmlspecialchars($requesterEmail ?: 'not-provided', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      '__REQUEST_ID__' => htmlspecialchars($requestId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      '__REVIEW_URL__' => htmlspecialchars($reviewUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      '__REQUESTED_AT_UTC__' => gmdate('Y-m-d H:i:s') . ' UTC',
    ];

    $htmlBody = Render::template('email-organization-access-request-html', $templateData);
    $textBody = Render::template('email-organization-access-request-text', $templateData);

    $transport = new EmailTransport();
    $bcc = !empty(Environment::emailDebug()) ? [Environment::emailDebug()] : [];

    $sent = $transport->send(
      to: $ownerEmail,
      subject: $subject,
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: Environment::emailReplyTo(),
      bcc: $bcc
    );

    if (!$sent) {
      error_log('[Email] sendOrganizationAccessRequest failed: ' . $transport->getLastError());
    }

    return $sent;
  }

  /**
   * Send organization activity notification email.
   */
  public static function sendOrganizationEventNotification(
    string $emailTo,
    string $recipientName,
    string $organizationName,
    string $eventLabel,
    string $eventDetail,
    string $eventTimeUTC
  ): bool {
    if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
      return false;
    }

    $appName = self::appName();
    $subject = '[' . $appName . '] Organization Notification: ' . $eventLabel;
    $orgSafe = htmlspecialchars($organizationName ?: 'Organization', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $nameSafe = htmlspecialchars($recipientName ?: 'there', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $eventSafe = htmlspecialchars($eventLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $detailSafe = htmlspecialchars($eventDetail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $timeSafe = htmlspecialchars($eventTimeUTC !== '' ? $eventTimeUTC : (gmdate('Y-m-d H:i:s') . ' UTC'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $url = htmlspecialchars(rtrim(Environment::appBaseURL(), '/') . '/organizations/', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $detailLineHtml = $detailSafe !== '' ? '<p style="margin:0 0 14px;">' . $detailSafe . '</p>' : '';
    $detailLineText = $eventDetail !== '' ? "\n{$eventDetail}\n" : "\n";

    $htmlBody = '<!doctype html><html><body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;line-height:1.45;color:#1f2937;">'
      . '<h2 style="margin:0 0 12px;">Organization Notification</h2>'
      . '<p style="margin:0 0 14px;">Hello ' . $nameSafe . ',</p>'
      . '<p style="margin:0 0 14px;"><strong>' . $eventSafe . '</strong> in <strong>' . $orgSafe . '</strong>.</p>'
      . $detailLineHtml
      . '<p style="margin:0 0 14px;">Event time (UTC): ' . $timeSafe . '</p>'
      . '<p style="margin:0 0 16px;"><a href="' . $url . '">Open Organizations</a></p>'
      . '<p style="margin:0;color:#6b7280;">' . htmlspecialchars($appName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' automated notification.</p>'
      . '</body></html>';

    $textBody = "Organization Notification\n\n"
      . "Hello {$recipientName},\n\n"
      . "{$eventLabel} in {$organizationName}.{$detailLineText}"
      . "Event time (UTC): " . ($eventTimeUTC !== '' ? $eventTimeUTC : (gmdate('Y-m-d H:i:s') . ' UTC')) . "\n"
      . 'Open Organizations: ' . rtrim(Environment::appBaseURL(), '/') . '/organizations/' . "\n\n"
      . $appName . ' automated notification.';

    $transport = new EmailTransport();
    $bcc = !empty(Environment::emailDebug()) ? [Environment::emailDebug()] : [];

    $sent = $transport->send(
      to: $emailTo,
      subject: $subject,
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: Environment::emailReplyTo(),
      bcc: $bcc
    );

    if (!$sent) {
      error_log('[Email] sendOrganizationEventNotification failed: ' . $transport->getLastError());
    }

    return $sent;
  }

  /**
   * Handles generateInviteBatchCode operation.
   */
  private static function generateInviteBatchCode(): string
  {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';

    for ($i = 0; $i < 3; $i++) {
      $index = random_int(0, strlen($alphabet) - 1);
      $code .= $alphabet[$index];
    }

    return $code;
  }
}


