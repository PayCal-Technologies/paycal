<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
#[Group('security')]
final class AuthTracePasskeyContractTest extends TestCase
{
  public function testPasskeySignupFlowUsesAuthTrace(): void
  {
    $source = $this->readProjectFile('src/Controllers/PasskeyController.php');

    foreach ([
      'AuthTrace::signupStart',
      'AuthTrace::signupRejected',
      'AuthTrace::signupCompleted',
      'AuthTrace::signupVerificationEmail',
      'AuthTrace::rateLimited',
    ] as $needle) {
      $this->assertStringContainsString($needle, $source);
    }

    $this->assertStringContainsString("'invalid_invite_code'", $source);
    $this->assertStringContainsString("'webauthn_process_create_failed'", $source);
    $this->assertStringContainsString("'challenge_expired_or_missing'", $source);
  }

  public function testAuthPageBootsLens(): void
  {
    $source = $this->readProjectFile('auth/index.php');
    $this->assertStringContainsString("Lens::boot('auth')", $source);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
