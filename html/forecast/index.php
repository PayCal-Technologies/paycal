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

Authentication::redirectHomeIfUnauthenticated();

$userUUID = User::currentUUID();
$hasPremium = $userUUID !== '' && SubscriptionGate::hasActivePremium($userUUID);

$pageTitle   = 'Crew Forecast - [' . Strings::i18n('SITE_NAME') . ']';
$pageLabel   = 'Crew Forecast';
$pageLanguage = 'en';

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
  'Next Pay Period' => ForecastWindow::nextPayPeriod($today, (int) round($rotation->cycleDays() * ($rotation->workDays / $rotation->cycleDays() * 14))),
  'Next 30 Days'    => ForecastWindow::next30Days($today),
  'Quarter'         => ForecastWindow::quarter($today),
];
if ($shutdownDate !== null) {
  $windows['Shutdown Total'] = ForecastWindow::projectTotal($today, $shutdownDate);
}

// Compute results per window
/** @var array<string, ForecastResult> $results */
$results = [];
foreach ($windows as $wLabel => $window) {
  $results[$wLabel] = CrewForecastEngine::forecastCrew($crewPairs, $window);
}

// Helper: format dollars
$fmt = static fn(int $cents): string => '$' . number_format($cents / 100, 2, '.', ',');

require_once Config\Environment::appHome() . 'html/header.php';
?>

<section class="f_column w100 forecast-page">
  <h1 class="visually_hidden">Crew Forecast</h1>

  <section class="panel w100 forecast-panel" aria-label="Crew parameters">
    <h2 class="forecast-panel__title">Crew Parameters</h2>
    <p class="forecast-panel__intro">Configure your crew below to project labor costs. All figures are <strong>ESTIMATES</strong> — not CRA-authoritative payroll calculations.</p>

    <form class="forecast-form" method="get" action="/forecast/" aria-label="Crew forecast parameters">
      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_rotation">Rotation</label>
        <select class="forecast-form__select" id="fc_rotation" name="rotation" aria-describedby="fc_rotation_hint">
          <?php foreach ($allowedPatterns as $pat): ?>
            <option value="<?php echo htmlspecialchars($pat, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $rotationPattern === $pat ? ' selected' : ''; ?>>
              <?php echo htmlspecialchars($pat, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <span id="fc_rotation_hint" class="forecast-form__hint">Work/rest days per cycle</span>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_rate">Hourly Rate ($)</label>
        <input class="forecast-form__input" id="fc_rate" type="number" name="rate"
               min="1" max="9999" step="0.01"
               value="<?php echo htmlspecialchars((string) $rateDollar, ENT_QUOTES, 'UTF-8'); ?>"
               aria-describedby="fc_rate_hint">
        <span id="fc_rate_hint" class="forecast-form__hint">Base hourly wage in dollars</span>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_count">Workers</label>
        <input class="forecast-form__input" id="fc_count" type="number" name="count"
               min="1" max="500" step="1"
               value="<?php echo htmlspecialchars((string) $workerCount, ENT_QUOTES, 'UTF-8'); ?>"
               aria-describedby="fc_count_hint">
        <span id="fc_count_hint" class="forecast-form__hint">Number of workers with these parameters</span>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_ot_rule">OT Rule</label>
        <select class="forecast-form__select" id="fc_ot_rule" name="ot_rule">
          <?php foreach ($allowedOtRules as $rule): ?>
            <option value="<?php echo htmlspecialchars($rule, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $otRuleStr === $rule ? ' selected' : ''; ?>>
              <?php echo htmlspecialchars(ucfirst($rule), ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_per_diem">Per Diem ($/day)</label>
        <input class="forecast-form__input" id="fc_per_diem" type="number" name="per_diem"
               min="0" max="9999" step="0.01"
               value="<?php echo htmlspecialchars((string) $perDiemDollar, ENT_QUOTES, 'UTF-8'); ?>"
               aria-describedby="fc_per_diem_hint">
        <span id="fc_per_diem_hint" class="forecast-form__hint">Daily living-out allowance per worker</span>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_province">Province</label>
        <select class="forecast-form__select" id="fc_province" name="province">
          <?php foreach ($allowedProvinces as $prov): ?>
            <option value="<?php echo htmlspecialchars($prov, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $province === $prov ? ' selected' : ''; ?>>
              <?php echo htmlspecialchars($prov, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_anchor">Rotation Anchor Date</label>
        <input class="forecast-form__input" id="fc_anchor" type="date" name="anchor"
               value="<?php echo htmlspecialchars($anchorRaw, ENT_QUOTES, 'UTF-8'); ?>"
               aria-describedby="fc_anchor_hint">
        <span id="fc_anchor_hint" class="forecast-form__hint">First day of the first work block</span>
      </div>

      <div class="forecast-form__row">
        <label class="forecast-form__label" for="fc_shutdown">Shutdown End Date <span class="forecast-form__optional">(optional)</span></label>
        <input class="forecast-form__input" id="fc_shutdown" type="date" name="shutdown"
               value="<?php echo htmlspecialchars($shutdownRaw, ENT_QUOTES, 'UTF-8'); ?>"
               aria-describedby="fc_shutdown_hint">
        <span id="fc_shutdown_hint" class="forecast-form__hint">Adds a Shutdown Total window</span>
      </div>

      <div class="forecast-form__actions">
        <button type="submit" class="btn btn--primary forecast-form__submit">Update Forecast</button>
      </div>
    </form>
  </section>

  <section class="panel w100 forecast-panel" aria-label="Crew cost forecast windows">
    <h2 class="forecast-panel__title">
      Projected Labor Cost
      <span class="forecast-panel__crew-summary">
        — <?php echo htmlspecialchars((string) $workerCount, ENT_QUOTES, 'UTF-8'); ?> worker<?php echo $workerCount !== 1 ? 's' : ''; ?>,
        <?php echo htmlspecialchars($rotationPattern, ENT_QUOTES, 'UTF-8'); ?> rotation,
        $<?php echo htmlspecialchars(number_format($rateDollar, 2), ENT_QUOTES, 'UTF-8'); ?>/hr
      </span>
    </h2>

    <div class="datagrid datagrid_cols_6 datagrid_layout_auto forecast-results-grid"
         role="region" aria-label="Forecast windows">
      <div class="datagrid_table" role="grid" aria-colcount="6">
        <div class="datagrid_header_row" role="rowgroup">
          <div class="datagrid_header_content" role="row">
            <div class="datagrid_heading" role="columnheader">Window</div>
            <div class="datagrid_heading" role="columnheader">Days</div>
            <div class="datagrid_heading" role="columnheader">Work Days</div>
            <div class="datagrid_heading" role="columnheader">OT Hours</div>
            <div class="datagrid_heading" role="columnheader">Est. Gross</div>
            <div class="datagrid_heading" role="columnheader">Est. Net</div>
          </div>
        </div>
        <div class="datagrid_body" role="rowgroup">
          <?php foreach ($results as $wLabel => $result): ?>
            <?php $window = $windows[$wLabel]; ?>
            <div class="datagrid_row<?php echo $wLabel === 'Shutdown Total' ? ' forecast-row--shutdown' : ''; ?>" role="row">
              <div class="datagrid_row_content" role="presentation">
                <div class="datagrid_item" role="gridcell"><?php echo htmlspecialchars($wLabel, ENT_QUOTES, 'UTF-8'); ?></div>
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
      All figures are projections based on the parameters above. Actual deductions depend on year-to-date income,
      benefit elections, and other factors. Not CRA-authoritative. Tax estimates use annualised marginal-rate
      approximations for <?php echo htmlspecialchars($province, ENT_QUOTES, 'UTF-8'); ?>.
    </p>
  </section>

  <?php if (!$hasPremium && !User::isAdmin()): ?>
  <section class="panel w100 forecast-panel forecast-upgrade-notice" aria-label="Upgrade notice">
    <p>
      <strong>Crew Forecasting</strong> is included in PayCal Premium.
      <a href="/premium/">Upgrade to unlock</a> full crew management, multi-site analytics, and PDF export.
    </p>
  </section>
  <?php endif; ?>

</section><!-- forecast-page -->

<?php
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('forecast') . '">' . PHP_EOL;

require_once Config\Environment::appHome() . 'html/footer.php';
