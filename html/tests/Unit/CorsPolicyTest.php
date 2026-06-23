<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\CORS;

#[Group('unit')]
final class CorsPolicyTest extends TestCase
{
  public function testApprovedPayCalOriginsAreAllowedVerbatim(): void
  {
    $this->assertSame('https://paycal.app', CORS::allowedOrigin('https://paycal.app'));
    $this->assertSame('https://www.paycal.app', CORS::allowedOrigin('https://www.paycal.app'));
  }

  public function testUnapprovedAndMalformedOriginsAreRejected(): void
  {
    $this->assertNull(CORS::allowedOrigin('https://evil.example'));
    $this->assertNull(CORS::allowedOrigin('https://paycal.app.evil.example'));
    $this->assertNull(CORS::allowedOrigin('http://paycal.app'));
    $this->assertNull(CORS::allowedOrigin(''));
  }
}
