<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\DataGrid;
use PayCal\Domain\Language;
use PayCal\Domain\Strings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('unit')]
final class CalendarLocaleTest extends TestCase
{
  #[Test]
  public function formatLocalizedMonthYearUsesGermanMonthNameWhenLocaleIsGerman(): void
  {
    if (!class_exists('\IntlDateFormatter')) {
      $this->markTestSkipped('IntlDateFormatter extension is required.');
    }

    $formatted = Strings::formatLocalizedMonthYear(2026, 6, 'de');

    $this->assertStringContainsString('2026', $formatted);
    $this->assertStringNotContainsString('June', $formatted);
    $this->assertMatchesRegularExpression('/juni|Juni/u', $formatted);
  }

  #[Test]
  public function formatLocalizedMonthYearUsesFrenchMonthNameWhenLocaleIsFrench(): void
  {
    if (!class_exists('\IntlDateFormatter')) {
      $this->markTestSkipped('IntlDateFormatter extension is required.');
    }

    $formatted = Strings::formatLocalizedMonthYear(2026, 6, 'fr');

    $this->assertStringContainsString('2026', $formatted);
    $this->assertStringNotContainsString('June', $formatted);
    $this->assertMatchesRegularExpression('/juin/u', $formatted);
  }

  #[Test]
  public function formatLocalizedMonthYearUsesSpanishMonthNameWhenLocaleIsSpanish(): void
  {
    if (!class_exists('\IntlDateFormatter')) {
      $this->markTestSkipped('IntlDateFormatter extension is required.');
    }

    $formatted = Strings::formatLocalizedMonthYear(2026, 6, 'es');

    $this->assertStringContainsString('2026', $formatted);
    $this->assertStringNotContainsString('June', $formatted);
    $this->assertMatchesRegularExpression('/junio/u', $formatted);
  }

  /**
   * @return array<string, array{0:string, 1:list<string>, 2:list<string>}>
   */
  public static function localizedWeekdayAbbreviationProvider(): array
  {
    return [
      'de' => ['de', ['So.', 'Mo.'], ['Sun', 'Mon']],
      'fr' => ['fr', ['dim.', 'lun.'], ['Sun', 'Mon']],
      'es' => ['es', ['dom', 'lun'], ['Sun', 'Mon']],
    ];
  }

  #[Test]
  public function formatLocalizedShortMonthYearUsesFrenchAbbreviationWhenLocaleIsFrench(): void
  {
    if (!class_exists('\IntlDateFormatter')) {
      $this->markTestSkipped('IntlDateFormatter extension is required.');
    }

    $formatted = Strings::formatLocalizedShortMonthYear(2026, 1, 'fr');

    $this->assertStringContainsString('2026', $formatted);
    $this->assertStringNotContainsString('Jan', $formatted);
    $this->assertMatchesRegularExpression('/janv\.?/ui', $formatted);
  }

  #[Test]
  public function formatLocalizedShortMonthUsesFrenchAbbreviationWhenLocaleIsFrench(): void
  {
    if (!class_exists('\IntlDateFormatter')) {
      $this->markTestSkipped('IntlDateFormatter extension is required.');
    }

    $formatted = Strings::formatLocalizedShortMonth(2026, 6, 'fr');

    $this->assertStringNotContainsString('Jun', $formatted);
    $this->assertMatchesRegularExpression('/juin/ui', $formatted);
  }

  #[Test]
  public function formatLocalizedMediumDateUsesFrenchMonthNameWhenLocaleIsFrench(): void
  {
    if (!class_exists('\IntlDateFormatter')) {
      $this->markTestSkipped('IntlDateFormatter extension is required.');
    }

    $date = new \DateTimeImmutable('2026-06-09', new \DateTimeZone('UTC'));
    $formatted = Strings::formatLocalizedMediumDate($date, 'fr');

    $this->assertStringContainsString('2026', $formatted);
    $this->assertStringNotContainsString('Jun', $formatted);
    $this->assertMatchesRegularExpression('/juin/ui', $formatted);
  }

  #[Test]
  public function mainCalendarIndexUsesLocalizedMonthContext(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $index = (string) file_get_contents($projectRoot . '/html/index.php');

    $this->assertStringContainsString('Strings::formatLocalizedMonthYear', $index);
    $this->assertStringNotContainsString(")->format('F Y')", $index);
  }

  #[Test]
  #[DataProvider('localizedWeekdayAbbreviationProvider')]
  public function generateWeekDayLabelsUsesLocaleAbbreviations(
    string $locale,
    array $expectedFragments,
    array $englishFragments,
  ): void {
    if (!class_exists('\IntlDateFormatter')) {
      $this->markTestSkipped('IntlDateFormatter extension is required.');
    }

    $labels = Strings::generateWeekDayLabels($locale);

    $this->assertCount(7, $labels);
    $shortLabels = array_map(static fn(array $label): string => $label['short'], $labels);
    foreach ($englishFragments as $english) {
      $this->assertNotContains($english, $shortLabels, "Unexpected English weekday in {$locale}");
    }
    foreach ($expectedFragments as $expected) {
      $this->assertContains($expected, $shortLabels, "Missing {$expected} in {$locale} weekday labels");
    }
  }

  #[Test]
  public function generateWeekDayLabelsProvidesNarrowShortAndLongLabels(): void
  {
    $labels = Strings::generateWeekDayLabels('en_US');

    $this->assertCount(7, $labels);
    $this->assertSame('S', $labels[0]['narrow']);
    $this->assertSame('Sun', $labels[0]['short']);
    $this->assertSame('Sunday', $labels[0]['long']);
    $this->assertSame('M', $labels[1]['narrow']);
    $this->assertSame('Mon', $labels[1]['short']);
    $this->assertSame('Monday', $labels[1]['long']);
  }

  #[Test]
  public function generateWeekDayLabelsUsesLocaleAwareLongAndNarrowNames(): void
  {
    if (!class_exists('\IntlDateFormatter')) {
      $this->markTestSkipped('IntlDateFormatter extension is required.');
    }

    $labels = Strings::generateWeekDayLabels('fr');

    $this->assertMatchesRegularExpression('/^lun\.?$/ui', $labels[1]['short']);
    $this->assertMatchesRegularExpression('/^lundi$/ui', $labels[1]['long']);
    $this->assertStringNotContainsString('Mon', $labels[1]['short']);
    $this->assertStringNotContainsString('Monday', $labels[1]['long']);
    $this->assertNotSame($labels[1]['short'], $labels[1]['narrow']);
  }

  #[Test]
  public function frenchPreviousStringUsesCorrectAccent(): void
  {
    $this->assertSame('Précédent', Strings::i18n('PREVIOUS', 'fr'));
    $this->assertSame('Suivant', Strings::i18n('NEXT', 'fr'));
  }

  #[Test]
  public function mainCalendarMonthGridRendersFrenchNavAndWeekdayHeaders(): void
  {
    if (!class_exists('\IntlDateFormatter')) {
      $this->markTestSkipped('IntlDateFormatter extension is required.');
    }

    $grid = new DataGrid([
      'id' => 'calendar-grid',
      'columns' => [],
      'rows' => [],
      'meta' => [
        'layout' => 'month',
        'year' => 2026,
        'month' => 6,
        'searchEnabled' => false,
        'language' => 'fr',
        'locale' => 'fr',
      ],
    ]);

    $html = $grid->table();

    $this->assertStringContainsString('<span aria-hidden="true">&lt;</span>', $html);
    $this->assertStringContainsString('<span aria-hidden="true">&gt;</span>', $html);
    $this->assertStringContainsString('aria-label="Previous month ([ or Page Up)"', $html);
    $this->assertStringContainsString('aria-label="Next month (] or Page Down)"', $html);
    $this->assertStringNotContainsString('← Précédent', $html);
    $this->assertStringNotContainsString('Suivant →', $html);
    $this->assertStringContainsString('calendar-v2-weekday-headers', $html);
    $this->assertMatchesRegularExpression('/juin/u', $html);
    $this->assertStringNotContainsString('>Sun<', $html);
    $this->assertStringNotContainsString('>Mon<', $html);
  }

  #[Test]
  public function resolveUserLocaleFallsBackToLanguageWhenConstantMissing(): void
  {
    $this->assertSame('fr', Language::resolveDateLocale('', 'fr'));
  }

  #[Test]
  public function calendarWorkEditorUsesLocalizedStringsAndEnglishColumnHeadings(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $calendarJs = (string) file_get_contents($projectRoot . '/html/js/calendar/calendar.js');
    $index = (string) file_get_contents($projectRoot . '/html/index.php');

    $this->assertStringContainsString('WORK_ENTRY_COLUMN_HEADINGS', $calendarJs);
    $this->assertStringContainsString("'Living Out Allowance'", $calendarJs);
    $this->assertStringContainsString("calendarI18n('DELETE'", $calendarJs);
    $this->assertStringContainsString('CALENDAR_MODAL_SELECT_SITE', $calendarJs);
    $this->assertStringContainsString('id="calendar-page-i18n"', $index);
    $this->assertStringContainsString('type="application/json"', $index);
    $this->assertStringContainsString('calendar-page-i18n', $calendarJs);
    $this->assertStringContainsString('formatCalendarLocaleDate', $calendarJs);
    $this->assertStringNotContainsString('>LOA<', $calendarJs);

    $this->assertSame('Agregar entrada', Strings::i18n('CALENDAR_MODAL_ADD_ENTRY', 'es'));
    $this->assertSame('Seleccionar sitio...', Strings::i18n('CALENDAR_MODAL_SELECT_SITE', 'es'));
    $this->assertSame('Select site...', Strings::i18n('CALENDAR_MODAL_SELECT_SITE', 'en'));
    $this->assertNotSame('Add Entry', Strings::i18n('CALENDAR_MODAL_ADD_ENTRY', 'fr'));
  }

  #[Test]
  public function calendarPageInjectsLockedPeriodAndUnlockI18nKeys(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $index = (string) file_get_contents($projectRoot . '/html/index.php');
    $calendarJs = (string) file_get_contents($projectRoot . '/html/js/calendar/calendar.js');

    foreach ([
      'CALENDAR_LOCKED_CANNOT_EDIT',
      'CALENDAR_LOCKED_CANNOT_EDIT_GRACE',
      'CALENDAR_UNLOCK_REQUIRED_EDIT',
      'CALENDAR_WEB_AUTHN_UNSUPPORTED',
      'CALENDAR_EMAIL_VERIFICATION_REQUIRED',
      'CALENDAR_WORK_ENTRY_LABEL',
      'CALENDAR_ENCRYPTED_DETAILS_UNAVAILABLE',
      'DATE_PICKER',
      'GROSS',
      'DEDUCTIONS',
      'NET',
    ] as $key) {
      $this->assertStringContainsString("'{$key}'", $index);
    }

    $this->assertStringContainsString("'CALENDAR_LOCKED_CANNOT_EDIT'", $calendarJs);
    $this->assertStringContainsString('formatLockedDateMessage(', $calendarJs);
    $this->assertStringContainsString("'CALENDAR_UNLOCK_REQUIRED_EDIT'", $calendarJs);
    $this->assertStringContainsString("calendarI18n('DATE_PICKER'", $calendarJs);
    $this->assertStringNotContainsString('MSG_CAL_LOCKED_CANNOT_EDIT', $calendarJs);

    $this->assertNotSame(
      'Work entry',
      Strings::i18n('CALENDAR_WORK_ENTRY_LABEL', 'fr')
    );
    $this->assertNotSame(
      'Encrypted work details are unavailable in this view.',
      Strings::i18n('CALENDAR_ENCRYPTED_DETAILS_UNAVAILABLE', 'de')
    );
  }
}
