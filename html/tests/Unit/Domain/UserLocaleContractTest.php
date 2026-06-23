<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserLocaleContractTest extends TestCase
{
  #[Test]
  public function configPhpBootstrapsSessionLocaleConstants(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $config = (string) file_get_contents($projectRoot . '/html/config.php');

    $this->assertStringContainsString("require_once __DIR__ . '/src/session.php'", $config);
  }

  #[Test]
  public function sessionPhpResolvesDateLocaleFromStoredLocaleAndLanguage(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $session = (string) file_get_contents($projectRoot . '/html/src/session.php');

    $this->assertStringContainsString('Language::resolveDateLocale(', $session);
    $this->assertStringContainsString('str_replace(\'-\', \'_\', USER_LOCALE)', $session);
  }

  #[Test]
  public function coreJsConfigUsesResolvedDateLocale(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $coreJs = (string) file_get_contents($projectRoot . '/html/js/core/index.php');

    $this->assertStringContainsString('Language::resolveDateLocale(', $coreJs);
  }

  #[Group('private-moat')]
  #[Test]
  public function workspaceTimestampFormatterUsesViewerLocale(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $timestampPopoversJs = (string) file_get_contents($projectRoot . '/html/js/business/core/timestamp-popovers.js.php');

    $this->assertStringContainsString('const resolveViewerLocale = () => {', $timestampPopoversJs);
    $this->assertStringContainsString('new Intl.DateTimeFormat(viewerLocale, { ...options, timeZone: normalizedZone })', $timestampPopoversJs);
    $this->assertStringNotContainsString("new Intl.DateTimeFormat('en-US', { ...options, timeZone: normalizedZone })", $timestampPopoversJs);
  }

  #[Group('private-moat')]
  #[Test]
  public function profileLanguageSaveDoesNotRequirePayPeriodValidationForDetailsSource(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $personalSettingsJs = (string) file_get_contents($projectRoot . '/html/js/business/core/personal-settings.js.php');

    $this->assertStringContainsString("const PAY_PERIOD_SAVE_SOURCES = new Set(['frequency', 'anchor', 'grace', 'calendar-day']);", $personalSettingsJs);
    $this->assertStringContainsString('if (PAY_PERIOD_SAVE_SOURCES.has(source) && !payPeriodValid)', $personalSettingsJs);
    $this->assertStringContainsString('window.location.reload();', $personalSettingsJs);
  }

  #[Group('private-moat')]
  #[Test]
  public function profileLanguageSaveStillWorksWhenPayPeriodManagedByBusiness(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $personalSettingsJs = (string) file_get_contents($projectRoot . '/html/js/business/core/personal-settings.js.php');

    $this->assertStringContainsString('const payPeriodManaged = state.profilePayPeriodManagedByBusiness;', $personalSettingsJs);
    $this->assertStringContainsString('if (payPeriodManaged && PAY_PERIOD_SAVE_SOURCES.has(source))', $personalSettingsJs);
    $this->assertStringContainsString('if (!payPeriodValid || payPeriodManaged)', $personalSettingsJs);

    $saveFn = 'const savePersonalBusinessSettings = async (source = \'auto\') => {';
    $saveStart = strpos($personalSettingsJs, $saveFn);
    $this->assertNotFalse($saveStart);
    $saveBody = substr($personalSettingsJs, (int) $saveStart, 1200);
    $this->assertStringNotContainsString(
      'if (state.profilePayPeriodManagedByBusiness) {',
      $saveBody,
    );
  }

  #[Test]
  public function calendarLockedMessagesUseI18nConstants(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $calendarIndex = (string) file_get_contents($projectRoot . '/html/js/calendar/index.php');
    $calendarJs = (string) file_get_contents($projectRoot . '/html/js/calendar/calendar.js');

    $this->assertStringContainsString('CALENDAR_LOCKED_CANNOT_EDIT', $calendarIndex);
    $this->assertStringContainsString('MSG_CAL_LOCKED_CANNOT_EDIT', $calendarIndex);
    $this->assertStringContainsString('formatLockedDateMessage(', $calendarJs);
    $this->assertStringContainsString("'CALENDAR_LOCKED_CANNOT_EDIT'", $calendarJs);
  }

  #[Test]
  public function membersGridRendererUsesIntlDateFormatterForAbsoluteJoinDates(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $renderer = (string) file_get_contents($projectRoot . '/html/src/Domain/BusinessMembersGridRenderer.php');

    $this->assertStringContainsString('formatLocalizedJoinDate', $renderer);
    $this->assertStringContainsString('new \\IntlDateFormatter', $renderer);
    $this->assertStringContainsString('$this->resolveUserLocale()', $renderer);
  }

  #[Test]
  public function calendarGridUsesLocalizedDateFormatting(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $dataGrid = (string) file_get_contents($projectRoot . '/html/src/Domain/DataGrid.php');
    $calendarJs = (string) file_get_contents($projectRoot . '/html/js/calendar/calendar.js');
    $calendarI18nJs = (string) file_get_contents($projectRoot . '/html/js/calendar/i18n.js');
    $settingsJs = (string) file_get_contents($projectRoot . '/html/js/settings/index.php');

    $this->assertStringContainsString('Strings::formatLocalizedMonthYear', $dataGrid);
    $this->assertStringContainsString('Strings::generateWeekDayLabels', $dataGrid);
    $this->assertStringNotContainsString("['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']", $dataGrid);
    $this->assertStringContainsString('function calendarUserLocale()', $calendarJs);
    $this->assertStringContainsString('function calendarConfig()', $calendarI18nJs);
    $this->assertStringContainsString('calendar-page-i18n', $calendarI18nJs);
    $this->assertStringNotContainsString("toLocaleDateString('en-US'", $calendarJs);
    $this->assertStringContainsString('payPeriodLocale', $settingsJs);
    $this->assertStringContainsString('PC?.config?.USER_LOCALE', $settingsJs);
  }
}
