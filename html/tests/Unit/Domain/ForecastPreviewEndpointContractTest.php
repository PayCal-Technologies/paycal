<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Controllers\EarningsController;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ForecastPreviewEndpointContractTest extends TestCase
{
  #[Test]
  public function earningsControllerForecastPreviewHasNoPersistence(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $controller = (string) file_get_contents($projectRoot . '/html/src/Controllers/EarningsController.php');

    $this->assertStringContainsString("Route('forecast/preview', ['POST'])", $controller);
    $this->assertStringContainsString('postForecastPreview', $controller);
    $this->assertStringContainsString('ForecastProjectionService', $controller);

    if (preg_match('/function postForecastPreview\(\): void\s*\{(.*?)\n  \}/s', $controller, $matches) !== 1) {
      $this->fail('postForecastPreview method not found');
    }
    $methodBody = $matches[1];
    $this->assertStringNotContainsString('Database::', $methodBody);
    $this->assertStringNotContainsString('EarningsCacheService', $methodBody);
  }

  #[Test]
  public function earningsForecastAndPaidExportFormatsRequirePremiumReporting(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $controller = (string) file_get_contents($projectRoot . '/html/src/Controllers/EarningsController.php');

    $this->assertStringContainsString('use PayCal\Domain\SubscriptionRepository;', $controller);
    $this->assertStringContainsString('currentUserCanExportFormat', $controller);
    $this->assertStringContainsString('Premium subscription required for this export format.', $controller);
    $this->assertStringContainsString('currentUserHasPremiumReporting', $controller);
    $this->assertStringContainsString('Premium subscription required for Forecast.', $controller);
  }

  #[Test]
  public function forecastProjectionServiceHasNoRedisWrites(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $service = (string) file_get_contents($projectRoot . '/html/src/Domain/ForecastProjectionService.php');

    $this->assertStringNotContainsString('Database::set', $service);
    $this->assertStringNotContainsString('Database::hset', $service);
    $this->assertStringNotContainsString('EarningsCacheService', $service);
  }

  #[Test]
  public function forecastWorkspaceRendererUsesI18nKeys(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $renderer = (string) file_get_contents($projectRoot . '/html/src/Domain/ForecastWorkspaceRenderer.php');

    $this->assertStringContainsString('EARNINGS_FORECAST_TITLE', $renderer);
    $this->assertStringContainsString('EARNINGS_FORECAST_BADGE_ESTIMATE', $renderer);
    $this->assertStringContainsString('EARNINGS_FORECAST_BADGE_NOT_CRA', $renderer);
    $this->assertStringNotContainsString('style=', $renderer);
  }
}
