<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * CryptoTamperDetectionTest — verifies that the verify_chain.php script rejects
 * a chain whose hmac has been deliberately tampered.
 *
 * Why this exists here:
 * - verify_chain.php is a CLI tool; testing it through a subprocess is the
 *   only reliable way to assert its exit code.
 * - The tampered fixture is generated in-test so no on-disk file is required
 *   and the test is always runnable without external setup.
 *
 * Previously gated by a missing on-disk tampered.example.json; that dependency
 * has been replaced by an in-memory fixture generated in setUp().
 */
#[Group('integration')]
#[Group('crypto')]
final class CryptoTamperDetectionTest extends TestCase
{
  private string $verifyScript;
  private string $tmpFixture;

  protected function setUp(): void
  {
    parent::setUp();

    $this->verifyScript = dirname(__DIR__, 3) . '/scripts/crypto/verify_chain.php';
    if (!file_exists($this->verifyScript)) {
      $this->markTestSkipped('verify_chain.php not present in scripts/crypto/');
    }

    // Build a two-entry chain following verify_chain.php's hash-chain algorithm:
    //   HMAC_n = hash_hmac('sha256', json_encode(ksorted_entry_without_hmac), HMAC_{n-1})
    // Entry 1 has a valid HMAC; entry 2 has a deliberately wrong HMAC to simulate tampering.
    $entry1 = [
      'event'     => 'key_generated',
      'payload'   => json_encode(['key_id' => 'test-key-1']),
      'seq'       => 1,
      'timestamp' => '2026-01-01T00:00:00Z',
    ];
    ksort($entry1);
    $hmac1 = hash_hmac('sha256', (string) json_encode($entry1), '');
    $entry1['hmac'] = $hmac1;

    $chain = [
      $entry1,
      [
        'seq'       => 2,
        'timestamp' => '2026-01-02T00:00:00Z',
        'event'     => 'key_rotated',
        'payload'   => json_encode(['key_id' => 'test-key-2']),
        'hmac'      => 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef', // deliberately wrong
      ],
    ];

    $this->tmpFixture = tempnam(sys_get_temp_dir(), 'paycal_tamper_') . '.json';
    file_put_contents($this->tmpFixture, json_encode($chain, JSON_PRETTY_PRINT));
  }

  protected function tearDown(): void
  {
    if (isset($this->tmpFixture) && file_exists($this->tmpFixture)) {
      unlink($this->tmpFixture);
    }
    parent::tearDown();
  }

  public function testTamperedChainFailsVerification(): void
  {
    exec(
      escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($this->verifyScript) . ' ' . escapeshellarg($this->tmpFixture),
      $out,
      $code
    );
    if (0 === $code) {
      fwrite(STDERR, "\n[verify_chain.php output]\n" . implode("\n", $out) . "\n");
    }
    $this->assertNotSame(0, $code, 'Tampered chain should fail verification — verify_chain.php returned 0');
  }
}

