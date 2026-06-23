<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * Maps legacy SecurityLog event names to Argus diagnostic events.
 */
final class SecurityEventCatalog
{
  /**
   * @return array{name: string, severity: DiagnosticSeverity}|null
   */
  public static function resolve(string $legacyEvent): ?array
  {
    $normalized = strtolower(trim($legacyEvent));
    if ($normalized === '') {
      return null;
    }

    $map = self::map();

    if (isset($map[$normalized])) {
      return $map[$normalized];
    }

    if (str_starts_with($normalized, 'billing_webhook_')) {
      $metric = substr($normalized, strlen('billing_webhook_'));
      $successMetrics = ['processed', 'duplicate'];
      $isSuccess = in_array($metric, $successMetrics, true);

      return [
        'name' => $isSuccess ? 'stripe.webhook_verified' : 'stripe.webhook_failed',
        'severity' => $isSuccess ? DiagnosticSeverity::Info : DiagnosticSeverity::Warn,
      ];
    }

    return null;
  }

  /**
   * @return array<string, array{name: string, severity: DiagnosticSeverity}>
   */
  private static function map(): array
  {
    static $map = null;
    if ($map !== null) {
      return $map;
    }

    $warn = DiagnosticSeverity::Warn;
    $info = DiagnosticSeverity::Info;
    $error = DiagnosticSeverity::Error;

    $map = [
      'rate_limit_triggered' => ['name' => 'request_guard.rate_limit_triggered', 'severity' => $warn],
      'entry_locked_attempt' => ['name' => 'lock_boundary.mutation_blocked', 'severity' => $warn],
      'redis_tier0_alert' => ['name' => 'redis.tier0_alert', 'severity' => $error],
      'stripe_webhook_queue_alert' => ['name' => 'stripe.webhook_queue_alert', 'severity' => $warn],
      'csp_violation' => ['name' => 'security.csp_violation', 'severity' => $warn],
      'protected_mode_activated' => ['name' => 'auth.protected_mode_activated', 'severity' => $info],
      'protected_mode_mutation_blocked' => ['name' => 'auth.protected_mode_mutation_blocked', 'severity' => $warn],
      'account_data_export' => ['name' => 'account.data_export', 'severity' => $info],
      'account_data_import_prepared' => ['name' => 'account.data_import_prepared', 'severity' => $info],
      'account_data_import_committed' => ['name' => 'account.data_import_committed', 'severity' => $warn],
      'earnings_export' => ['name' => 'account.earnings_export', 'severity' => $info],
      'verification_code_email_send_failed' => ['name' => 'auth.verification_email_failed', 'severity' => $warn],
      'verification_link_email_send_failed' => ['name' => 'auth.verification_email_failed', 'severity' => $warn],
      'change_email_started' => ['name' => 'auth.change_email_started', 'severity' => $info],
      'change_email_start_send_failed' => ['name' => 'auth.change_email_failed', 'severity' => $warn],
      'change_email_start_exception' => ['name' => 'auth.change_email_failed', 'severity' => $error],
      'email_changed' => ['name' => 'auth.email_changed', 'severity' => $info],
      'change_email_verify_exception' => ['name' => 'auth.change_email_failed', 'severity' => $error],
      'change_email_codes_resent' => ['name' => 'auth.change_email_resent', 'severity' => $info],
      'change_email_resend_exception' => ['name' => 'auth.change_email_failed', 'severity' => $error],
      'change_email_cancelled' => ['name' => 'auth.change_email_cancelled', 'severity' => $info],
      'change_email_cancel_exception' => ['name' => 'auth.change_email_failed', 'severity' => $error],
      'email_verification_attempt' => ['name' => 'auth.email_verification_attempt', 'severity' => $info],
      'email_verification_failed' => ['name' => 'auth.email_verification_failed', 'severity' => $warn],
      'email_verification_already_verified' => ['name' => 'auth.email_verification_redundant', 'severity' => $info],
      'email_verified' => ['name' => 'auth.email_verified', 'severity' => $info],
      'verification_auto_verified_local' => ['name' => 'auth.email_verified', 'severity' => $info],
      'verification_code_generation_failed' => ['name' => 'auth.verification_email_failed', 'severity' => $error],
      'verification_email_resent' => ['name' => 'auth.verification_email_resent', 'severity' => $info],
      'verification_email_resent_failed' => ['name' => 'auth.verification_email_failed', 'severity' => $warn],
      'verification_email_resent_primary_exception' => ['name' => 'auth.verification_email_failed', 'severity' => $error],
      'verification_email_resent_fallback' => ['name' => 'auth.verification_email_resent', 'severity' => $info],
      'verification_email_resent_fallback_exception' => ['name' => 'auth.verification_email_failed', 'severity' => $error],
      'verification_email_resend_redis_exception' => ['name' => 'auth.verification_email_failed', 'severity' => $error],
      'verification_email_resend_exception' => ['name' => 'auth.verification_email_failed', 'severity' => $error],
      'recovery_key_generation_skipped' => ['name' => 'auth.recovery_key_skipped', 'severity' => $info],
      'recovery_key_sent' => ['name' => 'auth.recovery_key_sent', 'severity' => $info],
      'recovery_key_send_failed' => ['name' => 'auth.recovery_key_failed', 'severity' => $warn],
      'recovery_key_created_from_settings' => ['name' => 'auth.recovery_key_created', 'severity' => $info],
      'recovery_key_created_from_settings_email_failed' => ['name' => 'auth.recovery_key_failed', 'severity' => $warn],
      'recovery_key_created_from_settings_email_exception' => ['name' => 'auth.recovery_key_failed', 'severity' => $error],
      'passkey_signup_success' => ['name' => 'auth.passkey_signup_success', 'severity' => $info],
      'passkey_revoked_reregistration_blocked' => ['name' => 'auth.passkey_reregistration_blocked', 'severity' => $warn],
      'passkey_registered' => ['name' => 'auth.passkey_registered', 'severity' => $info],
      'passkey_malformed_credential_rejected' => ['name' => 'auth.passkey_rejected', 'severity' => $warn],
      'passkey_malformed_challenge_rejected' => ['name' => 'auth.passkey_rejected', 'severity' => $warn],
      'passkey_clone_suspected' => ['name' => 'auth.passkey_clone_suspected', 'severity' => $error],
      'passkey_login_success' => ['name' => 'auth.passkey_login_success', 'severity' => $info],
      'passkey_renamed' => ['name' => 'auth.passkey_renamed', 'severity' => $info],
      'passkey_deleted' => ['name' => 'auth.passkey_deleted', 'severity' => $info],
      'passkey_recovery_email_requested' => ['name' => 'auth.passkey_recovery_requested', 'severity' => $info],
      'verification_email_sent' => ['name' => 'auth.verification_email_sent', 'severity' => $info],
      'verification_email_failed' => ['name' => 'auth.verification_email_failed', 'severity' => $warn],
      'recovery_email_start_cooldown_triggered' => ['name' => 'auth.recovery_email_cooldown', 'severity' => $warn],
      'recovery_email_start_max_resends_exceeded' => ['name' => 'auth.recovery_email_rate_limited', 'severity' => $warn],
      'recovery_email_verification_started' => ['name' => 'auth.recovery_email_started', 'severity' => $info],
      'recovery_email_verification_send_failed' => ['name' => 'auth.recovery_email_failed', 'severity' => $warn],
      'recovery_email_start_exception' => ['name' => 'auth.recovery_email_failed', 'severity' => $error],
      'recovery_email_verify_max_attempts' => ['name' => 'auth.recovery_email_failed', 'severity' => $warn],
      'recovery_email_verified' => ['name' => 'auth.recovery_email_verified', 'severity' => $info],
      'recovery_email_verify_exception' => ['name' => 'auth.recovery_email_failed', 'severity' => $error],
      'recovery_email_resent' => ['name' => 'auth.recovery_email_resent', 'severity' => $info],
      'recovery_email_resend_exception' => ['name' => 'auth.recovery_email_failed', 'severity' => $error],
      'billing_customer_cleanup_subscription_canceled' => ['name' => 'stripe.customer_cleanup_success', 'severity' => $info],
      'billing_customer_cleanup_subscription_cancel_failed' => ['name' => 'stripe.customer_cleanup_failed', 'severity' => $warn],
      'billing_customer_cleanup_failed' => ['name' => 'stripe.customer_cleanup_failed', 'severity' => $error],
    ];

    return $map;
  }
}
