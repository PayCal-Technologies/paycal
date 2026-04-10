<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Encryption\EnvelopeFormat;

/**
 * Contract: encryption envelope shape and round-trip stability.
 */
#[Group('contract')]
#[Group('crypto')]
final class EnvelopeFormatContractTest extends TestCase
{
  public function testEnvelopeRoundTripContract(): void
  {
    $envelope = EnvelopeFormat::create(
      1,
      base64_encode(random_bytes(12)),
      base64_encode('contract-ciphertext'),
      'aad-contract'
    );

    $json = EnvelopeFormat::toJson($envelope);
    $decoded = EnvelopeFormat::fromJson($json);

    $this->assertSame(1, $decoded['version']);
    $this->assertArrayHasKey('nonce', $decoded);
    $this->assertArrayHasKey('ciphertext', $decoded);
    $this->assertArrayHasKey('aad', $decoded);
    $this->assertSame($envelope, $decoded);
  }
}
