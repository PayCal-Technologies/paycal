<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class StatusRegionContractTest extends TestCase
{
  #[Test]
  public function settingsAndOrganizationsExposeCoreStatusRegions(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $settings = (string) file_get_contents($projectRoot . '/html/settings/index.php');
    $organizations = (string) file_get_contents($projectRoot . '/html/organizations/index.php');

    $settingsIds = [
      'passkey_credentials_sr_status',
      'change_email_status',
      'recovery_email_send_status',
      'delete_account_status',
    ];

    $organizationsIds = [
      'organizations_grid_sr_status',
      'organizations_members_grid_sr_status',
      'organizations_invites_sr_status',
      'organizations_discovery_sr_status',
      'organizations_audit_sr_status',
      'organizations_access_requests_sr_status',
    ];

    foreach ($settingsIds as $id) {
      $this->assertStringContainsString($id, $settings);
    }

    foreach ($organizationsIds as $id) {
      $this->assertStringContainsString($id, $organizations);
    }
  }

  #[Test]
  public function sitesPageExposesGridStatusRegionsAndDescriptions(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $sites = (string) file_get_contents($projectRoot . '/html/sites/index.php');

    $this->assertStringContainsString('sites_grid_active_sr_status', $sites);
    $this->assertStringContainsString('sites_grid_archived_sr_status', $sites);
    $this->assertStringContainsString('aria-describedby="sites_grid_active_sr_instructions sites_grid_active_sr_context sites_grid_active_sr_status"', $sites);
    $this->assertStringContainsString('aria-describedby="sites_grid_archived_sr_instructions sites_grid_archived_sr_context sites_grid_archived_sr_status"', $sites);
  }
}
