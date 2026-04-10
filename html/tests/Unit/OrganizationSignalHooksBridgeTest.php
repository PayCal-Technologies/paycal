<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Extensions\HookBus;
use PayCal\Domain\OrganizationSignalHooks;

/**
 * Ensures organization audit events flow through HookBus seam.
 */
#[Group('unit')]
final class OrganizationSignalHooksBridgeTest extends TestCase
{
  /** @var array<string, array<int, array{priority:int, callback:callable, source:string}>> */
  private array $originalListeners;

  public static function setUpBeforeClass(): void
  {
    require_once __DIR__ . '/../../extensions/runtime.php';
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
  public function onOrganizationAuditEventDispatchesHookBusPayload(): void
  {
    $captured = [];

    HookBus::register('organization.audit_event', static function (array $payload) use (&$captured): null {
      $captured = $payload;
      return null;
    }, 100, 'tests:org-bridge');

    $event = [
      'organization_id' => 'ORG123',
      'event_type' => 'access.requested',
      'event_id' => 'EVT123',
      'details' => '{"scope":"test"}',
    ];

    OrganizationSignalHooks::onOrganizationAuditEvent($event);

    $this->assertArrayHasKey('event', $captured);
    $this->assertSame($event, $captured['event']);
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
