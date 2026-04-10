<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: frontend/backend response shapes are flat and do not rely on
 * compatibility fallbacks for deprecated nested data envelopes.
 */
#[Group('contract')]
final class NetworkTrafficContractTest extends TestCase
{
  public function testCalendarControllerDoesNotRewrapCalendarPayloadUnderData(): void
  {
    $controller = $this->readProjectFile('src/Controllers/CalendarController.php');

    $this->assertStringNotContainsString("['data' => \$calendarData]", $controller);
    $this->assertStringContainsString("Response::success('[CC] Calendar data retrieved.', \$calendarData", $controller);
  }

  public function testLegacyCalendarLoaderUsesFlatPayloadShape(): void
  {
    $calendarLoader = $this->readProjectFile('js/calendar/index.php');

    $this->assertStringNotContainsString('calendarData = data.data;', $calendarLoader);
    $this->assertStringNotContainsString("data.status !== 'success' || !data.data", $calendarLoader);
    $this->assertStringNotContainsString('const responseData = (payload && typeof payload === \'object\' && payload.data && typeof payload.data === \'object\')', $calendarLoader);
    $this->assertStringContainsString("if (payload.status !== 'success' || !payload || !payload.weeks)", $calendarLoader);
    $this->assertStringContainsString('calendarData = payload;', $calendarLoader);
  }

  public function testBillingFrontendsUseFlatPayloadsOnly(): void
  {
    $organizationsJs = $this->readProjectFile('js/organizations/index.php');
    $settingsJs = $this->readProjectFile('js/settings/index.php');

    $this->assertStringNotContainsString('payload.data?.checkout_url', $organizationsJs);
    $this->assertStringNotContainsString('payload.data?.portal_url', $organizationsJs);
    $this->assertStringNotContainsString('subData = payload.data', $organizationsJs);
    $this->assertStringNotContainsString('const responseData = (payload && typeof payload === \'object\' && payload.data && typeof payload.data === \'object\')', $organizationsJs);
    $this->assertStringContainsString('const subData = billingController.subscription;', $organizationsJs);
    $this->assertStringContainsString('initializeBillingSection({', $organizationsJs);

    $this->assertStringNotContainsString('payload.data?.checkout_url', $settingsJs);
    $this->assertStringNotContainsString('payload.data?.portal_url', $settingsJs);
    $this->assertStringNotContainsString('subData = payload.data', $settingsJs);
    $this->assertStringNotContainsString('const responseData = (payload && typeof payload === \'object\' && payload.data && typeof payload.data === \'object\')', $settingsJs);
    $this->assertStringContainsString('initializeBillingSection({', $settingsJs);
  }

  public function testAccountRecoveryStartResponseAndFrontendAreFlatOnly(): void
  {
    $recoveryController = $this->readProjectFile('src/Controllers/AccountRecoveryController.php');
    $recoveryFrontend = $this->readProjectFile('js/auth-recovery/index.php');

    $this->assertStringNotContainsString("'data' => [", $recoveryController);
    $this->assertStringContainsString("'txnId' =>", $recoveryController);
    $this->assertStringContainsString("'txnSecret' =>", $recoveryController);

    $this->assertStringNotContainsString('state.txnId = payload.data?.txnId', $recoveryFrontend);
    $this->assertStringNotContainsString('state.txnSecret = payload.data?.txnSecret', $recoveryFrontend);
    $this->assertStringNotContainsString('const responseData = (payload && typeof payload === \'object\' && payload.data && typeof payload.data === \'object\')', $recoveryFrontend);
      $this->assertStringContainsString("state.txnId = payload?.txnId || '';", $recoveryFrontend);
      $this->assertStringContainsString("state.txnSecret = payload?.txnSecret || '';", $recoveryFrontend);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
