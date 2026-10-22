<?php declare(strict_types=1);

namespace Tests\Unit\Domain;

use PayCal\Domain\KeyMaterialVault;
use PHPUnit\Framework\TestCase;

final class KeyMaterialVaultTest extends TestCase
{
  public function testSealedValuesRemainReadableAndBoundToAccountAndCredential(): void
  {
    $previous = $_ENV['PAYCAL_KEY_MATERIAL_KEYRING_FILE'] ?? null;
    $file = tempnam(sys_get_temp_dir(), 'paycal-keyring-test-');
    try {
      chmod($file, 0600);
      file_put_contents($file, json_encode(['active' => 'test', 'keys' => ['test' => base64_encode(random_bytes(32))]], JSON_THROW_ON_ERROR));
      $_ENV['PAYCAL_KEY_MATERIAL_KEYRING_FILE'] = $file;
      $key = 'user:test-user:passkey_wrapped_deks';
      $plain = 'synthetic-wrapper';
      $sealed = KeyMaterialVault::seal($key, 'credential', $plain);
      self::assertStringStartsWith(KeyMaterialVault::PREFIX, $sealed);
      self::assertSame($plain, KeyMaterialVault::open($key, 'credential', $sealed));
      self::assertSame($plain, KeyMaterialVault::open($key, 'credential', $plain));
      foreach ([['user:other:passkey_wrapped_deks', 'credential'], [$key, 'other']] as [$wrongKey, $wrongField]) {
        $rejected = false;
        try {
          KeyMaterialVault::open($wrongKey, $wrongField, $sealed);
        } catch (\RuntimeException) {
          $rejected = true;
        }
        self::assertTrue($rejected);
      }
      unlink($file);
      $this->expectException(\RuntimeException::class);
      KeyMaterialVault::open($key, 'credential', $sealed);
    } finally {
      if (is_file($file)) {
        unlink($file);
      }
      if ($previous === null) {
        unset($_ENV['PAYCAL_KEY_MATERIAL_KEYRING_FILE']);
      } else {
        $_ENV['PAYCAL_KEY_MATERIAL_KEYRING_FILE'] = $previous;
      }
    }
  }
}
