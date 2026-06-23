<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

/**
 * Forecast Page.
 *
 * Purpose: Org-facing crew labor cost forecasting dashboard. Accepts crew
 *          parameters via GET form and projects earnings across four standard
 *          windows using CrewForecastEngine.
 *
 * Developer notes:
 * - Requires an active premium subscription (SubscriptionGate::hasActivePremium).
 * - All form inputs are sanitized through InputSanitizer before use.
 * - All monetary output is labelled ESTIMATE — not CRA-authoritative.
 * - The page is stateless (GET params only); no crew data is persisted here.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Pages
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

$currentPage = 'PAGE_FORECAST';

require_once '../config.php';

if (function_exists('forecast_index_i18n') === false) {
  function forecast_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

if (function_exists('forecast_index_i18n_fmt') === false) {
  function forecast_index_i18n_fmt(string $key, array $params = []): string
  {
    $label = forecast_index_i18n($key);
    foreach ($params as $paramKey => $paramValue) {
      $label = str_replace('{' . $paramKey . '}', (string) $paramValue, $label);
    }

    return $label;
  }
}

Authentication::redirectHomeIfUnauthenticated();

$userUUID = User::currentUUID();
$hasPremium = $userUUID !== '' && SubscriptionGate::hasActivePremium($userUUID);

$pageTitle = forecast_index_i18n('FORECAST_CREW_TITLE') . ' - [' . forecast_index_i18n('SITE_NAME') . ']';
$pageLabel = forecast_index_i18n('FORECAST_CREW_TITLE');
$pageLanguage = (string) (User::current()->language ?? 'en');

Lens::boot('forecast');

// ─── Input parsing ────────────────────────────────────────────────────────────

// Rotation pattern (14/7, 20/10, 14/14, 7/7, 5/2)
$allowedPatterns = ['14/7', '20/10', '14/14', '7/7', '5/2'];
$rotationRaw     = InputSanitizer::getString('rotation') ?? '14/7';
$rotationPattern = in_array($rotationRaw, $allowedPatterns, true) ? $rotationRaw : '14/7';

// Hourly wage rate (dollars)
$rateRaw   = InputSanitizer::getString('rate') ?? '52';
$rateDollar = is_numeric($rateRaw) && (float) $rateRaw > 0.0 ? (float) $rateRaw : 52.0;

// Number of workers of this type
$countRaw = InputSanitizer::getString('count') ?? '1';
$workerCount = is_numeric($countRaw) && (int) $countRaw >= 1 ? min((int) $countRaw, 500) : 1;

// OT rule
$allowedOtRules = ['daily', 'weekly', 'both', 'none'];
$otRuleRaw = InputSanitizer::getString('ot_rule') ?? 'both';
$otRuleStr = in_array($otRuleRaw, $allowedOtRules, true) ? $otRuleRaw : 'both';
$otRule    = OtRule::from($otRuleStr);

// Per diem (dollars per day)
$perDiemRaw = InputSanitizer::getString('per_diem') ?? '0';
$perDiemDollar = is_numeric($perDiemRaw) && (float) $perDiemRaw >= 0.0 ? (float) $perDiemRaw : 0.0;

// Province
$allowedProvinces = ['AB', 'BC', 'SK', 'MB', 'ON', 'QC', 'NS', 'NB', 'NL', 'PE', 'YT', 'NT', 'NU'];
$provinceRaw = strtoupper(InputSanitizer::getString('province') ?? 'AB');
$province    = in_array($provinceRaw, $allowedProvinces, true) ? $provinceRaw : 'AB';

// Anchor date for rotation (ISO YYYY-MM-DD, defaults to today)
$anchorRaw = InputSanitizer::getString('anchor') ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $anchorRaw)) {
  $anchorRaw = date('Y-m-d');
}
$anchorDate = new \DateTimeImmutable($anchorRaw);

// Custom shutdown end date for project-total window
$shutdownRaw = InputSanitizer::getString('shutdown') ?? '';
$shutdownDate = null;
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $shutdownRaw)) {
  $shutdownDate = new \DateTimeImmutable($shutdownRaw);
  if ($shutdownDate <= $anchorDate) {
    $shutdownDate = null;
  }
}

// ─── Build rate card and rotation ────────────────────────────────────────────

$wageRateCents  = (int) round($rateDollar * 100);
$perDiemCents   = (int) round($perDiemDollar * 100);

// Determine hoursPerDay from rotation pattern defaults
$hoursPerDayMap = ['14/7' => 8.0, '20/10' => 10.0, '14/14' => 12.0, '7/7' => 12.0, '5/2' => 8.0];
$hoursPerDay = $hoursPerDayMap[$rotationPattern] ?? 8.0;

$rateCard = new WorkerRateCard(
  wageRateCents:          $wageRateCents,
  rateType:               RateType::Hourly,
  otRule:                 $otRule,
  otThresholdDailyHours:  8.0,
  otThresholdWeeklyHours: 44.0,
  otMultiplierBasisPoints: 15000,
  perDiemCents:           $perDiemCents,
  sitePremiumCents:       0,
  taxRegion:              $province
);

$rotation = RotationTemplate::fromPattern($rotationPattern, $anchorDate);

// Build crew pairs (N identical workers)
$crewPairs = [];
for ($i = 0; $i < $workerCount; ++$i) {
  $crewPairs[] = ['rateCard' => $rateCard, 'rotation' => $rotation];
}

// ─── Forecast windows ────────────────────────────────────────────────────────

$today = new \DateTimeImmutable('today');
$windows = [
  'next_pay_period' => ForecastWindow::nextPayPeriod($today, (int) round($rotation->cycleDays() * ($rotation->workDays / $rotation->cycleDays() * 14))),
  'next_30_days'    => ForecastWindow::next30Days($today),
  'quarter'         => ForecastWindow::quarter($today),
];
if ($shutdownDate !== null) {
  $windows['shutdown_total'] = ForecastWindow::projectTotal($today, $shutdownDate);
}

$windowLabelKeys = [
  'next_pay_period' => 'FORECAST_WINDOW_NEXT_PAY_PERIOD',
  'next_30_days'    => 'FORECAST_WINDOW_NEXT_30_DAYS',
  'quarter'         => 'FORECAST_WINDOW_QUARTER',
  'shutdown_total'  => 'FORECAST_WINDOW_SHUTDOWN_TOTAL',
];

$otRuleLabelKeys = [
  'daily'  => 'FORECAST_OT_RULE_DAILY',
  'weekly' => 'FORECAST_OT_RULE_WEEKLY',
  'both'   => 'FORECAST_OT_RULE_BOTH',
  'none'   => 'FORECAST_OT_RULE_NONE',
];

$crewSummaryFmt = $workerCount === 1
  ? forecast_index_i18n_fmt('FORECAST_CREW_SUMMARY_SINGULAR_FMT', [
    'count'    => (string) $workerCount,
    'rotation' => $rotationPattern,
    'rate'     => number_format($rateDollar, 2),
  ])
  : forecast_index_i18n_fmt('FORECAST_CREW_SUMMARY_PLURAL_FMT', [
    'count'    => (string) $workerCount,
    'rotation' => $rotationPattern,
    'rate'     => number_format($rateDollar, 2),
  ]);

// Compute results per window
/** @var array<string, ForecastResult> $results */
$results = [];
foreach ($windows as $windowKey => $window) {
  $results[$windowKey] = CrewForecastEngine::forecastCrew($crewPairs, $window);
}

// Helper: format dollars
$fmt = static fn(int $cents): string => '$' . number_format($cents / 100, 2, '.', ',');

require_once Config\Environment::appHome() . 'html/header.php';
?>

<section class="f_column w100 forecast-page">
  <h1 class="visually_hidden"><?php echo forecast_index_i18n('FORECAST_CREW_TITLE'); ?></h1>

  <section class="panel w100 forecast-panel" aria-labelledby="forecast-crew-parameters-heading">
    <h2 id="forecast-crew-parameters-heading" class="forecast-panel__title"><?php echo forecast_index_i18n('FORECAST_CREW_PARAMETERS'); ?></h2>
    <p class="forecast-panel__intro"><?php echo forecast_index_i18n('FORECAST_CREW_PARAMETERS_INTRO_LEDE'); ?> <strong><?php echo forecast_index_i18n('FORECAST_ESTIMATE_BADGE'); ?></strong> <?php echo forecast_index_i18n('FORECAST_CREW_PARAMETERS_INTRO_TAIL'); ?></p>

    <form class="forecast-form" method="get" action="/forecast/" aria-label="<?php echo htmlspecialchars(forecast_index_i18n('FORECAST_CREW_FORM_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_rotation"><?php echo forecast_index_i18n('FORECAST_ROTATION'); ?></label>
        <select class="forecast-form__select" id="fc_rotation" name="rotation" aria-describedby="fc_rotation_hint">
          <?php foreach ($allowedPatterns as $pat): ?>
            <option value="<?php echo htmlspecialchars($pat, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $rotationPattern === $pat ? ' selected' : ''; ?>>
              <?php echo htmlspecialchars($pat, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <span id="fc_rotation_hint" class="forecast-form__hint"><?php echo forecast_index_i18n('FORECAST_ROTATION_HINT'); ?></span>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_rate"><?php echo forecast_index_i18n('FORECAST_HOURLY_RATE'); ?></label>
        <input class="forecast-form__input" id="fc_rate" type="number" name="rate"
               min="1" max="9999" step="0.01"
               value="<?php echo htmlspecialchars((string) $rateDollar, ENT_QUOTES, 'UTF-8'); ?>"
               aria-describedby="fc_rate_hint">
        <span id="fc_rate_hint" class="forecast-form__hint"><?php echo forecast_index_i18n('FORECAST_HOURLY_RATE_HINT'); ?></span>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_count"><?php echo forecast_index_i18n('FORECAST_WORKERS'); ?></label>
        <input class="forecast-form__input" id="fc_count" type="number" name="count"
               min="1" max="500" step="1"
               value="<?php echo htmlspecialchars((string) $workerCount, ENT_QUOTES, 'UTF-8'); ?>"
               aria-describedby="fc_count_hint">
        <span id="fc_count_hint" class="forecast-form__hint"><?php echo forecast_index_i18n('FORECAST_WORKERS_HINT'); ?></span>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_ot_rule"><?php echo forecast_index_i18n('FORECAST_OT_RULE'); ?></label>
        <select class="forecast-form__select" id="fc_ot_rule" name="ot_rule">
          <?php foreach ($allowedOtRules as $rule): ?>
            <option value="<?php echo htmlspecialchars($rule, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $otRuleStr === $rule ? ' selected' : ''; ?>>
              <?php echo htmlspecialchars(forecast_index_i18n($otRuleLabelKeys[$rule] ?? 'FORECAST_OT_RULE_' . strtoupper($rule)), ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_per_diem"><?php echo forecast_index_i18n('FORECAST_PER_DIEM'); ?></label>
        <input class="forecast-form__input" id="fc_per_diem" type="number" name="per_diem"
               min="0" max="9999" step="0.01"
               value="<?php echo htmlspecialchars((string) $perDiemDollar, ENT_QUOTES, 'UTF-8'); ?>"
               aria-describedby="fc_per_diem_hint">
        <span id="fc_per_diem_hint" class="forecast-form__hint"><?php echo forecast_index_i18n('FORECAST_PER_DIEM_HINT'); ?></span>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_province"><?php echo forecast_index_i18n('PROVINCE'); ?></label>
        <select class="forecast-form__select" id="fc_province" name="province">
          <?php foreach ($allowedProvinces as $prov): ?>
            <option value="<?php echo htmlspecialchars($prov, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $province === $prov ? ' selected' : ''; ?>>
              <?php echo htmlspecialchars($prov, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_anchor"><?php echo forecast_index_i18n('FORECAST_ANCHOR_DATE'); ?></label>
        <input class="forecast-form__input" id="fc_anchor" type="date" name="anchor"
               value="<?php echo htmlspecialchars($anchorRaw, ENT_QUOTES, 'UTF-8'); ?>"
               aria-describedby="fc_anchor_hint">
        <span id="fc_anchor_hint" class="forecast-form__hint"><?php echo forecast_index_i18n('FORECAST_ANCHOR_DATE_HINT'); ?></span>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_shutdown"><?php echo forecast_index_i18n('FORECAST_SHUTDOWN_END_DATE'); ?> <span class="forecast-form__optional"><?php echo forecast_index_i18n('FORECAST_OPTIONAL'); ?></span></label>
        <input class="forecast-form__input" id="fc_shutdown" type="date" name="shutdown"
               value="<?php echo htmlspecialchars($shutdownRaw, ENT_QUOTES, 'UTF-8'); ?>"
               aria-describedby="fc_shutdown_hint">
        <span id="fc_shutdown_hint" class="forecast-form__hint"><?php echo forecast_index_i18n('FORECAST_SHUTDOWN_END_HINT'); ?></span>
      </div>

      <div class="forecast-form__actions">
        <button type="submit" class="btn btn--primary forecast-form__submit"><?php echo forecast_index_i18n('FORECAST_UPDATE_BUTTON'); ?></button>
      </div>
    </form>
  </section>

  <section class="panel w100 forecast-panel" aria-labelledby="forecast-windows-heading">
    <h2 id="forecast-windows-heading" class="forecast-panel__title">
      <?php echo forecast_index_i18n('FORECAST_PROJECTED_LABOR_COST'); ?>
      <span class="forecast-panel__crew-summary">
        — <?php echo htmlspecialchars($crewSummaryFmt, ENT_QUOTES, 'UTF-8'); ?>
      </span>
    </h2>

    <div class="datagrid datagrid_cols_6 datagrid_layout_auto forecast-results-grid"
         role="region" aria-label="<?php echo htmlspecialchars(forecast_index_i18n('FORECAST_WINDOWS_GRID_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
      <div class="datagrid_table" role="grid" aria-colcount="6">
        <div class="datagrid_header_row" role="rowgroup">
          <div class="datagrid_header_content" role="row">
            <div class="datagrid_heading" role="columnheader"><?php echo forecast_index_i18n('FORECAST_COL_WINDOW'); ?></div>
            <div class="datagrid_heading" role="columnheader"><?php echo forecast_index_i18n('DAYS'); ?></div>
            <div class="datagrid_heading" role="columnheader"><?php echo forecast_index_i18n('FORECAST_COL_WORK_DAYS'); ?></div>
            <div class="datagrid_heading" role="columnheader"><?php echo forecast_index_i18n('EARNINGS_BREAKDOWN_OT_HOURS_LABEL'); ?></div>
            <div class="datagrid_heading" role="columnheader"><?php echo forecast_index_i18n('EARNINGS_FORECAST_EST_GROSS'); ?></div>
            <div class="datagrid_heading" role="columnheader"><?php echo forecast_index_i18n('EARNINGS_FORECAST_EST_NET'); ?></div>
          </div>
        </div>
        <div class="datagrid_body" role="rowgroup">
          <?php foreach ($results as $windowKey => $result): ?>
            <?php $window = $windows[$windowKey]; ?>
            <?php $windowLabel = forecast_index_i18n($windowLabelKeys[$windowKey] ?? $windowKey); ?>
            <div class="datagrid_row<?php echo $windowKey === 'shutdown_total' ? ' forecast-row--shutdown' : ''; ?>" role="row">
              <div class="datagrid_row_content" role="presentation">
                <div class="datagrid_item" role="gridcell"><?php echo htmlspecialchars($windowLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="datagrid_item" role="gridcell"><?php echo htmlspecialchars((string) $window->days(), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="datagrid_item" role="gridcell"><?php echo htmlspecialchars((string) $result->onsiteDays, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="datagrid_item forecast-ot" role="gridcell"><?php echo htmlspecialchars(number_format($result->otHours, 1), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="datagrid_item forecast-gross" role="gridcell"><?php echo htmlspecialchars($fmt($result->estimatedGrossCents), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="datagrid_item forecast-net" role="gridcell"><?php echo htmlspecialchars($fmt($result->estimatedNetCents), ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <p class="forecast-disclaimer">
      <?php echo htmlspecialchars(
        forecast_index_i18n_fmt('FORECAST_DISCLAIMER_FMT', ['province' => $province]),
        ENT_QUOTES,
        'UTF-8'
      ); ?>
    </p>
  </section>

  <?php if (!$hasPremium && !User::isAdmin()): ?>
  <section class="panel w100 forecast-panel forecast-upgrade-notice" aria-labelledby="forecast-upgrade-heading">
    <h2 id="forecast-upgrade-heading" class="visually_hidden"><?php echo forecast_index_i18n('FORECAST_UPGRADE_SECTION_ARIA'); ?></h2>
    <p>
      <strong><?php echo forecast_index_i18n('FORECAST_UPGRADE_TITLE'); ?></strong>
      <?php echo forecast_index_i18n('FORECAST_UPGRADE_LEDE_SUFFIX'); ?>
      <a href="/pricing/"><?php echo forecast_index_i18n('FORECAST_UPGRADE_LINK'); ?></a>
      <?php echo forecast_index_i18n('FORECAST_UPGRADE_LINK_SUFFIX'); ?>
    </p>
  </section>
  <?php endif; ?>

</section><!-- forecast-page -->

<?php
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('forecast') . '">' . PHP_EOL;

require_once Config\Environment::appHome() . 'html/footer.php';
