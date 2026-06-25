<?php declare(strict_types=1);

namespace PayCal\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SecurityTimersContractTest extends TestCase
{
  #[Test]
  public function coreModuleExportsSharedSecurityTimers(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $coreJs = (string) file_get_contents($projectRoot . '/html/js/core/index.php');
    $securityTimersJs = (string) file_get_contents($projectRoot . '/html/js/core/security-timers.js');
    $calendarJs = (string) file_get_contents($projectRoot . '/html/js/calendar/calendar.js');
    $settingsJs = (string) file_get_contents($projectRoot . '/html/js/settings/index.php');
    $index = (string) file_get_contents($projectRoot . '/html/index.php');

    $this->assertStringContainsString("import createSecurityTimers from '/js/core/security-timers.js';", $coreJs);
    $this->assertStringContainsString('securityTimers,', $coreJs);
    $this->assertStringContainsString('getDekIdleTimeoutMs', $securityTimersJs);
    $this->assertStringContainsString('notifyDekZeroized', $calendarJs);
    $this->assertStringContainsString('notifyDekZeroizedToUser', $calendarJs);
    $this->assertStringContainsString('performPasskeyStepUpReauth', $calendarJs);
    $this->assertStringContainsString('PC.securityTimers', $settingsJs);
    $this->assertStringContainsString('id="modal_calendar_reauth"', $index);

    foreach ([
      'CALENDAR_REAUTH_SESSION_TITLE',
      'CALENDAR_REAUTH_SESSION_MESSAGE',
    ] as $key) {
      $this->assertStringContainsString("'{$key}'", $index);
    }

    $this->assertStringContainsString('SECURITY_DEK_ZEROIZED_TOAST', $coreJs);
  }
}
