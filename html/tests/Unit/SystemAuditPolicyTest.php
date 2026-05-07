<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Domain\AuditAccessDeniedException;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\SystemAuditPolicy;
use PayCal\Domain\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * SystemAuditPolicyTest
 *
 * Purpose: Verify that the SOC 2 RBAC access-control policy for the system audit log
 * is enforced correctly — read requires ADMIN+, export requires SUPERADMIN.
 *
 * Why this file exists:
 *   SystemAuditPolicy is a machine-readable control boundary that auditors can inspect.
 *   These tests prove the boundary is active, not just documented.
 */
#[Group('unit')]
#[Group('security')]
final class SystemAuditPolicyTest extends TestCase
{
  // ──────────────────────────────────────────────────────────────────────────
  // assertCanRead
  // ──────────────────────────────────────────────────────────────────────────

  public function testAdminCanRead(): void
  {
    $user = $this->userWithLevel(AuthLevel::ADMIN);
    // Should not throw
    SystemAuditPolicy::assertCanRead($user);
    $this->assertTrue(true);
  }

  public function testSuperadminCanRead(): void
  {
    $user = $this->userWithLevel(AuthLevel::SUPERADMIN);
    SystemAuditPolicy::assertCanRead($user);
    $this->assertTrue(true);
  }

  public function testManagerCannotRead(): void
  {
    $this->expectException(AuditAccessDeniedException::class);
    SystemAuditPolicy::assertCanRead($this->userWithLevel(AuthLevel::MANAGER));
  }

  public function testUserCannotRead(): void
  {
    $this->expectException(AuditAccessDeniedException::class);
    SystemAuditPolicy::assertCanRead($this->userWithLevel(AuthLevel::USER));
  }

  public function testVerifiedCannotRead(): void
  {
    $this->expectException(AuditAccessDeniedException::class);
    SystemAuditPolicy::assertCanRead($this->userWithLevel(AuthLevel::VERIFIED));
  }

  // ──────────────────────────────────────────────────────────────────────────
  // assertCanExport
  // ──────────────────────────────────────────────────────────────────────────

  public function testSuperadminCanExport(): void
  {
    $user = $this->userWithLevel(AuthLevel::SUPERADMIN);
    SystemAuditPolicy::assertCanExport($user);
    $this->assertTrue(true);
  }

  public function testAdminCannotExport(): void
  {
    $this->expectException(AuditAccessDeniedException::class);
    SystemAuditPolicy::assertCanExport($this->userWithLevel(AuthLevel::ADMIN));
  }

  public function testManagerCannotExport(): void
  {
    $this->expectException(AuditAccessDeniedException::class);
    SystemAuditPolicy::assertCanExport($this->userWithLevel(AuthLevel::MANAGER));
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Policy constants
  // ──────────────────────────────────────────────────────────────────────────

  public function testRequiredReadLevelIsAdmin(): void
  {
    $this->assertSame(AuthLevel::ADMIN, SystemAuditPolicy::REQUIRED_READ_LEVEL);
  }

  public function testRequiredExportLevelIsSuperadmin(): void
  {
    $this->assertSame(AuthLevel::SUPERADMIN, SystemAuditPolicy::REQUIRED_EXPORT_LEVEL);
  }

  public function testExceptionMessageContainsRequiredLevel(): void
  {
    try {
      SystemAuditPolicy::assertCanRead($this->userWithLevel(AuthLevel::USER));
      $this->fail('Expected AuditAccessDeniedException');
    } catch (AuditAccessDeniedException $e) {
      $this->assertStringContainsString(AuthLevel::ADMIN->value, $e->getMessage());
    }
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Helper
  // ──────────────────────────────────────────────────────────────────────────

  private function userWithLevel(AuthLevel $level): User
  {
    $user = new User();
    $user->auth_level = $level;
    return $user;
  }
}
