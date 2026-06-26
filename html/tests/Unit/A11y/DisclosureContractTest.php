<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class DisclosureContractTest extends TestCase
{
  private function projectRoot(): string
  {
    return dirname(__DIR__, 4);
  }

  private function htmlRoot(): string
  {
    return $this->projectRoot() . '/html';
  }

  #[Test]
  public function calendarRangePickersExposeDialogDisclosureContracts(): void
  {
    $calendarPage = (string) file_get_contents($this->htmlRoot() . '/index.php');

    $pickerContracts = [
      'cal_picker_button' => 'modal_cal_picker',
      'cal_week_picker_button' => 'modal_cal_week_picker',
      'cal_payperiod_picker_button' => 'modal_cal_payperiod_picker',
    ];

    foreach ($pickerContracts as $triggerId => $dialogId) {
      $this->assertStringContainsString('id="' . $triggerId . '"', $calendarPage);
      $this->assertStringContainsString('aria-haspopup="dialog"', $calendarPage);
      $this->assertStringContainsString('aria-expanded="false"', $calendarPage);
      $this->assertStringContainsString('aria-controls="' . $dialogId . '"', $calendarPage);
    }

    $dataGrid = (string) file_get_contents($this->projectRoot() . '/html/src/Domain/DataGrid.php');
    $this->assertStringContainsString('aria-controls="modal_cal_picker"', $dataGrid);
    $this->assertStringContainsString("aria-controls=\"<?php echo \$this->escape(\$compactNavPickerDialogId); ?>\"", $dataGrid);
    $this->assertStringContainsString("'open-week-picker' => 'modal_cal_week_picker'", $dataGrid);
    $this->assertStringContainsString("'open-pay-period-picker' => 'modal_cal_payperiod_picker'", $dataGrid);
  }

  #[Test]
  public function calendarJsSyncsRangePickerExpandedState(): void
  {
    $calendarJs = (string) file_get_contents($this->htmlRoot() . '/js/calendar/calendar.js');
    $legacyCalendarJs = (string) file_get_contents($this->htmlRoot() . '/js/calendar/index.php');

    $this->assertStringContainsString('CALENDAR_RANGE_PICKER_DISCLOSURES', $calendarJs);
    $this->assertStringContainsString('function syncCalendarRangePickerExpanded(triggerId, expanded)', $calendarJs);
    $this->assertStringContainsString("trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');", $calendarJs);
    $this->assertStringContainsString('bindCalendarRangePickerDisclosures();', $calendarJs);
    $this->assertStringContainsString("syncCalendarRangePickerExpanded('cal_picker_button', true);", $calendarJs);
    $this->assertStringNotContainsString("button.setAttribute('aria-label', String(!expanded));", $legacyCalendarJs);
    $this->assertStringContainsString("pickerBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');", $legacyCalendarJs);
  }

  #[Test]
  public function sidebarToggleExposesNavigationDisclosureContracts(): void
  {
    $header = (string) file_get_contents($this->htmlRoot() . '/header.php');
    $navigationJs = (string) file_get_contents($this->htmlRoot() . '/js/navigation-toggle.js');

    $this->assertStringContainsString('id="sidebar_toggle_control"', $header);
    $this->assertStringContainsString('aria-controls="primary_navigation"', $header);
    $this->assertStringContainsString('aria-expanded="true"', $header);
    $this->assertStringContainsString("toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');", $navigationJs);
    $this->assertStringContainsString("primaryNav.setAttribute('aria-hidden', 'true');", $navigationJs);
  }

  #[Test]
  public function passkeyCardMenuExposesMenuDisclosureContracts(): void
  {
    $settingsJs = (string) file_get_contents($this->htmlRoot() . '/js/settings/index.php');

    $this->assertStringContainsString("trigger.setAttribute('aria-haspopup', 'menu');", $settingsJs);
    $this->assertStringContainsString("trigger.setAttribute('aria-expanded', 'false');", $settingsJs);
    $this->assertStringContainsString("trigger.setAttribute('aria-controls', menuId);", $settingsJs);
    $this->assertStringContainsString("trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');", $settingsJs);
    $this->assertStringContainsString("trigger.setAttribute('aria-expanded', 'false');", $settingsJs);
    $this->assertStringContainsString('SETTINGS_T.SETTINGS_PASSKEY_MENU_ARIA', $settingsJs);
  }

  #[Test]
  public function businessReportsDrawersExposeDisclosureContracts(): void
  {
    $reportsPage = (string) file_get_contents($this->htmlRoot() . '/business/reports/index.php');
    $reportsJs = (string) file_get_contents($this->htmlRoot() . '/js/business/subpages/reports.js.php');

    $this->assertStringContainsString('data-report-customize-open aria-expanded="false" aria-controls="business_reports_customize_drawer"', $reportsPage);
    $this->assertStringContainsString('id="business_reports_customize_drawer"', $reportsPage);
    $this->assertStringContainsString('data-report-export-open aria-expanded="false" aria-controls="business_reports_export_drawer"', $reportsPage);
    $this->assertStringContainsString('id="business_reports_export_drawer"', $reportsPage);
    $this->assertStringContainsString("customizeOpen.setAttribute('aria-expanded', expanded ? 'true' : 'false');", $reportsJs);
    $this->assertStringContainsString("exportOpen.setAttribute('aria-expanded', expanded ? 'true' : 'false');", $reportsJs);
  }

  #[Test]
  public function businessReportsTabsExposeKeyboardNavigationContracts(): void
  {
    $reportsPage = (string) file_get_contents($this->htmlRoot() . '/business/reports/index.php');
    $reportsJs = (string) file_get_contents($this->htmlRoot() . '/js/business/subpages/reports.js.php');

    $this->assertStringContainsString('tabindex="0"', $reportsPage);
    $this->assertStringContainsString('tabindex="-1"', $reportsPage);
    $this->assertStringContainsString("button.setAttribute('tabindex', isActive ? '0' : '-1');", $reportsJs);
    $this->assertStringContainsString('panel.setAttribute(\'aria-labelledby\', `${activeTabButton.id} business_reports_panel_heading`);', $reportsJs);
    $this->assertStringContainsString("event.key === 'ArrowRight'", $reportsJs);
  }

  #[Test]
  public function businessDialogTriggersExposeExpandedStateContracts(): void
  {
    $governancePanel = (string) file_get_contents($this->htmlRoot() . '/business/_partials/governance_panel.php');
    $workspaceJs = (string) file_get_contents($this->htmlRoot() . '/js/business/workspace.js.php');
    $membersPage = (string) file_get_contents($this->htmlRoot() . '/business/members/index.php');
    $membersJs = (string) file_get_contents($this->htmlRoot() . '/js/business/subpages/members.js.php');

    $this->assertStringContainsString('id="businesses_definitions_help_button"', $governancePanel);
    $this->assertStringContainsString('aria-haspopup="dialog"', $governancePanel);
    $this->assertStringContainsString('aria-controls="businesses_definitions_dialog"', $governancePanel);
    $this->assertStringContainsString('aria-expanded="false"', $governancePanel);
    $this->assertStringContainsString("elements.definitionsHelpButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');", $workspaceJs);

    $this->assertStringContainsString('id="business_members_bulk_group_toggle"', $membersPage);
    $this->assertStringContainsString('aria-haspopup="menu"', $membersPage);
    $this->assertStringContainsString('aria-controls="business_members_bulk_group_menu"', $membersPage);
    $this->assertStringContainsString('id="business_members_report_toggle"', $membersPage);
    $this->assertStringContainsString('aria-controls="business_members_report_panel"', $membersPage);
    $this->assertStringContainsString("elements.membersReportToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');", $membersJs);
    $this->assertStringContainsString("metricChip.setAttribute('aria-expanded', details.open ? 'true' : 'false');", $membersJs);
  }
}
