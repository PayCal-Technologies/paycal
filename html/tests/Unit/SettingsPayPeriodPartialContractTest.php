<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SettingsPayPeriodPartialContractTest extends TestCase
{
  #[Test]
  public function calendarPayPeriodPartialExposesFormAndPreviewHooks(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $partial = (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_calendar_pay_period.php');
    $calendarPage = (string) file_get_contents($projectRoot . '/html/settings/calendar/index.php');
    $workDefaults = (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_calendar_work_defaults.php');

    $requiredIds = [
      'settings_pay_period_form',
      'pay_period_current_preview',
      'pay_period_current_calendar',
      'pay_period_generate',
    ];

    foreach ($requiredIds as $id) {
      $this->assertStringContainsString('id="' . $id . '"', $partial, $id . ' must exist for settings pay-period JS');
    }

    $this->assertStringContainsString('api/v1/settings/pay_period/update/', $partial);
    $this->assertStringContainsString('panel_calendar_work_defaults.php', $calendarPage);

    foreach (['default_site_id', 'default_hours', 'default_travel_hours'] as $id) {
      $this->assertStringContainsString('id="' . $id . '"', $workDefaults, $id . ' must exist on calendar work defaults partial');
    }
  }

  #[Test]
  public function payPeriodPreviewModalExposesEditorControls(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $modals = (string) file_get_contents($projectRoot . '/html/settings/_partials/modals.php');

    $requiredIds = [
      'modal_pay_period_preview',
      'pay_period_start',
      'pay_frequency',
      'pay_anchor',
      'editing_grace_days',
      'pay_period_preview_calendar',
      'pay_period_preview_summary',
      'pay_period_preview_apply',
      'pay_period_preview_cancel',
    ];

    foreach ($requiredIds as $id) {
      $this->assertStringContainsString($id, $modals, $id . ' must exist for pay-period preview modal JS');
    }
  }

  #[Test]
  public function settingsJavascriptUsesSharedPayPeriodPreviewCore(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $settingsJs = (string) file_get_contents($projectRoot . '/html/js/settings/index.php');
    $coreJs = (string) file_get_contents($projectRoot . '/html/js/core/pay-period-preview.js');

    $this->assertStringContainsString('from "../core/pay-period-preview.js"', $settingsJs);
    $this->assertStringContainsString('buildPayPeriodPreviewState({', $settingsJs);
    $this->assertStringContainsString('rollBiweeklyToToday: true', $settingsJs);
    $this->assertStringNotContainsString('const currentPeriod =', $settingsJs);
    $this->assertStringNotContainsString('const buildRibbonCalendar =', $settingsJs);

    $this->assertStringContainsString('export function buildPayPeriodCurrentRange', $coreJs);
    $this->assertStringContainsString('export function buildPayPeriodPreviewState', $coreJs);
    $this->assertStringContainsString('export function buildPayPeriodRibbonCalendar', $coreJs);
    $this->assertStringContainsString('export function resolvePayPeriodPreviewSelection', $coreJs);
  }
}
