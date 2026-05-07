<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

/**
 * Earnings Page.
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

$i18nKeys = ['EARNINGS', 'SITE_NAME'];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

Authentication::redirectHomeIfUnauthenticated();

$currentPage = 'PAGE_EARNINGS';
$message = '&nbsp;';
$pageTitle = $i18n['EARNINGS'] . ' - [' . $i18n['SITE_NAME'] . ']';
$pageLabel = $i18n['EARNINGS'];
$pageLanguage = 'en';
$earningsMode = InputSanitizer::getString('earnings_mode') === 'eager' ? 'eager' : 'lazy';

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
  $payPeriodWarning = 'Warning: Pay period frequency is invalid. Open Profile > Pay Period to fix your settings.';
} elseif ($payPeriodLength !== $expectedLengths[$payFrequency]) {
  $payPeriodWarning = 'Warning: Pay period length does not match frequency. Open Profile > Pay Period to fix your settings.';
} elseif (($payFrequency === 'weekly' || $payFrequency === 'biweekly') && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $payPeriodStart)) {
  $payPeriodWarning = 'Warning: Pay period start date is missing or invalid. Open Profile > Pay Period and pick a valid start date.';
} else {
  $graceMin = (int) SystemLimits::get('editing_grace_days_min');
  $graceMax = (int) SystemLimits::get('editing_grace_days_max');
  if ($editingGraceDays < $graceMin || $editingGraceDays > $graceMax) {
    $payPeriodWarning = 'Warning: Grace-day setting is out of range. Open Profile > Pay Period to fix your settings.';
  }
}

if ($payPeriodWarning !== '') {
  $message = htmlspecialchars($payPeriodWarning, ENT_QUOTES, 'UTF-8');
}

Lens::boot('earnings');

if (InputSanitizer::getString('lens') === '1') {
  $userUUID = User::currentUUID();
  $workEntryCount = count(Database::scanKeys(Keys::WORK . ':' . $userUUID . ':*'));
  $activeSiteCount = iterator_count(Sites::getSites($userUUID, 'active'));

  Lens::add('Earnings Backend Snapshot', [
    'page' => $currentPage,
    'year' => (int) date('Y'),
    'active_sites' => $activeSiteCount,
    'work_entries' => $workEntryCount,
    'auth_user_present' => true,
  ]);
}

require_once Environment::appHome().'html/header.php';

?>


<section class="f_column w100">
  <h1 class="visually_hidden"><?php echo htmlspecialchars($i18n['EARNINGS'], ENT_QUOTES, 'UTF-8'); ?></h1>
  <div class="status centered" role="status" aria-live="polite"><?php echo $message; ?></div>
  <?php echo Earnings::getInstance()->renderSections($earningsMode); ?>
</section><!-- page wrapper -->

<?php

echo PHP_EOL."<link rel=\"stylesheet\" href=\"" . Render::cssURL('earnings') . "\">".PHP_EOL;
echo PHP_EOL."<link rel=\"stylesheet\" href=\"" . Render::cssURL('datagrid') . "\">".PHP_EOL;
echo PHP_EOL.Render::jsScript('earnings');

require_once Environment::appHome().'html/footer.php';
