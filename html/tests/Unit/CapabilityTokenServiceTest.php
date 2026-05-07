<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Infrastructure\Auth\CapabilityTokenService;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PHPUnit\Framework\TestCase;

final class CapabilityTokenServiceTest extends TestCase
{
  private string $userUuid;
  private string $sessionHash;

  protected function setUp(): void
  {
    parent::setUp();

    $this->userUuid = 'cap-user-' . bin2hex(random_bytes(4));
    $this->sessionHash = hash('sha256', bin2hex(random_bytes(16)));
  }

  protected function tearDown(): void
  {
    Database::del('capability:token:' . $this->userUuid . ':*');
    Database::del('capability:replay:*');

    parent::tearDown();
  }

  public function testIssueAndConsumeOneShotCapabilityToken(): void
  {
    $issued = CapabilityTokenService::issue('admin.redis.freeze', $this->userUuid, $this->sessionHash);

    $this->assertNotSame('', $issued['token']);
    $this->assertSame('admin.redis.freeze', $issued['action']);
    $this->assertGreaterThan(0, $issued['expires_in']);

    $firstConsume = CapabilityTokenService::consume(
      $issued['token'],
      'admin.redis.freeze',
      $this->userUuid,
      $this->sessionHash
    );

    $this->assertTrue($firstConsume['ok']);
    $this->assertSame('OK', $firstConsume['code']);

    $secondConsume = CapabilityTokenService::consume(
      $issued['token'],
      'admin.redis.freeze',
      $this->userUuid,
      $this->sessionHash
    );

    $this->assertFalse($secondConsume['ok']);
    $this->assertSame('CAPABILITY_REPLAY', $secondConsume['code']);
  }

  public function testRejectsActionMismatch(): void
  {
    $issued = CapabilityTokenService::issue('admin.redis.breaker.open', $this->userUuid, $this->sessionHash);

    $decision = CapabilityTokenService::consume(
      $issued['token'],
      'admin.redis.freeze',
      $this->userUuid,
      $this->sessionHash
    );

    $this->assertFalse($decision['ok']);
    $this->assertSame('CAPABILITY_ACTION_MISMATCH', $decision['code']);
  }

  public function testCapabilityKeyHelpers(): void
  {
    $token = 'abc123';
    $this->assertSame(
      'capability:token:' . $this->userUuid . ':' . $token,
      Keys::capabilityToken($this->userUuid, $token)
    );
    $this->assertSame('capability:replay:' . $token, Keys::capabilityReplay($token));
  }
}
