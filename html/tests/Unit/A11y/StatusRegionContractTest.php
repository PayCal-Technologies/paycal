<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class StatusRegionContractTest extends TestCase
{
  #[Test]
  public function settingsAndBusinessesExposeCoreStatusRegions(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $settings = (string) file_get_contents($projectRoot . '/html/settings/index.php');
    $dashboard = (string) file_get_contents($projectRoot . '/html/business/index.php');
    $editorDialog = (string) file_get_contents($projectRoot . '/html/business/_archive/partials/editor_dialog.php');
    $discoveryPanel = (string) file_get_contents($projectRoot . '/html/business/_partials/editor_sites_discovery_panel.php');
    $auditPanel = (string) file_get_contents($projectRoot . '/html/business/_partials/editor_audit_panels.php');

    $settingsIds = [
      'passkey_credentials_sr_status',
      'change_email_status',
      'recovery_email_send_status',
      'delete_account_status',
    ];

    $dashboardIds = [];

    $membersPage = (string) file_get_contents($projectRoot . '/html/business/members/index.php');

    $membersPageIds = [
      'businesses_members_grid_sr_status',
    ];

    $editorDialogIds = [
      'businesses_invites_sr_status',
      'businesses_access_requests_sr_status',
    ];

    foreach ($settingsIds as $id) {
      $this->assertStringContainsString($id, $settings);
    }

    foreach ($dashboardIds as $id) {
      $this->assertStringContainsString($id, $dashboard);
    }

    foreach ($membersPageIds as $id) {
      $this->assertStringContainsString($id, $membersPage);
    }

    foreach ($editorDialogIds as $id) {
      $this->assertStringContainsString($id, $editorDialog);
    }

    $this->assertStringContainsString('businesses_discovery_sr_status', $discoveryPanel);
    $this->assertStringContainsString('businesses_audit_sr_status', $auditPanel);
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
