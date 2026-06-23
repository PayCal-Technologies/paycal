<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * SSR metric cards for the business dashboard (8 fast counters).
 *
 * @var array{
 *   members: int,
 *   sites: int,
 *   pending_invites: int|null,
 *   pending_requests: int|null,
 *   work_entries_today: int,
 *   work_entries_week: int,
 *   last_activity_at: string,
 *   created_at: string
 * } $dashboardMetrics
 */

$metricCards = [
  [
    'key' => 'members',
    'label_key' => 'BUSINESS_DASHBOARD_METRIC_MEMBERS',
    'href' => '/business/members/',
    'value' => BusinessDashboardMetrics::formatIntegerCount($dashboardMetrics['members']),
    'count_up' => true,
  ],
  [
    'key' => 'sites',
    'label_key' => 'BUSINESS_DASHBOARD_METRIC_SITES',
    'href' => '/business/sites/',
    'value' => BusinessDashboardMetrics::formatIntegerCount($dashboardMetrics['sites']),
    'count_up' => true,
  ],
  [
    'key' => 'pending_invites',
    'label_key' => 'BUSINESS_DASHBOARD_METRIC_PENDING_INVITES',
    'href' => '/business/members/',
    'value' => $dashboardMetrics['pending_invites'] === null
      ? businesses_index_i18n('BUSINESSES_EXEC_NONE')
      : BusinessDashboardMetrics::formatOptionalCount($dashboardMetrics['pending_invites']),
    'is_none' => $dashboardMetrics['pending_invites'] === null
      || (int) $dashboardMetrics['pending_invites'] === 0,
    'count_up' => $dashboardMetrics['pending_invites'] !== null,
  ],
  [
    'key' => 'pending_requests',
    'label_key' => 'BUSINESS_DASHBOARD_METRIC_PENDING_REQUESTS',
    'href' => '/business/members/',
    'value' => $dashboardMetrics['pending_requests'] === null
      ? businesses_index_i18n('BUSINESSES_EXEC_NONE')
      : BusinessDashboardMetrics::formatOptionalCount($dashboardMetrics['pending_requests']),
    'is_none' => $dashboardMetrics['pending_requests'] === null
      || (int) $dashboardMetrics['pending_requests'] === 0,
    'count_up' => $dashboardMetrics['pending_requests'] !== null,
  ],
  [
    'key' => 'work_today',
    'label_key' => 'BUSINESS_DASHBOARD_METRIC_WORK_TODAY',
    'href' => '/calendar/',
    'value' => BusinessDashboardMetrics::formatIntegerCount($dashboardMetrics['work_entries_today']),
    'count_up' => true,
  ],
  [
    'key' => 'work_week',
    'label_key' => 'BUSINESS_DASHBOARD_METRIC_WORK_WEEK',
    'href' => '/business/reports/',
    'value' => BusinessDashboardMetrics::formatIntegerCount($dashboardMetrics['work_entries_week']),
    'count_up' => true,
  ],
  [
    'key' => 'last_activity',
    'label_key' => 'BUSINESS_DASHBOARD_METRIC_LAST_ACTIVITY',
    'href' => '/business/audit/',
    'value' => BusinessDashboardMetrics::formatTimestampLabel($dashboardMetrics['last_activity_at']),
  ],
  [
    'key' => 'created',
    'label_key' => 'BUSINESS_DASHBOARD_METRIC_CREATED',
    'href' => '/business/details/',
    'value' => BusinessDashboardMetrics::formatTimestampLabel($dashboardMetrics['created_at']),
  ],
];

$formatMetricDisplay = static function (array $card): string {
  $value = trim((string) ($card['value'] ?? ''));
  if ($value === '') {
    return businesses_index_i18n('BUSINESSES_EXEC_NONE');
  }

  return $value;
};

?>

<section class="panel business_dashboard_metrics" aria-labelledby="business_dashboard_metrics_heading">
  <h2 id="business_dashboard_metrics_heading" class="visually_hidden"><?php echo businesses_index_i18n('BUSINESS_DASHBOARD_METRICS_ARIA'); ?></h2>
  <div class="business_dashboard_metrics_grid">
<?php foreach ($metricCards as $card) {
  $displayValue = $formatMetricDisplay($card);
  $cardClasses = ['business_dashboard_metric_card'];
  if (!empty($card['is_none']) || $displayValue === businesses_index_i18n('BUSINESSES_EXEC_NONE')) {
    $cardClasses[] = 'business_dashboard_metric_card--muted';
  }
  $cardClassAttr = htmlspecialchars(implode(' ', $cardClasses), ENT_QUOTES, 'UTF-8');
  $label = businesses_index_i18n((string) $card['label_key']);
  $href = (string) ($card['href'] ?? '');
  $metricKey = htmlspecialchars((string) ($card['key'] ?? ''), ENT_QUOTES, 'UTF-8');
  $countUpAttr = !empty($card['count_up']) ? ' data-count-up-metric="1"' : '';
  ?>
    <a class="<?php echo $cardClassAttr; ?>" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>" data-dashboard-metric="<?php echo $metricKey; ?>">
      <span class="business_dashboard_metric_label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
      <span class="business_dashboard_metric_value" id="business_dashboard_metric_<?php echo $metricKey; ?>"<?php echo $countUpAttr; ?>><?php echo htmlspecialchars($displayValue, ENT_QUOTES, 'UTF-8'); ?></span>
    </a>
<?php } ?>
  </div>
</section>
