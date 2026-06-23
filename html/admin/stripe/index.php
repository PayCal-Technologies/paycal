<?php declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\DataGrid;
use PayCal\Domain\MetricsService;
use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once '../../config.php';

$currentPage = 'PAGE_ADMIN';
$pageTitle = Strings::i18n('ADMIN_STRIPE_DASHBOARD') . ' - [PayCal]';
$pageLabel = Strings::i18n('ADMIN_STRIPE_DASHBOARD');

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/stripe/');

if (function_exists('admin_stripe_i18n') === false) {
  function admin_stripe_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

/**
 * @param array<string, mixed> $arr
 * @return array<string, mixed>
 */
function getArrayValue(array $arr, string $key): array {
  return isset($arr[$key]) && is_array($arr[$key]) ? $arr[$key] : [];
}

/**
 * @param array<string, mixed> $arr
 */
function getIntValue(array $arr, string $key, int $default = 0): int {
  return isset($arr[$key]) && is_numeric($arr[$key]) ? (int) $arr[$key] : $default;
}

/**
 * @param array<string, mixed> $arr
 */
function getStringValue(array $arr, string $key, string $default = ''): string {
  return isset($arr[$key]) && is_scalar($arr[$key]) ? (string) $arr[$key] : $default;
}

function formatStripeNumber(int|float $value, int $fractionDigits = 0): string {
  return Strings::formatLocalizedNumber($value, $fractionDigits, $fractionDigits);
}

$billingWebhooks = MetricsService::getBillingWebhookMetrics();
$billingWebhookOutcomes = getArrayValue($billingWebhooks, 'outcomes');
$billingWebhookEvents = getArrayValue($billingWebhooks, 'event_types');
$billingWebhookRecentDays = getArrayValue($billingWebhooks, 'recent_days');
$billingWebhookRecentThirtyDays = getArrayValue($billingWebhooks, 'recent_30_days');
$billingWebhookRollingThirtyTotals = getArrayValue($billingWebhooks, 'rolling_30_totals');
$summaryDate = getStringValue($billingWebhooks, 'date', date('Y-m-d'));

// Prepare data for event types grid
$eventTypesRows = [];
foreach ($billingWebhookEvents as $eventType => $summary) {
  $eventSummary = is_array($summary) ? $summary : [];
  $eventTypesRows[] = [
    'event_type' => (string) $eventType,
    'processed' => formatStripeNumber(getIntValue($eventSummary, 'processed', 0)),
    'duplicate' => formatStripeNumber(getIntValue($eventSummary, 'duplicate', 0)),
  ];
}

// Prepare data for 7-day trend grid
$sevenDayRows = [];
foreach ($billingWebhookRecentDays as $row) {
  $day = is_array($row) ? $row : [];
  $sevenDayRows[] = [
    'date' => getStringValue($day, 'date', ''),
    'processed' => formatStripeNumber(getIntValue($day, 'processed', 0)),
    'duplicate' => formatStripeNumber(getIntValue($day, 'duplicate', 0)),
    'verify_fail' => formatStripeNumber(getIntValue($day, 'verification_failed', 0)),
    'rejected' => formatStripeNumber(getIntValue($day, 'event_rejected', 0)),
  ];
}

// Prepare data for 30-day trend grid
$thirtyDayRows = [];
foreach ($billingWebhookRecentThirtyDays as $row) {
  $day = is_array($row) ? $row : [];
  $thirtyDayRows[] = [
    'date' => getStringValue($day, 'date', ''),
    'processed' => formatStripeNumber(getIntValue($day, 'processed', 0)),
    'duplicate' => formatStripeNumber(getIntValue($day, 'duplicate', 0)),
    'verify_fail' => formatStripeNumber(getIntValue($day, 'verification_failed', 0)),
    'rejected' => formatStripeNumber(getIntValue($day, 'event_rejected', 0)),
  ];
}

// Create DataGrid instances
$eventTypesGrid = new DataGrid([
  'id' => 'stripe-event-types-grid',
  'columns' => [
    ['key' => 'event_type', 'label' => admin_stripe_i18n('ADMIN_STRIPE_EVENT_TYPE'), 'sortable' => false],
    ['key' => 'processed', 'label' => admin_stripe_i18n('ADMIN_STRIPE_PROCESSED'), 'sortable' => false],
    ['key' => 'duplicate', 'label' => admin_stripe_i18n('ADMIN_STRIPE_DUPLICATE_LOWER'), 'sortable' => false],
  ],
  'rows' => $eventTypesRows,
  'meta' => [
    'layout' => 'auto',
    'noChrome' => false,
  ],
]);

$sevenDayGrid = new DataGrid([
  'id' => 'stripe-7day-trend-grid',
  'columns' => [
    ['key' => 'date', 'label' => admin_stripe_i18n('DATE'), 'sortable' => false],
    ['key' => 'processed', 'label' => admin_stripe_i18n('ADMIN_STRIPE_PROCESSED'), 'sortable' => false],
    ['key' => 'duplicate', 'label' => admin_stripe_i18n('ADMIN_STRIPE_DUPLICATE_LOWER'), 'sortable' => false],
    ['key' => 'verify_fail', 'label' => admin_stripe_i18n('ADMIN_STRIPE_VERIFY_FAIL_LOWER'), 'sortable' => false],
    ['key' => 'rejected', 'label' => admin_stripe_i18n('ADMIN_STRIPE_REJECTED_LOWER'), 'sortable' => false],
  ],
  'rows' => $sevenDayRows,
  'meta' => [
    'layout' => 'auto',
    'noChrome' => false,
  ],
]);

$thirtyDayGrid = new DataGrid([
  'id' => 'stripe-30day-trend-grid',
  'columns' => [
    ['key' => 'date', 'label' => admin_stripe_i18n('DATE'), 'sortable' => false],
    ['key' => 'processed', 'label' => admin_stripe_i18n('ADMIN_STRIPE_PROCESSED'), 'sortable' => false],
    ['key' => 'duplicate', 'label' => admin_stripe_i18n('ADMIN_STRIPE_DUPLICATE_LOWER'), 'sortable' => false],
    ['key' => 'verify_fail', 'label' => admin_stripe_i18n('ADMIN_STRIPE_VERIFY_FAIL_LOWER'), 'sortable' => false],
    ['key' => 'rejected', 'label' => admin_stripe_i18n('ADMIN_STRIPE_REJECTED_LOWER'), 'sortable' => false],
  ],
  'rows' => $thirtyDayRows,
  'meta' => [
    'layout' => 'auto',
    'noChrome' => false,
  ],
]);

require_once HTML . '/header.php';

$cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce = is_scalar($cspNonceRaw) ? (string) $cspNonceRaw : '';
echo '<link rel="stylesheet" href="' . htmlspecialchars(Render::cssURL('admin/metrics'), ENT_QUOTES, 'UTF-8') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;
?>
<section class="metrics-dashboard" aria-label="<?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_BILLING_DASHBOARD_ARIA'), ENT_QUOTES, 'UTF-8') ?>">
  <div class="metric-card">
    <h1><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_DASHBOARD'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_OVERVIEW_PREFIX'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($summaryDate, ENT_QUOTES, 'UTF-8') ?>.</p>
    <div class="metric-row">
      <span class="metric-label"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_PROCESSED'), ENT_QUOTES, 'UTF-8') ?></span>
      <span class="metric-value success"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'processed', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_DUPLICATES'), ENT_QUOTES, 'UTF-8') ?></span>
      <span class="metric-value"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'duplicate', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_VERIFICATION_FAILED'), ENT_QUOTES, 'UTF-8') ?></span>
      <span class="metric-value danger"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'verification_failed', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_REJECTED_EVENTS'), ENT_QUOTES, 'UTF-8') ?></span>
      <span class="metric-value danger"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'event_rejected', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_EMPTY_PAYLOADS'), ENT_QUOTES, 'UTF-8') ?></span>
      <span class="metric-value danger"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'payload_empty', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_MISSING_SIGNATURES'), ENT_QUOTES, 'UTF-8') ?></span>
      <span class="metric-value danger"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'signature_missing', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_SECRET_MISSING'), ENT_QUOTES, 'UTF-8') ?></span>
      <span class="metric-value danger"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'secret_key_missing', 0)) ?></span>
    </div>
    <div class="metric-row">
      <span class="metric-label"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_WEBHOOK_SECRET_MISSING'), ENT_QUOTES, 'UTF-8') ?></span>
      <span class="metric-value danger"><?= formatStripeNumber(getIntValue($billingWebhookOutcomes, 'webhook_secret_missing', 0)) ?></span>
    </div>
  </div>

  <div class="metric-card">
    <h2><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_WEBHOOK_EVENT_TYPES'), ENT_QUOTES, 'UTF-8') ?></h2>
    <?php echo $eventTypesGrid->table(); ?>
  </div>

  <div class="metric-card">
    <h2><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_7_DAY_TREND'), ENT_QUOTES, 'UTF-8') ?></h2>
    <?php echo $sevenDayGrid->table(); ?>
  </div>

  <div class="metric-card">
    <h2><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_30_DAY_TREND'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p>
      <?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_TOTAL_PROCESSED'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= formatStripeNumber(getIntValue($billingWebhookRollingThirtyTotals, 'processed', 0)) ?></strong> /
      <?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_DUPLICATE_LOWER'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= formatStripeNumber(getIntValue($billingWebhookRollingThirtyTotals, 'duplicate', 0)) ?></strong> /
      <?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_VERIFICATION_FAILED_LOWER'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= formatStripeNumber(getIntValue($billingWebhookRollingThirtyTotals, 'verification_failed', 0)) ?></strong> /
      <?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_REJECTED_LOWER'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= formatStripeNumber(getIntValue($billingWebhookRollingThirtyTotals, 'event_rejected', 0)) ?></strong>
    </p>
    <?php echo $thirtyDayGrid->table(); ?>
  </div>

  <div class="metric-card">
    <h2><?= htmlspecialchars(admin_stripe_i18n('ACTIONS'), ENT_QUOTES, 'UTF-8') ?></h2>
    <div class="metrics-grid">
      <div class="metric-row">
        <span class="metric-label"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_ADMIN_OVERVIEW'), ENT_QUOTES, 'UTF-8') ?></span>
        <span class="metric-value"><a href="/admin/" class="btn btn_secondary"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_BACK_TO_ADMIN'), ENT_QUOTES, 'UTF-8') ?></a></span>
      </div>
      <div class="metric-row">
        <span class="metric-label"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_PLATFORM_METRICS'), ENT_QUOTES, 'UTF-8') ?></span>
        <span class="metric-value"><a href="/admin/metrics/" class="btn btn_secondary"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_OPEN_METRICS'), ENT_QUOTES, 'UTF-8') ?></a></span>
      </div>
      <div class="metric-row">
        <span class="metric-label"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_DASHBOARD'), ENT_QUOTES, 'UTF-8') ?></span>
        <span class="metric-value"><a href="https://dashboard.stripe.com/" target="_blank" rel="noopener noreferrer" class="btn btn_secondary"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_OPEN_STRIPE_EXTERNAL'), ENT_QUOTES, 'UTF-8') ?></a></span>
      </div>
      <div class="metric-row">
        <span class="metric-label"><?= htmlspecialchars(admin_stripe_i18n('ADMIN_STRIPE_RAW_TELEMETRY_API'), ENT_QUOTES, 'UTF-8') ?></span>
        <span class="metric-value"><a href="/api/v1/billing/telemetry" class="btn btn_primary"><?= htmlspecialchars(admin_stripe_i18n('VIEW_JSON'), ENT_QUOTES, 'UTF-8') ?></a></span>
      </div>
    </div>
  </div>
</section>
<?php
require_once HTML . '/footer.php';
