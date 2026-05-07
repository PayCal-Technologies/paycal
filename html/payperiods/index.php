<?php declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\Calendar;
use PayCal\Domain\Earnings;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Render;
use PayCal\Domain\Strings;
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

$i18nKeys = [
  'CALENDAR_DAYS',
  'DEDUCTIONS',
  'GROSS',
  'NET',
  'OVERTIME',
  'PAY_PERIOD_PROGRESS',
  'REGULAR',
  'TO',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

Authentication::redirectHomeIfUnauthenticated();

$currentPage = 'PAGE_PAYPERIODS';
$pageTitle = 'Pay Periods - [PayCal]';
$pageLabel = 'Pay Periods';
$pageLanguage = 'en';

\PayCal\Observability\Lens::boot('payperiods');

require_once HTML.'/header.php';

$payPeriods = Calendar::getCurrentPayPeriods();
$ppData = $payPeriods->getPayPeriodForDate(new \DateTimeImmutable('now'));

$totals = Earnings::getTotalsForRange($ppData['start'], $ppData['end']);

$regular = (float) $totals['hours']['regular'];
$overtime = (float) $totals['hours']['overtime'];
$gross = (float) $totals['totals']['gross'];

$deductions = (float) array_sum($totals['deductions']);

$net = (float) $totals['totals']['net'];

$label = $ppData['start']->format('M d').'&nbsp;'.$i18n['TO'].'&nbsp;'.$ppData['end']->format('M d, Y');
$subLabel = 'Pay Period #'.$ppData['number'];
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

if (\PayCal\Domain\InputSanitizer::getString('lens') === '1') {
  \PayCal\Observability\Lens::add('PayPeriods Backend Snapshot', [
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
    '__LABEL__' => $label,
    '__SUBLABEL__' => $subLabel,
    '__PROGRESS_LABEL__' => $i18n['PAY_PERIOD_PROGRESS'],
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

echo "<div class='data-cards'>";
echo Render::template('pay-period-card', $renders);
echo '</div>';

echo PHP_EOL."<link rel=\"stylesheet\" href=\"" . Render::cssURL('payperiods') . "\">".PHP_EOL;
echo PHP_EOL.Render::jsScript('payperiods');

require_once HTML.'/footer.php';
