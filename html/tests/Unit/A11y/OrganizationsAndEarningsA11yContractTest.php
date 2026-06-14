<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class OrganizationsAndEarningsA11yContractTest extends TestCase
{
  #[Test]
  public function organizationsRequestControlsExposeAccessibleNamesAndToggleState(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $page = (string) file_get_contents($projectRoot . '/html/organizations/index.php');
    $organizationsJs = (string) file_get_contents($projectRoot . '/html/js/organizations/index.php');

    $this->assertStringContainsString('id="organizations_request_email"', $page);
    $this->assertStringContainsString('aria-label="<?php echo organizations_index_i18n(\'ORGANIZATIONS_REQUEST_EMAIL_PLACEHOLDER\'); ?>"', $page);
    $this->assertStringContainsString('id="organizations_request_access_readonly"', $page);
    $this->assertStringContainsString('aria-pressed="true"', $page);
    $this->assertStringContainsString('id="organizations_request_access_full"', $page);
    $this->assertStringContainsString('aria-pressed="false"', $page);

    $this->assertStringContainsString('p.setAttribute(\'aria-pressed\'', $organizationsJs);
    $this->assertStringContainsString('applyContactInputAriaLabels', $organizationsJs);
  }

  #[Group('private-moat')]
  #[Test]
  public function earningsOrgViewUsesNavigationSemanticsAndNamedSelector(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $page = (string) file_get_contents($projectRoot . '/html/earnings/index.php');

    $this->assertStringContainsString('<nav class="earnings_view_tabs" aria-label="<?php echo htmlspecialchars($i18n[\'EARNINGS_VIEW_LABEL\']', $page);
    $this->assertStringContainsString('class="earnings_view_tab<?php echo $isTeam ? \' active\' : \'\'; ?>"', $page);
    $this->assertStringNotContainsString('role="tab"', $page);
    $this->assertStringNotContainsString('aria-selected="<?php echo $isTeam ? \'true\' : \'false\'; ?>"', $page);
    $this->assertStringContainsString('id="earnings_team_org" name="org" class="earnings_tab_org_select" aria-label="<?php echo htmlspecialchars($i18n[\'EARNINGS_SELECT_ORGANIZATION\']', $page);
  }
}
