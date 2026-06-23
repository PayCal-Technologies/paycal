<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
final class UserConnectionsAuditScriptContractTest extends TestCase
{
  public function testUserConnectionsAuditIsRegisteredInScriptSuite(): void
  {
    $paycal = (string) file_get_contents(__DIR__ . '/../../../scripts/paycal');

    $this->assertStringContainsString('user:connections:audit [--connection connectionId] [--fix] [--json]', $paycal);
    $this->assertStringContainsString('user-connections-audit.php', $paycal);
  }

  public function testUserConnectionsAuditCoversPersonConnectionIndexesAndGrants(): void
  {
    $script = (string) file_get_contents(__DIR__ . '/../../../scripts/user-connections-audit.php');

    foreach ([
      'USER_CONNECTIONS_OWNER',
      'USER_CONNECTIONS_TARGET',
      'USER_CONNECTIONS_PENDING',
      'USER_CONNECTION_ACTIVE',
      'USER_CONNECTION_GRANTS',
      'USER_CONNECTION_GRANT',
      'CAPABILITY_CALENDAR_VIEW',
      'CAPABILITY_TRUSTED_RECOVERY',
      'grant_active_without_active_connection',
    ] as $needle) {
      $this->assertStringContainsString($needle, $script);
    }
  }
}
