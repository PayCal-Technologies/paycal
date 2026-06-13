<?php declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\Calendar;
use PayCal\Domain\Earnings;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Render;
use PayCal\Domain\Strings;
use PayCal\Domain\User;
use PayCal\Observability\Lens;

/**
 * Pay Periods Page.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Pages
 *
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

require_once '../config.php';

if (function_exists('payperiods_index_i18n') === false) {
  function payperiods_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

$i18nKeys = [
  'CALENDAR_DAYS',
  'DEDUCTIONS',
  'EARNINGS_PAY_PERIOD_ARIA_FMT',
  'GROSS',
  'NET',
  'OVERTIME',
  'PAY_PERIOD_NUMBER_FMT',
  'PAY_PERIOD_PROGRESS',
  'PAY_PERIOD_PROGRESS_ARIA_FMT',
  'PAY_PERIODS',
  'REGULAR',
  'SITE_NAME',
  'TO',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = payperiods_index_i18n($i18nKey);
}

Authentication::redirectHomeIfUnauthenticated();

$currentPage = 'PAGE_PAYPERIODS';
$pageTitle = $i18n['PAY_PERIODS'] . ' - [' . $i18n['SITE_NAME'] . ']';
$pageLabel = $i18n['PAY_PERIODS'];
$pageLanguage = (string) (User::current()->language ?? 'en');

Lens::boot('payperiods');

require_once HTML.'/header.php';

$payPeriods = Calendar::getCurrentPayPeriods();
$ppData = $payPeriods->getPayPeriodForDate(new \DateTimeImmutable('now'));

$totals = Earnings::getTotalsForRange($ppData['start'], $ppData['end']);

$regular = (float) $totals['hours']['regular'];
$overtime = (float) $totals['hours']['overtime'];
$gross = (float) $totals['totals']['gross'];

$deductions = (float) array_sum($totals['deductions']);

$net = (float) $totals['totals']['net'];

$periodStartLabel = Strings::formatLocalizedMediumDate($ppData['start']);
$periodEndLabel = Strings::formatLocalizedMediumDate($ppData['end']);
$label = $periodStartLabel . '&nbsp;' . $i18n['TO'] . '&nbsp;' . $periodEndLabel;
$subLabel = str_replace('{number}', (string) $ppData['number'], $i18n['PAY_PERIOD_NUMBER_FMT']);
$periodStart = $ppData['start'];
$periodEnd = $ppData['end'];
$now = new DateTimeImmutable('now', $periodStart->getTimezone());
$totalDays = (int) $periodStart->diff($periodEnd)->days + 1;

if ($now < $periodStart) {
  $elapsedDays = 0;
} elseif ($now > $periodEnd) {
  $elapsedDays = $totalDays;
} else {
  $elapsedDays = (int) $periodStart->diff($now)->days + 1;
}

$barLength = 20;
$filled = (int) floor(($elapsedDays / max(1, $totalDays)) * $barLength);
$filled = max(0, min($barLength, $filled));
$progressBar = '['.str_repeat('#', $filled).str_repeat('-', $barLength - $filled).']';
$progressText = sprintf('%d/%d %s', $elapsedDays, $totalDays, $i18n['CALENDAR_DAYS']);
$progressAria = str_replace(
  ['{elapsed}', '{total}'],
  [(string) $elapsedDays, (string) $totalDays],
  $i18n['PAY_PERIOD_PROGRESS_ARIA_FMT']
);
$payPeriodAria = str_replace(
  ['{start}', '{end}'],
  [$periodStartLabel, $periodEndLabel],
  $i18n['EARNINGS_PAY_PERIOD_ARIA_FMT']
);

if (InputSanitizer::getString('lens') === '1') {
  Lens::add('PayPeriods Backend Snapshot', [
    'page' => $currentPage,
    'period_number' => (int) $ppData['number'],
    'period_start' => $periodStart->format('Y-m-d'),
    'period_end' => $periodEnd->format('Y-m-d'),
    'total_days' => $totalDays,
    'elapsed_days' => $elapsedDays,
    'regular_hours' => $regular,
    'overtime_hours' => $overtime,
    'gross' => $gross,
    'deductions' => $deductions,
    'net' => $net,
  ]);
}

$renders = [
    '__PAY_PERIOD_ARIA__' => htmlspecialchars($payPeriodAria, ENT_QUOTES, 'UTF-8'),
    '__PAY_PERIOD_HEADING_ID__' => 'pay-period-current-heading',
    '__LABEL__' => $label,
    '__SUBLABEL__' => htmlspecialchars($subLabel, ENT_QUOTES, 'UTF-8'),
    '__PROGRESS_LABEL__' => $i18n['PAY_PERIOD_PROGRESS'],
    '__PROGRESS_ARIA__' => htmlspecialchars($progressAria, ENT_QUOTES, 'UTF-8'),
    '__PROGRESS_BAR__' => $progressBar,
    '__PROGRESS_TEXT__' => $progressText,
    '__REGULAR_HOURS_LABEL__' => $i18n['REGULAR'],
    '__REGULAR_HOURS__' => Strings::formatLocalizedNumber($regular, 2, 2),
    '__OVERTIME_HOURS_LABEL__' => $i18n['OVERTIME'],
    '__OVERTIME_HOURS__' => Strings::formatLocalizedNumber($overtime, 2, 2),
    '__GROSS_LABEL__' => $i18n['GROSS'],
    '__GROSS__' => '$'.Strings::formatLocalizedNumber($gross, 2, 2),
    '__DEDUCTIONS_LABEL__' => $i18n['DEDUCTIONS'],
    '__DEDUCTIONS__' => '$'.Strings::formatLocalizedNumber($deductions, 2, 2),
    '__NET_LABEL__' => $i18n['NET'],
    '__NET__' => '$'.Strings::formatLocalizedNumber($net, 2, 2),
];

echo '<section class="f_column w100 payperiods-page">';
echo '<h1 class="visually_hidden">' . htmlspecialchars($i18n['PAY_PERIODS'], ENT_QUOTES, 'UTF-8') . '</h1>';
echo "<div class='data-cards'>";
echo Render::template('pay-period-card', $renders);
echo '</div>';
echo '</section>';

echo PHP_EOL."<link rel=\"stylesheet\" href=\"" . Render::cssURL('payperiods') . "\">".PHP_EOL;
echo PHP_EOL.Render::jsScript('payperiods');

require_once HTML.'/footer.php';
