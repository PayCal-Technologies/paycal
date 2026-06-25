<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CalendarCryptoIdleTimeoutContractTest extends TestCase
{
  #[Test]
  public function calendarIdleZeroizeUsesCalendarEditTtlPreference(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $calendarJs = (string) file_get_contents($htmlRoot . '/js/calendar/calendar.js');
    $coreJs = (string) file_get_contents($htmlRoot . '/js/core/index.php');

    $this->assertStringContainsString('form_ttl_calendar_seconds', $calendarJs);
    $this->assertStringNotContainsString('session_timeout_seconds * 1000', $calendarJs);
    $this->assertStringContainsString('paycal:security-timeouts-updated', $calendarJs);
    $this->assertStringContainsString('payCalRequiresPasskeyStepUp = true', $calendarJs);
    $this->assertStringContainsString('payCalDekEnsureInteractiveRequested', $calendarJs);
    $this->assertStringContainsString('performPasskeyStepUpReauth', $calendarJs);
    $this->assertStringContainsString('preferredCredentialId', $calendarJs);
    $this->assertStringContainsString('wrappedDekCandidates', $calendarJs);
    $this->assertStringContainsString('dek_unwrap_failed', $calendarJs);
    $this->assertStringContainsString('CALENDAR_REAUTH_DEK_UNLOCK_FAILED', $calendarJs);
    $this->assertStringContainsString('config.form_ttl_calendar_seconds = Number(next.form_ttl_calendar_seconds', $coreJs);
  }

  #[Test]
  public function calendarJsParsesWithoutSyntaxErrors(): void
  {
    $calendarJsPath = dirname(__DIR__, 3) . '/js/calendar/calendar.js';
    $output = [];
    $exitCode = 0;
    exec('node --check ' . escapeshellarg($calendarJsPath) . ' 2>&1', $output, $exitCode);

    $this->assertSame(
      0,
      $exitCode,
      'calendar.js must parse cleanly: ' . implode("\n", $output)
    );
  }

  #[Test]
  public function calendarReauthI18nKeysExistInAllLocales(): void
  {
    $repoRoot = dirname(__DIR__, 4);
    $keys = [
      'CALENDAR_REAUTH_SESSION_TITLE',
      'CALENDAR_REAUTH_SESSION_MESSAGE',
      'CALENDAR_REAUTH_SESSION_ARIA',
      'CALENDAR_REAUTH_CANCELLED',
      'CALENDAR_REAUTH_FAILED',
      'CALENDAR_REAUTH_DEK_UNLOCK_FAILED',
    ];

    foreach (glob($repoRoot . '/strings/*.txt') as $localeFile) {
      $contents = (string) file_get_contents($localeFile);
      foreach ($keys as $key) {
        $this->assertStringContainsString(
          $key . ' ',
          $contents,
          sprintf('Missing %s in %s', $key, basename($localeFile))
        );
      }
    }
  }
}
