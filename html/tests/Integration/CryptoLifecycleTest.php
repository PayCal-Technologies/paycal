<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Crypto\ChainVerifier;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 *
 */
#[Group('integration')]
#[Group('crypto')]
#[Group('stress')]
final class CryptoLifecycleTest extends TestCase
{
  private function resolveSimulationScript(): ?string
  {
    $candidates = [
      '/var/www/paycal/scripts/crypto/simulate_rotation.php',
      '/var/www/paycal-private/scripts/crypto/simulate_rotation.php',
      '/private/var/www/paycal/scripts/crypto/simulate_rotation.php',
      '/private/var/www/paycal/dev/scripts/crypto/simulate_rotation.php',
    ];

    foreach ($candidates as $candidate) {
      if (is_file($candidate)) {
        return $candidate;
      }
    }

    return null;
  }

  public function testRotationSimulation(): void
  {
    $script = $this->resolveSimulationScript();
    if (null === $script) {
      $this->markTestSkipped('simulate_rotation.php not present in this environment');
    }

    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' --json', $output, $exitCode);
    $this->assertSame(0, $exitCode);
    $json = implode("\n", $output);
    $this->assertNotEmpty($json, 'Simulation produced no output.');
    $periods = json_decode($json, true);
    $this->assertIsArray(
      $periods,
      "Decoded periods invalid. Raw output:\n".$json
    );
    $result = ChainVerifier::verify($periods);
    $this->assertTrue($result, 'Chain verification must pass');
  }

  #[Group('skip')]
  public function testRotationSimulationStress(): void
  {
    if ('1' !== getenv('CRYPTO_STRESS')) {
      $this->markTestSkipped('CRYPTO_STRESS not enabled');
    }

    $script = $this->resolveSimulationScript();
    if (null === $script) {
      $this->markTestSkipped('simulate_rotation.php not present in this environment');
    }

    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' --periods=1000 --stress', $out, $code);
    if (0 !== $code) {
      fwrite(STDERR, "\n[simulate_rotation.php --stress output]\n".implode("\n", $out)."\n");
    }
    $this->assertSame(0, $code, 'Rotation simulation (stress) must pass');
  }
}
