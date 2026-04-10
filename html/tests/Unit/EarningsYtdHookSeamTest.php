<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Extensions\HookBus;

/**
 * Contract tests for the earnings.ytd.render hook seam.
 *
 * Purpose: Verify that the basic earnings-ytd extension hook produces the
 * expected HTML contract when dispatched, and that the HookBus integration
 * returns the first non-empty result for this hook name.
 *
 * Located here because it tests cross-cutting behavior between the extension
 * hook registration (extensions/basic/earnings-ytd/bootstrap.php) and the
 * HookBus dispatch seam consumed by Earnings::renderYearToDateSummary().
 */
#[Group('unit')]
final class EarningsYtdHookSeamTest extends TestCase
{
  /** @var array<string, array<int, array{priority:int, callback:callable, source:string}>> */
  private array $originalListeners;

  public static function setUpBeforeClass(): void
  {
    require_once __DIR__ . '/../../extensions/runtime.php';
    require_once __DIR__ . '/../../extensions/basic/earnings-ytd/hooks.php';
  }

  protected function setUp(): void
  {
    parent::setUp();
    $this->originalListeners = $this->readHookBusListeners();
    $this->writeHookBusListeners([]);
  }

  protected function tearDown(): void
  {
    $this->writeHookBusListeners($this->originalListeners);
    parent::tearDown();
  }

  #[Test]
  public function basicEarningsYtdHookProducesNonEmptyHtml(): void
  {
    HookBus::register(
      'earnings.ytd.render',
      [\PayCal\Extensions\Basic\EarningsYtd\Hooks::class, 'render'],
      100,
      'extension:earnings-ytd:basic'
    );

    $payload = [
      '__EARNINGS_YTD_ID__' => 'ytd-test',
      '__EARNINGS_YTD_ARIA_LABEL__' => 'Year to Date',
      '__REGULAR_HOURS__' => '80.00',
      '__OVERTIME_HOURS__' => '5.00',
      '__HOURS__' => 'hrs',
      '__REGULAR__' => 'Regular',
      '__OVERTIME__' => 'Overtime',
      '__GROSS_LABEL__' => 'Gross',
      '__EARNINGS_TOTAL_DEDUCTIONS__' => 'Deductions',
      '__NET_LABEL__' => 'Net',
      '__GROSS__' => '4800.00',
      '__TOTAL_DEDUCTIONS__' => '1200.00',
      '__NET__' => '3600.00',
    ];

    $results = HookBus::dispatch('earnings.ytd.render', ['year' => 2026, 'payload' => $payload, 'mode' => 'auto']);

    $this->assertCount(1, $results, 'Exactly one hook result expected from basic extension.');
    $html = $results[0];
    $this->assertIsString($html);
    $this->assertNotEmpty(trim($html));
  }

  #[Test]
  public function basicEarningsYtdHookOutputContainsExpectedStructure(): void
  {
    HookBus::register(
      'earnings.ytd.render',
      [\PayCal\Extensions\Basic\EarningsYtd\Hooks::class, 'render'],
      100,
      'extension:earnings-ytd:basic'
    );

    $payload = [
      '__EARNINGS_YTD_ID__' => 'ytd-structure-test',
      '__EARNINGS_YTD_ARIA_LABEL__' => 'YTD Test',
      '__REGULAR_HOURS__' => '40.00',
      '__OVERTIME_HOURS__' => '0.00',
      '__HOURS__' => 'hours',
      '__REGULAR__' => 'Regular',
      '__OVERTIME__' => 'Overtime',
      '__GROSS_LABEL__' => 'Gross',
      '__EARNINGS_TOTAL_DEDUCTIONS__' => 'Deductions',
      '__NET_LABEL__' => 'Net Pay',
      '__GROSS__' => '3200.00',
      '__TOTAL_DEDUCTIONS__' => '800.00',
      '__NET__' => '2400.00',
    ];

    $results = HookBus::dispatch('earnings.ytd.render', ['year' => 2026, 'payload' => $payload]);
    $html = $results[0] ?? '';

    $this->assertStringContainsString('id="ytd-structure-test"', $html);
    $this->assertStringContainsString('role="region"', $html);
    $this->assertStringContainsString('<dl', $html);
    $this->assertStringContainsString('$3200.00', $html);
    $this->assertStringContainsString('$2400.00', $html);
  }

  #[Test]
  public function basicEarningsYtdHookEscapesXssPayload(): void
  {
    HookBus::register(
      'earnings.ytd.render',
      [\PayCal\Extensions\Basic\EarningsYtd\Hooks::class, 'render'],
      100,
      'extension:earnings-ytd:basic'
    );

    $payload = [
      '__EARNINGS_YTD_ID__' => 'xss-<script>',
      '__EARNINGS_YTD_ARIA_LABEL__' => '<img src=x onerror=alert(1)>',
      '__REGULAR_HOURS__' => '0',
      '__OVERTIME_HOURS__' => '0',
      '__HOURS__' => 'h',
      '__REGULAR__' => '<b>bad</b>',
      '__OVERTIME__' => 'OT',
      '__GROSS_LABEL__' => 'Gross',
      '__EARNINGS_TOTAL_DEDUCTIONS__' => 'Deductions',
      '__NET_LABEL__' => 'Net',
      '__GROSS__' => '0.00',
      '__TOTAL_DEDUCTIONS__' => '0.00',
      '__NET__' => '0.00',
    ];

    $results = HookBus::dispatch('earnings.ytd.render', ['year' => 2026, 'payload' => $payload]);
    $html = $results[0] ?? '';

    $this->assertStringNotContainsString('<script>', $html);
    $this->assertStringNotContainsString('<img src=x', $html);
    $this->assertStringNotContainsString('<b>bad</b>', $html);
    $this->assertStringContainsString('&lt;script&gt;', $html);
  }

  #[Test]
  public function dispatchWithNoRegisteredListenerReturnsEmptyResults(): void
  {
    $results = HookBus::dispatch('earnings.ytd.render', ['year' => 2026, 'payload' => []]);

    $this->assertSame([], $results, 'No listeners registered should yield empty dispatch results.');
  }

  /** @return array<string, array<int, array{priority:int, callback:callable, source:string}>> */
  private function readHookBusListeners(): array
  {
    $ref = new \ReflectionProperty(HookBus::class, 'listeners');
    /** @var array<string, array<int, array{priority:int, callback:callable, source:string}>> $listeners */
    $listeners = $ref->getValue();
    return $listeners;
  }

  /** @param array<string, array<int, array{priority:int, callback:callable, source:string}>> $listeners */
  private function writeHookBusListeners(array $listeners): void
  {
    $ref = new \ReflectionProperty(HookBus::class, 'listeners');
    $ref->setValue(null, $listeners);
  }
}
