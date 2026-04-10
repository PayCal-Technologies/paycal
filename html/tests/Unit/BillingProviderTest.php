<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Domain\BillingProvider;
use PayCal\Domain\Extensions\ExtensionRuntime;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class BillingProviderTest extends TestCase
{
  /** @var array<string, array<string, mixed>> */
  private array $originalActive;

  public static function setUpBeforeClass(): void
  {
    require_once __DIR__ . '/../../extensions/runtime.php';
  }

  protected function setUp(): void
  {
    parent::setUp();

    $ref = new \ReflectionProperty(ExtensionRuntime::class, 'active');
    /** @var array<string, array<string, mixed>> $active */
    $active = $ref->getValue();
    $this->originalActive = $active;
  }

  protected function tearDown(): void
  {
    $ref = new \ReflectionProperty(ExtensionRuntime::class, 'active');
    $ref->setValue(null, $this->originalActive);

    parent::tearDown();
  }

  #[Test]
  public function currentDefaultsToPublicToggle(): void
  {
    $ref = new \ReflectionProperty(ExtensionRuntime::class, 'active');
    $ref->setValue(null, []);

    $this->assertSame(BillingProvider::PUBLIC_TOGGLE, BillingProvider::current());
    $this->assertFalse(BillingProvider::isStripe());
  }

  #[Test]
  public function currentUsesStripeCapabilityWhenPresent(): void
  {
    $ref = new \ReflectionProperty(ExtensionRuntime::class, 'active');
    $ref->setValue(null, [
      'billing-provider' => [
        'id' => 'billing-provider',
        'version' => '1.0.0-private',
        'source' => 'override',
        'capabilities' => [
          'billing.provider' => 'stripe',
        ],
      ],
    ]);

    $this->assertSame(BillingProvider::STRIPE, BillingProvider::current());
    $this->assertTrue(BillingProvider::isStripe());
  }
}