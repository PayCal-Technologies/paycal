<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Encryption\EnvelopeFormat;
use PayCal\Domain\InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 *
 */
#[Group('unit')]
final class EnvelopeFormatTest extends TestCase
{
  public function testCreateAndValidateEnvelope(): void
  {
    $envelope = EnvelopeFormat::create(1, base64_encode(random_bytes(12)), base64_encode('ciphertext'), 'aad');
    $this->assertTrue(EnvelopeFormat::isValid($envelope));
    EnvelopeFormat::validateOrThrow($envelope);
  }

  public function testEnvelopeToJsonAndFromJson(): void
  {
    $envelope = EnvelopeFormat::create(1, base64_encode(random_bytes(12)), base64_encode('ciphertext'), 'aad');
    $json = EnvelopeFormat::toJson($envelope);
    $parsed = EnvelopeFormat::fromJson($json);
    $this->assertEquals($envelope, $parsed);
  }

  public function testInvalidEnvelopeThrows(): void
  {
    $this->expectException(InvalidArgumentException::class);
    EnvelopeFormat::validateOrThrow(['foo' => 'bar']);
  }
}
