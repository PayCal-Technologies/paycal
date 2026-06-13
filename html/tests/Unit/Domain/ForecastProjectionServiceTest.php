<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\ForecastAssumptionSource;
use PayCal\Domain\ForecastProjectionService;
use PayCal\Domain\ForecastScenario;
use PayCal\Domain\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ForecastProjectionServiceTest extends TestCase
{
  private function sampleUser(float $payRate = 52.0): User
  {
    $user = new User();
    $user->pay_rate = (string) $payRate;
    $user->province = 'AB';
    $user->pay_frequency = 'biweekly';
    $user->pay_period_start = '2026-01-05';
    $user->default_hours = '8';
    $user->default_living_out_allowance = '150';
    $user->user_uuid = 'forecast-test-user-' . bin2hex(random_bytes(4));

    return $user;
  }

  #[Test]
  public function buildStateReturnsCentsBasedSummaryCards(): void
  {
    $state = (new ForecastProjectionService())->buildState($this->sampleUser());

    $this->assertTrue($state['can_calculate']);
    $cards = $state['forecast_state']['projection_result']['summary_cards'];
    $this->assertArrayHasKey('next_paycheck', $cards);
    $this->assertIsInt($cards['next_paycheck']['net_cents']);
    $this->assertIsInt($cards['next_paycheck']['gross_cents']);
    $this->assertGreaterThan(0, $cards['next_paycheck']['net_cents']);
    $this->assertLessThanOrEqual($cards['next_paycheck']['gross_cents'], $cards['next_paycheck']['net_cents']);
  }

  #[Test]
  public function calculatorOverrideTakesPriorityOverProfile(): void
  {
    $user = $this->sampleUser(40.0);
    $service = new ForecastProjectionService();

    $baseline = $service->preview($user, [], ForecastScenario::Normal);
    $overridden = $service->preview($user, ['wage_rate_cents' => 6000], ForecastScenario::Custom);

    $baselineNet = (int) $baseline['forecast_state']['projection_result']['summary_cards']['next_30_days']['net_cents'];
    $overrideNet = (int) $overridden['forecast_state']['projection_result']['summary_cards']['next_30_days']['net_cents'];

    $this->assertGreaterThan($baselineNet, $overrideNet);

    $sources = $overridden['forecast_state']['assumption_sources'];
    $wageRow = null;
    foreach ($sources as $row) {
      if (($row['field'] ?? '') === 'wage_rate_cents') {
        $wageRow = $row;
        break;
      }
    }
    $this->assertNotNull($wageRow);
    $this->assertSame(ForecastAssumptionSource::Temporary->value, $wageRow['source']);
  }

  #[Test]
  public function incompleteProfileRendersGracefully(): void
  {
    $user = new User();
    $user->pay_rate = '0';
    $user->user_uuid = 'forecast-empty-' . bin2hex(random_bytes(4));

    $state = (new ForecastProjectionService())->buildState($user);

    $this->assertTrue($state['setup_required']);
    $this->assertFalse($state['can_calculate']);
    $this->assertSame(0, $state['forecast_state']['projection_result']['summary_cards']['next_paycheck']['net_cents']);
  }

  #[Test]
  public function conservativeScenarioProjectsLowerNetThanOvertime(): void
  {
    $user = $this->sampleUser();
    $service = new ForecastProjectionService();

    $conservative = $service->preview($user, [], ForecastScenario::Conservative);
    $overtime = $service->preview($user, [], ForecastScenario::Overtime);

    $consNet = (int) $conservative['forecast_state']['projection_result']['scenarios']['conservative']['net_cents'];
    $otNet = (int) $overtime['forecast_state']['projection_result']['scenarios']['overtime']['net_cents'];

    $this->assertGreaterThan($consNet, $otNet);
  }

  #[Test]
  public function previewDoesNotMutateUserProfile(): void
  {
    $user = $this->sampleUser(55.0);
    $beforeRate = (string) $user->pay_rate;
    $beforeProvince = (string) $user->province;

    (new ForecastProjectionService())->preview($user, [
      'wage_rate_cents' => 9900,
      'province' => 'ON',
      'regular_hours_week' => 50,
    ], ForecastScenario::Custom);

    $this->assertSame($beforeRate, (string) $user->pay_rate);
    $this->assertSame($beforeProvince, (string) $user->province);
  }
}
