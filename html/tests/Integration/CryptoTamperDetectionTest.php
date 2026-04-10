<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 *
 */
#[Group('integration')]
#[Group('crypto')]
#[Group('skip')]
final class CryptoTamperDetectionTest extends TestCase
{
  public function testTamperedChainFailsVerification(): void
  {
    $fixture = 'scripts/crypto/tampered.example.json';
    if (!file_exists($fixture)) {
      $this->markTestSkipped('Fixture tampered.example.json not present');
    }
    exec(escapeshellarg(PHP_BINARY) . ' /scripts/crypto/verify_chain.php ' . escapeshellarg($fixture), $out, $code);
    if (0 === $code) {
      fwrite(STDERR, "\n[verify_chain.php output]\n".implode("\n", $out)."\n");
    }
    $this->assertNotSame(0, $code, 'Tampered chain should fail verification');
  }
}
