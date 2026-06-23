<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
final class WorkEntryPlaintextAuditScriptContractTest extends TestCase
{
  public function testWorkPlaintextAuditIsRegisteredInScriptSuite(): void
  {
    $paycal = (string) file_get_contents(__DIR__ . '/../../../scripts/paycal');

    $this->assertStringContainsString('work:plaintext:audit [--json] [--include-archived] [--include-ignored] [--sample-limit=N]', $paycal);
    $this->assertStringContainsString('work-entry-plaintext-audit.php', $paycal);
  }

  public function testWorkPlaintextAuditExplainsZeroKnowledgeMigrationBoundary(): void
  {
    $script = (string) file_get_contents(__DIR__ . '/../../../scripts/work-entry-plaintext-audit.php');

    foreach ([
      'server_can_auto_encrypt_plaintext_rows',
      'Work rows need the user DEK/passkey context',
      'snapshot_fields_are_intentional',
      'WORK_PLAINTEXT_AUDIT_TEST_SEED_EMAIL_DOMAINS',
      'ignored_test_seed_domain_plaintext_entries',
      'rows_requiring_user_session_rewrite',
      'ready_to_remove_plaintext_read_compatibility',
      'key_fp',
      'user_fp',
      'site_fp',
    ] as $needle) {
      $this->assertStringContainsString($needle, $script);
    }
  }

  public function testOldPlaintextMigrationCommandIsRetiredInsteadOfStrippingSnapshots(): void
  {
    $script = (string) file_get_contents(__DIR__ . '/../../../scripts/paycal');

    $this->assertStringContainsString('crypto:migrate-plaintext is retired', $script);
    $this->assertStringContainsString('work-entry-plaintext-audit.php', $script);
    $this->assertStringContainsString('use work:plaintext:audit and a user-session encrypted rewrite plan instead', $script);
    $this->assertStringNotContainsString('Database::hdel', $script);
  }
}
