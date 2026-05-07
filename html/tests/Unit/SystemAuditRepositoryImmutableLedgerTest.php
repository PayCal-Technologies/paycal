<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Infrastructure\Audit\SystemAuditRepository;
use PHPUnit\Framework\TestCase;

/**
 * SystemAuditRepositoryImmutableLedgerTest
 *
 * Validates immutable hash-chain behavior for system audit events.
 */
final class SystemAuditRepositoryImmutableLedgerTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();

    // Test isolation for immutable-ledger keyspace.
    Database::del('system:audit:ledger:*');
    Database::del('system:audit:anchor:*');
    Database::unlink(Keys::SYSTEM_AUDIT_BLOCKCHAIN . ':anchor_queue');
    Database::unlink(Keys::SYSTEM_AUDIT);
    Database::del('system:audit:event:*');
  }

  protected function tearDown(): void
  {
    Database::del('system:audit:ledger:*');
    Database::del('system:audit:anchor:*');
    Database::unlink(Keys::SYSTEM_AUDIT_BLOCKCHAIN . ':anchor_queue');
    Database::unlink(Keys::SYSTEM_AUDIT);
    Database::del('system:audit:event:*');

    parent::tearDown();
  }

  public function testAppendAddsImmutableLedgerProofFields(): void
  {
    $eventId = SystemAuditRepository::append(
      'user.auth_level.changed',
      'test-actor-' . bin2hex(random_bytes(4)),
      [
        'target_user_uuid' => 'test-target-' . bin2hex(random_bytes(4)),
        'from_level' => '10',
        'to_level' => '50',
      ]
    );

    $recent = SystemAuditRepository::recent(10);
    $this->assertNotEmpty($recent);

    $event = null;
    foreach ($recent as $row) {
      if (($row['event_id'] ?? '') === $eventId) {
        $event = $row;
        break;
      }
    }

    $this->assertIsArray($event);
    $this->assertNotSame('', (string) ($event['ledger_sequence'] ?? ''));
    $this->assertNotSame('', (string) ($event['ledger_block_hash'] ?? ''));
    $this->assertSame(128, strlen((string) ($event['ledger_block_hash'] ?? '')));

    $proof = SystemAuditRepository::proofForEvent($eventId);
    $this->assertTrue((bool) ($proof['has_proof'] ?? false));
    $this->assertNotSame('', (string) ($proof['block_hash'] ?? ''));
    $this->assertNotSame('', (string) ($proof['previous_hash'] ?? ''));
  }

  public function testVerifyImmutableLedgerReturnsVerifiedAfterAppend(): void
  {
    SystemAuditRepository::append(
      'user.account.deleted',
      'test-actor-' . bin2hex(random_bytes(4)),
      [
        'target_user_uuid' => 'test-target-' . bin2hex(random_bytes(4)),
        'reason' => 'test',
      ]
    );

    $verification = SystemAuditRepository::verifyImmutableLedger();

    $this->assertTrue((bool) ($verification['ok'] ?? false));
    $this->assertSame('verified', (string) ($verification['reason'] ?? ''));
    $this->assertGreaterThan(0, (int) ($verification['checked_blocks'] ?? 0));
  }
}
