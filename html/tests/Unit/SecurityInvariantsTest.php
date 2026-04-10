<?php declare(strict_types=1);

namespace PayCal\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Security invariant guard tests.
 *
 * These tests lock down code-level security guarantees that must not regress.
 * They intentionally assert source-level enforcement points for high-risk paths.
 *
 * @internal
 */
#[Group('unit')]
final class SecurityInvariantsTest extends TestCase
{
  private function src(string $relativePath): string
  {
    $path = __DIR__ . '/../../src/' . $relativePath;
    $content = @file_get_contents($path);

    $this->assertNotFalse($content, "Failed to read source file: {$relativePath}");

    return (string) $content;
  }

  #[Test]
  public function workEntryMutationsEnforceLockBeforePersistence(): void
  {
    $src = $this->src('Domain/WorkEntry.php');

    $lockCheckPos = strpos($src, 'WorkEntryLockService::isLocked($workDate, $userUUID)');
    $persistPos = strpos($src, 'Database::hset($workEntryKey, $fieldsToStore)');

    $this->assertIsInt($lockCheckPos);
    $this->assertIsInt($persistPos);
    $this->assertLessThan($persistPos, $lockCheckPos, 'Lock check must happen before write.');
    $this->assertStringContainsString('SecurityLog::logEntryLockedAttempt', $src);
  }

  #[Test]
  public function siteDeleteBlocksWhenAnyEntryIsLocked(): void
  {
    $src = $this->src('Domain/SitesService.php');

    $this->assertStringContainsString('WorkEntryLockService::isLocked($date, $userUUID)', $src);
    $this->assertStringContainsString('if ($lockedCount > 0)', $src);
    $this->assertStringContainsString("'success' => false", $src);
  }

  #[Test]
  public function requestGuardEnforcesUserAndIpRateLimits(): void
  {
    $src = $this->src('Domain/RequestGuard.php');

    $this->assertStringContainsString('RateLimiter::checkCalendarLimit(User::currentUUID())', $src);
    $this->assertStringContainsString('RateLimiter::checkIPCalendarLimit(self::clientIP())', $src);
    $this->assertStringContainsString('HttpStatus::HTTP_TOO_MANY_REQUESTS', $src);
    $this->assertStringContainsString('SecurityLog::logRateLimitTriggered', $src);
  }

  #[Test]
  public function telemetryEndpointRequiresAuthenticationAndHasDedicatedLimit(): void
  {
    $src = $this->src('Controllers/TelemetryController.php');

    $this->assertStringContainsString('Authentication::validateAndTouchSession()', $src);
    $this->assertStringContainsString('RateLimiter::checkTelemetryLimit($userUUID)', $src);
    $this->assertStringContainsString('HttpStatus::HTTP_TOO_MANY_REQUESTS', $src);
  }

  #[Test]
  public function lockBoundaryCacheIsDayScopedAndMidnightBounded(): void
  {
    $src = $this->src('Domain/WorkEntryLockService.php');

    $this->assertStringContainsString('Keys::LOCK_BOUNDARY . ":{$userUUID}:{$today}"', $src);
    $this->assertStringContainsString('self::utcToday()', $src);
    $this->assertStringContainsString('self::secondsUntilMidnight()', $src);
    $this->assertStringContainsString('Database::scanKeys($pattern)', $src);
  }

  #[Test]
  public function encryptionRequiredModeRejectsPlaintextReadsAndFallbacks(): void
  {
    $src = $this->src('Domain/WorkEntry.php');

    $this->assertStringContainsString('required_write_rejected', $src);
    $this->assertStringContainsString('required_plaintext_rejected', $src);
    $this->assertStringNotContainsString('required_fallback_blocked', $src);
  }

  #[Test]
  public function canonicalMutationGatewayExists(): void
  {
    $repo = $this->src('Domain/WorkEntryRepository.php');
    $calendar = $this->src('Controllers/CalendarController.php');
    $sites = $this->src('Domain/SitesService.php');
    $admin = $this->src('Controllers/AdminController.php');

    $this->assertStringContainsString('final class WorkEntryRepository', $repo);
    $this->assertStringContainsString('return WorkEntry::updateWorkEntry($workDetails, $userUUID);', $repo);
    $this->assertStringContainsString('public static function archiveByKey', $repo);
    $this->assertStringContainsString('public static function restoreByKey', $repo);
    $this->assertStringContainsString('public static function deleteArchivedByKey', $repo);
    $this->assertStringContainsString('WorkEntryRepository::save(', $calendar);
    $this->assertStringContainsString('WorkEntryRepository::archiveByKey(', $sites);
    $this->assertStringContainsString('WorkEntryRepository::restoreByKey(', $sites);
    $this->assertStringContainsString('WorkEntryRepository::deleteArchivedByKey(', $sites);
    $this->assertStringContainsString('WorkEntryRepository::save(', $admin);
    $this->assertStringNotContainsString('Database::hset($workKey, $workData)', $admin);
    $this->assertStringContainsString("'locked_entries' => ", $sites);
  }
}
