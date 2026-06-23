<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * AuthTrace — Argus instrumentation for passkey signup/login flows.
 *
 * Privacy: never pass raw email, names, tokens, or UUIDs. Use emailToken() for correlation.
 */
final class AuthTrace
{
  /**
   * Create a rotating email correlation token without exposing the email.
   */
  public static function emailToken(string $email): string
  {
    $normalized = strtolower(trim($email));
    if ($normalized === '') {
      return 'unknown';
    }

    return substr(hash('sha256', $normalized . '|' . date('Y-m-d-H')), 0, 16);
  }

  /**
   * @param array<string, scalar|null> $context
   */
  public static function point(string $event, DiagnosticSeverity $severity, array $context = []): void
  {
    Argus::emit($event, $severity, $context);
  }

  /**
   * @param array<string, scalar|null> $context
   */
  public static function signupStart(string $step, array $context = []): void
  {
    self::point('auth.signup.start', DiagnosticSeverity::Info, array_merge(['step' => $step], $context));
  }

  /**
   * @param array<string, scalar|null> $context
   */
  public static function signupRejected(string $phase, string $reason, array $context = []): void
  {
    self::point('auth.signup.rejected', DiagnosticSeverity::Warn, array_merge([
      'phase' => $phase,
      'reason' => $reason,
    ], $context));
  }

  /**
   * @param array<string, scalar|null> $context
   */
  public static function signupCompleted(array $context = []): void
  {
    self::point('auth.signup.completed', DiagnosticSeverity::Info, $context);
  }

  /**
   * @param array<string, scalar|null> $context
   */
  public static function signupVerificationEmail(array $context = []): void
  {
    self::point('auth.signup.verification_email', DiagnosticSeverity::Info, $context);
  }

  /**
   * @param array<string, scalar|null> $context
   */
  public static function loginRejected(string $phase, string $reason, array $context = []): void
  {
    self::point('auth.login.rejected', DiagnosticSeverity::Warn, array_merge([
      'phase' => $phase,
      'reason' => $reason,
    ], $context));
  }

  /**
   * @param array<string, scalar|null> $context
   */
  public static function loginCompleted(array $context = []): void
  {
    self::point('auth.login.completed', DiagnosticSeverity::Info, $context);
  }

  /**
   * Emit a rate-limit diagnostic event for an auth endpoint.
   */
  public static function rateLimited(string $endpoint): void
  {
    self::point('auth.rate_limited', DiagnosticSeverity::Warn, [
      'endpoint' => $endpoint,
    ]);
  }
}
