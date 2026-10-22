<?php declare(strict_types=1);

use PayCal\Domain\FederatedEmailIdentity;
use PHPUnit\Framework\TestCase;

final class FederatedEmailIdentityTest extends TestCase
{
  public function testDistinctCustomDomainMailboxesCannotLink(): void
  {
    self::assertFalse(FederatedEmailIdentity::matches('person@example.test', 'person+department@example.test'));
    self::assertFalse(FederatedEmailIdentity::matches('person@example.test', 'per.son@example.test'));
    self::assertFalse(FederatedEmailIdentity::matches('person@example.test', 'person@example.test<script>'));
    self::assertFalse(FederatedEmailIdentity::matches('', ''));
  }

  public function testVerifiedEquivalentInboxFormsRemainCompatible(): void
  {
    self::assertTrue(FederatedEmailIdentity::matches(' Person@Example.test ', 'person@example.test'));
    self::assertTrue(FederatedEmailIdentity::matches('p.er.son@gmail.com', 'person+paycal@googlemail.com'));
    self::assertTrue(FederatedEmailIdentity::matches('person+department@example.test', 'person+department@example.test'));
  }
}
