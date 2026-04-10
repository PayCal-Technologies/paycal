<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Domain\EmailGarum;
use PHPUnit\Framework\TestCase;

/**
 * EmailGarumTest
 *
 * Purpose: lock down legacy constant fallback behavior for mail composition.
 * Why this exists: production/bootstrap paths can differ from test bootstrap,
 * so the mailer must not fatal when PC_NAME is not defined globally.
 */
final class EmailGarumTest extends TestCase
{
  public function testAppNameFallsBackToDefaultWhenLegacyConstantMissing(): void
  {
    $method = new \ReflectionMethod(EmailGarum::class, 'appName');

    $this->assertSame('PayCal', $method->invoke(null));
  }
}