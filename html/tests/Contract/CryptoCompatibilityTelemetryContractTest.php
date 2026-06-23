<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
final class CryptoCompatibilityTelemetryContractTest extends TestCase
{
  public function testCryptoCompatibilityAuditIsRegisteredInScriptSuite(): void
  {
    $paycal = (string) file_get_contents(__DIR__ . '/../../../scripts/paycal');

    $this->assertStringContainsString('crypto:compat:audit [--json]', $paycal);
    $this->assertStringContainsString('crypto-compat-audit.php', $paycal);
  }

  public function testRuntimeTelemetryWhitelistsWrapperCompatibilityCounters(): void
  {
    $telemetry = (string) file_get_contents(__DIR__ . '/../../src/Domain/CryptoCompatibilityTelemetry.php');

    foreach ([
      'wrapper_present:personal_current',
      'wrapper_present:personal_legacy',
      'legacy_wrapper_blocked:personal_legacy',
      'unwrap_success:personal_current',
      'unwrap_failure:business_current',
      'plaintext_fallback:work_totals_range',
      'plaintext_fallback:daily_year',
      'telemetry:encryption:{$schema}:compat:{$counter}',
    ] as $needle) {
      $this->assertStringContainsString($needle, $telemetry);
    }
  }

  public function testUnwrapCallersRecordCompatibilitySourceBeforeLegacyRemoval(): void
  {
    foreach ([
      __DIR__ . '/../../src/Domain/Earnings.php',
      __DIR__ . '/../../src/Controllers/EarningsController.php',
      __DIR__ . '/../../src/Controllers/SitesController.php',
    ] as $path) {
      $source = (string) file_get_contents($path);

      $this->assertStringContainsString('SOURCE_PERSONAL_CURRENT', $source);
      $this->assertStringContainsString('SOURCE_PERSONAL_LEGACY', $source);
      $this->assertStringContainsString('legacyWrapperBlocked', $source);
      $this->assertStringContainsString('unwrapSuccess', $source);
      $this->assertStringContainsString('unwrapFailure', $source);
    }

    foreach ([
      __DIR__ . '/../../src/Domain/Earnings.php',
      __DIR__ . '/../../src/Controllers/EarningsController.php',
    ] as $path) {
      $source = (string) file_get_contents($path);

      $this->assertStringContainsString('plaintextFallback', $source);
    }
  }
}
