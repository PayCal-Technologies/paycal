<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Observability\DiagnosticRedactor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('security')]
final class DiagnosticRedactorTest extends TestCase
{
  #[Test]
  public function redactDropsBlockedIdentityAndPayrollKeys(): void
  {
    $redacted = DiagnosticRedactor::redact([
      'user_uuid' => 'U-SECRET',
      'email' => 'person@example.com',
      'wage' => '42.50',
      'tax_amount' => '9.99',
      'scope' => 'user:calendar',
      'remaining' => 0,
    ]);

    $this->assertNull($redacted);
  }

  #[Test]
  public function redactAllowsSafeOperationalKeys(): void
  {
    $redacted = DiagnosticRedactor::redact([
      'scope' => 'user:calendar',
      'remaining' => 0,
      'email_token' => 'abc123hash456',
    ]);

    $this->assertIsArray($redacted);
    $this->assertSame('user:calendar', $redacted['scope']);
    $this->assertSame('abc123hash456', $redacted['email_token']);
  }

  #[Test]
  public function redactFailsClosedOnTokenLikeScalars(): void
  {
    $jwt = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.signature';
    $redacted = DiagnosticRedactor::redact([
      'note' => $jwt,
    ]);

    $this->assertNull($redacted);
  }
}
