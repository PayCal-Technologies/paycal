<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\WorkEntryRepository;

/**
 * @internal
 */
#[Group('unit')]
final class WorkEntryRepositoryTest extends TestCase
{
  #[Test]
  public function keyHelpersReturnCanonicalShapes(): void
  {
    $this->assertSame(
      'work:Uabc:2026-03-01:S123456789',
      WorkEntryRepository::activeKey('Uabc', '2026-03-01', 'S123456789')
    );

    $this->assertSame(
      'work:archived:Uabc:2026-03-01:S123456789',
      WorkEntryRepository::archivedKey('Uabc', '2026-03-01', 'S123456789')
    );

    $this->assertSame(
      'work:Uabc:*:S123456789',
      WorkEntryRepository::activePatternForSite('Uabc', 'S123456789')
    );

    $this->assertSame(
      'work:archived:Uabc:*:S123456789',
      WorkEntryRepository::archivedPatternForSite('Uabc', 'S123456789')
    );

    $this->assertSame('work:Uabc:*:*', WorkEntryRepository::activePatternForUser('Uabc'));
  }

  #[Test]
  public function saveRejectsInvalidDatePayload(): void
  {
    $result = WorkEntryRepository::save([
      'date' => 'invalid-date',
      'site_id' => 'S123456789',
      'hours' => '8.00',
    ], 'Utest-user');

    $this->assertFalse($result);
  }
}
