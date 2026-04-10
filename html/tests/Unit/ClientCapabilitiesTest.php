<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\ClientCapabilities;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 *
 */
#[Group('unit')]
final class ClientCapabilitiesTest extends TestCase
{
  public function testMinimalReportIsValid(): void
  {
    $report = ClientCapabilities::getMinimalReport();
    $this->assertTrue(ClientCapabilities::isValidReport($report));
  }

  public function testInvalidReportIsInvalid(): void
  {
    $this->assertFalse(ClientCapabilities::isValidReport(['foo' => 'bar']));
  }
}
