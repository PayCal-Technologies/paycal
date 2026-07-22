<?php declare(strict_types=1);

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\PayPeriodGenerator;
use PayCal\Domain\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('unit')]
#[Group('pay-periods')]
#[Group('redis-write')]
final class PayPeriodGeneratorTest extends TestCase
{
  private string $userUUID;

  protected function setUp(): void
  {
    $this->userUUID = 'test-pp-' . bin2hex(random_bytes(4));
  }

  protected function tearDown(): void
  {
    PayPeriodGenerator::clearSchedule($this->userUUID);
  }

  #[Test]
  public function resolveForDateOrComputeFallsBackWhenStoredScheduleMissesDate(): void
  {
    $user = $this->makeBiweeklyUser();
    $this->seedStaleSchedule($user->user_uuid, '2024-01-01');

    $target = new \DateTimeImmutable('2026-05-23', new \DateTimeZone('America/Edmonton'));
    $this->assertNull(PayPeriodGenerator::resolveForDate($user, $target));

    $resolved = PayPeriodGenerator::resolveForDateOrCompute($user, $target);
    $info = $resolved->getPayPeriodForDate($target);

    $this->assertTrue($info['start'] <= $target && $target <= $info['end']);
    $this->assertSame(13, $info['start']->diff($info['end'])->days);
    $this->assertSame('Monday', $resolved->start()->format('l'));
    $this->assertNotSame('', (string) ($info['label_short'] ?? $info['label_full'] ?? ''));
  }

  #[Test]
  public function resolveForDateOrComputeUsesStoredScheduleWhenAvailable(): void
  {
    $user = $this->makeBiweeklyUser();
    $storedStart = '2026-05-18';
    $this->seedScheduleContaining($user->user_uuid, $storedStart, 14);

    $target = new \DateTimeImmutable('2026-05-23', new \DateTimeZone('America/Edmonton'));
    $resolved = PayPeriodGenerator::resolveForDateOrCompute($user, $target);

    $this->assertSame($storedStart, $resolved->start()->format('Y-m-d'));
    $this->assertNotNull(PayPeriodGenerator::resolveForDate($user, $target));
  }

  private function makeBiweeklyUser(): User
  {
    $user = new User();
    $user->user_uuid = $this->userUUID;
    $user->timezone = 'America/Edmonton';
    $user->pay_frequency = 'biweekly';
    $user->pay_anchor = 'Monday';
    $user->pay_period_start = '2024-01-01';
    $user->pay_epoch = '2024-01-01';

    return $user;
  }

  private function seedStaleSchedule(string $userUUID, string $startYmd): void
  {
    $this->seedScheduleContaining($userUUID, $startYmd, 14);
  }

  private function seedScheduleContaining(string $userUUID, string $startYmd, int $lengthDays): void
  {
    $baseKey = Keys::PAY_PERIOD . ':schedule:' . $userUUID;
    $indexKey = $baseKey . ':index';
    $periodKey = $baseKey . ':' . $startYmd;
    $zone = new \DateTimeZone('America/Edmonton');
    $start = new \DateTimeImmutable($startYmd, $zone);
    $endExclusive = $start->modify('+' . $lengthDays . ' days');

    Database::hset($periodKey, [
      'start' => $start->format('Y-m-d'),
      'end_exclusive' => $endExclusive->format('Y-m-d'),
      'end_inclusive' => $endExclusive->modify('-1 day')->format('Y-m-d'),
      'frequency' => 'biweekly',
      'anchor' => 'Monday',
      'epoch' => $start->format('Y-m-d'),
      'timezone' => 'America/Edmonton',
    ]);

    Database::getInstance()->client->zAdd($indexKey, (float) $start->getTimestamp(), $start->format('Y-m-d'));
  }
}
