<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Extensions\HookBus;
use PayCal\Domain\SecurityLog;

/**
 * @internal
 */
#[Group('unit')]
final class SecurityLogTest extends TestCase
{
  public static function setUpBeforeClass(): void
  {
    require_once __DIR__ . '/../../extensions/runtime.php';
  }

  #[Test]
  public function logRateLimitTriggeredDoesNotThrow(): void
  {
    $this->expectNotToPerformAssertions();

    SecurityLog::logRateLimitTriggered('user:calendar', 'Utest-user', 0);
  }

  #[Test]
  public function logDispatchesSecurityAuditEventHook(): void
  {
    $ref = new \ReflectionProperty(HookBus::class, 'listeners');
    /** @var array<string, array<int, array{priority:int, callback:callable, source:string}>> $originalListeners */
    $originalListeners = $ref->getValue();

    $capturedPayloads = [];

    try {
      $ref->setValue(null, []);

      HookBus::register('security.audit_event', static function (array $payload) use (&$capturedPayloads): null {
        $capturedPayloads[] = $payload;
        return null;
      }, 100, 'test:security-log');

      SecurityLog::log('unit.security.hook', ['foo' => 'bar']);

      $this->assertCount(1, $capturedPayloads);
      $this->assertSame('unit.security.hook', $capturedPayloads[0]['event'] ?? null);
      $this->assertSame(['foo' => 'bar'], $capturedPayloads[0]['context'] ?? null);
      $this->assertIsInt($capturedPayloads[0]['timestamp'] ?? null);
    } finally {
      $ref->setValue(null, $originalListeners);
    }
  }
}
