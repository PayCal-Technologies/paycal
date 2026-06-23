<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Domain\Business\BusinessPermissionPresenter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class BusinessPermissionPresenterTest extends TestCase
{
  #[Test]
  public function scopeListLabelUsesUserFacingAccessCopy(): void
  {
    $label = BusinessPermissionPresenter::scopeListLabel('sites.read,work.read');

    $this->assertStringContainsString('View assigned business sites', $label);
    $this->assertStringContainsString('Use approved work entries for reports', $label);
    $this->assertStringNotContainsString('sites.read', $label);
    $this->assertStringNotContainsString('work.read', $label);
  }

  #[Test]
  public function ownerScopeUsesPlainFullAccessCopy(): void
  {
    $this->assertSame('Full business access', BusinessPermissionPresenter::scopeListLabel('all'));
  }
}
