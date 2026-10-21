<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PayRulesBrowserTest extends TestCase
{
  #[Test]
  public function reconciledHoursAndPremiumRatesSurviveBrowserHydration(): void
  {
    $fixture = dirname(__DIR__, 2) . '/Fixtures/pay-rules-browser.mjs';
    $output = [];
    $status = 0;
    exec('node ' . escapeshellarg($fixture) . ' 2>&1', $output, $status);
    $this->assertSame(0, $status, implode("\n", $output));
  }
}
