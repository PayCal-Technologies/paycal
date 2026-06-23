<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

/**
 * Reports Page (personal earnings).
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

$legacyReportsView = InputSanitizer::getString('view');
if ($legacyReportsView === 'team' || $legacyReportsView === 'group') {
  $redirectParams = [];
  $org = InputSanitizer::getString('org');
  if ($org !== '') {
    $redirectParams['org'] = $org;
  }
  $year = InputSanitizer::getString('year');
  if ($year !== '') {
    $redirectParams['year'] = $year;
  }
  $query = $redirectParams !== [] ? '?' . http_build_query($redirectParams) : '';
  header('Location: /business/reports/' . $query, true, 302);
  exit;
}

$i18nKeys = [
  'REPORTS',
  'EARNINGS',
  'SITE_NAME',
  'EARNINGS_PAY_PERIOD_WARNING_INVALID_FREQUENCY',
  'EARNINGS_PAY_PERIOD_WARNING_INVALID_LENGTH',
  'EARNINGS_PAY_PERIOD_WARNING_INVALID_START',
  'EARNINGS_PAY_PERIOD_WARNING_GRACE_RANGE',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

Authentication::redirectHomeIfUnauthenticated();

$currentPage = 'PAGE_REPORTS';
$message = '&nbsp;';
$pageTitle = $i18n['REPORTS'] . ' - [' . $i18n['SITE_NAME'] . ']';
$pageLabel = $i18n['REPORTS'];
$pageLanguage = (string) (User::current()->language ?? 'en');
$earningsMode = InputSanitizer::getString('earnings_mode') === 'eager' ? 'eager' : 'lazy';
$earningsRenderMode = $earningsMode === 'eager' ? 'eager' : 'shell';
$isLensMode = InputSanitizer::getString('lens') === '1';

$user = User::current();
$payFrequency = strtolower(trim((string) ($user->pay_frequency ?? 'biweekly')));
$payPeriodLength = (int) ($user->pay_period_length ?? 14);
$payPeriodStart = trim((string) ($user->pay_period_start ?? ''));
$editingGraceDays = (int) ($user->editing_grace_days ?? 0);
$expectedLengths = [
  'weekly' => 7,
  'biweekly' => 14,
  'semimonthly' => 15,
  'monthly' => 30,
];

$payPeriodWarning = '';
if (!array_key_exists($payFrequency, $expectedLengths)) {
  $payPeriodWarning = $i18n['EARNINGS_PAY_PERIOD_WARNING_INVALID_FREQUENCY'];
} elseif ($payPeriodLength !== $expectedLengths[$payFrequency]) {
  $payPeriodWarning = $i18n['EARNINGS_PAY_PERIOD_WARNING_INVALID_LENGTH'];
} elseif (($payFrequency === 'weekly' || $payFrequency === 'biweekly') && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $payPeriodStart)) {
  $payPeriodWarning = $i18n['EARNINGS_PAY_PERIOD_WARNING_INVALID_START'];
} else {
  $graceMin = (int) SystemLimits::get('editing_grace_days_min');
  $graceMax = (int) SystemLimits::get('editing_grace_days_max');
  if ($editingGraceDays < $graceMin || $editingGraceDays > $graceMax) {
    $payPeriodWarning = $i18n['EARNINGS_PAY_PERIOD_WARNING_GRACE_RANGE'];
  }
}

if ($payPeriodWarning !== '') {
  $message = htmlspecialchars($payPeriodWarning, ENT_QUOTES, 'UTF-8');
}

$userUUID = User::currentUUID();

Lens::boot('reports');

if ($isLensMode) {
  $workEntryCount = count(Database::scanKeys(\PayCal\Domain\Constants\Keys::WORK . ':' . $userUUID . ':*'));
  $activeSiteCount = iterator_count(Sites::getSites($userUUID, 'active'));

  Lens::add('Earnings Backend Snapshot', [
    'page'              => $currentPage,
    'year'              => (int) date('Y'),
    'active_sites'      => $activeSiteCount,
    'work_entries'      => $workEntryCount,
    'view_mode'         => 'personal',
    'auth_user_present' => true,
  ]);
}

require_once Environment::appHome().'html/header.php';

?>

<section class="f_column w100">
  <h1 class="visually_hidden"><?php echo htmlspecialchars($i18n['REPORTS'], ENT_QUOTES, 'UTF-8'); ?></h1>
  <div class="status centered" role="status" aria-live="polite"><?php echo $message; ?></div>

  <?php echo Earnings::getInstance()->renderSections($earningsRenderMode); ?>

</section><!-- page wrapper -->
<?php

echo PHP_EOL."<link rel=\"stylesheet\" href=\"" . Render::cssURL('earnings') . "\">".PHP_EOL;
echo PHP_EOL."<link rel=\"stylesheet\" href=\"" . Render::cssURL('datagrid') . "\">".PHP_EOL;
echo PHP_EOL.Render::jsScript('earnings');
echo PHP_EOL.Render::jsScript('reports-print');

require_once Environment::appHome().'html/footer.php';
