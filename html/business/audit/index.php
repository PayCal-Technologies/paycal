<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Business Audit — access policy, roles & permissions, audit timeline, SOC 2.
 *
 * Coordinator-gated business subpage (owner/coordinator + admins only).
 */
$currentPage = 'PAGE_BUSINESS_AUDIT';

require_once dirname(__DIR__) . '/_layout.php';
BusinessNav::requireCoordinatorAccess();

$auditActiveMembers = 0;
$auditPendingMembers = 0;
$auditRevokedRelationships = 0;
$auditCoordinators = 0;
$auditPendingRequests = 0;
$auditSummaryAvailable = false;

if ($workspaceBusinessId !== '') {
  $auditService = new BusinessDiscoveryService();

  $contextMetricsResult = $auditService->loadBusinessContextHeaderMetrics($userUUID, $workspaceBusinessId);
  if ((bool) ($contextMetricsResult['success'] ?? false)) {
    $auditSummaryAvailable = true;
    $snapshot = is_array($contextMetricsResult['data']['snapshot'] ?? null)
      ? $contextMetricsResult['data']['snapshot']
      : [];
    $relationships = is_array($snapshot['connections'] ?? null)
      ? $snapshot['connections']
      : [];

    foreach ($relationships as $relationship) {
      if (!is_array($relationship)) {
        continue;
      }

      $status = strtolower(trim((string) ($relationship['status'] ?? '')));
      $role = strtolower(trim((string) ($relationship['role'] ?? '')));

      if ($status === 'active') {
        $auditActiveMembers++;
        if (in_array($role, ['owner', 'coordinator'], true)) {
          $auditCoordinators++;
        }
      } elseif ($status === 'pending') {
        $auditPendingMembers++;
      } elseif ($status === 'revoked') {
        $auditRevokedRelationships++;
      }
    }
  }

  $auditPendingRequests = BusinessNav::workspacePendingAccessRequestCount($userUUID, $workspaceBusinessId);
}

$auditMetricValue = static function (int $count): string {
  return $count > 0 ? (string) $count : businesses_index_i18n('BUSINESSES_EXEC_NONE');
};
?>

<div id="business-workspace" class="business_workspace business_audit" data-business-subpage="audit"<?php echo $workspaceBusinessIdAttr; ?>>

  <h1 class="visually_hidden"><?php echo Strings::i18n('BUSINESS_NAV_AUDIT'); ?></h1>

  <section class="panel business_audit_summary" aria-labelledby="business_audit_summary_heading">
    <div class="businesses_section_header">
      <h2 id="business_audit_summary_heading"><?php echo businesses_index_i18n_html('BUSINESS_GOVERNANCE_SUMMARY_TITLE'); ?></h2>
      <a class="btn btn_secondary" href="/business/members/"><?php echo businesses_index_i18n_html('BUSINESS_DASHBOARD_MANAGE_MEMBERS'); ?></a>
    </div>
    <p class="help_text"><?php echo businesses_index_i18n_html('BUSINESS_GOVERNANCE_SUMMARY_HELP'); ?></p>
<?php if ($auditSummaryAvailable) { ?>
    <dl class="businesses_exec_summary_metrics business_audit_metrics">
      <div class="businesses_exec_metric">
        <dt><?php echo businesses_index_i18n_html('BUSINESS_GOVERNANCE_ACTIVE_MEMBERS'); ?></dt>
        <dd id="business_audit_active_members"><?php echo htmlspecialchars($auditMetricValue($auditActiveMembers), ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
      <div class="businesses_exec_metric">
        <dt><?php echo businesses_index_i18n_html('BUSINESS_GOVERNANCE_COORDINATORS'); ?></dt>
        <dd id="business_audit_coordinators"><?php echo htmlspecialchars($auditMetricValue($auditCoordinators), ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
      <div class="businesses_exec_metric">
        <dt><?php echo businesses_index_i18n_html('BUSINESS_GOVERNANCE_PENDING_MEMBERS'); ?></dt>
        <dd id="business_audit_pending_members"><?php echo htmlspecialchars($auditMetricValue($auditPendingMembers), ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
      <div class="businesses_exec_metric">
        <dt><?php echo businesses_index_i18n_html('BUSINESS_GOVERNANCE_ACCESS_REQUESTS_TITLE'); ?></dt>
        <dd id="business_audit_pending_requests"><?php echo htmlspecialchars($auditMetricValue($auditPendingRequests), ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
      <div class="businesses_exec_metric">
        <dt><?php echo businesses_index_i18n_html('BUSINESS_GOVERNANCE_REVOKED_CONNECTIONS'); ?></dt>
        <dd id="business_audit_revoked"><?php echo htmlspecialchars($auditMetricValue($auditRevokedRelationships), ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
    </dl>
<?php } else { ?>
    <p class="help_text business_audit_summary_empty"><?php echo businesses_index_i18n_html('BUSINESSES_SELECT_FIRST'); ?></p>
<?php } ?>
  </section>

<?php require __DIR__ . '/../_partials/governance_panel.php'; ?>

  <div class="business_audit_panels">
<?php require __DIR__ . '/../_partials/editor_audit_panels.php'; ?>
  </div>

  <section class="panel business_audit_soc_stub" aria-labelledby="business_audit_soc_heading">
    <div class="businesses_section_header">
      <h2 id="business_audit_soc_heading"><?php echo Strings::i18n('BUSINESS_COMPLIANCE_SOC_HEADING'); ?></h2>
    </div>
    <p class="help_text"><?php echo Strings::i18n('BUSINESS_COMPLIANCE_SOC_HELP'); ?></p>
    <p class="business_audit_soc_link">
      <a href="/soc2/"><?php echo Strings::i18n('BUSINESS_COMPLIANCE_SOC_LINK'); ?></a>
    </p>
  </section>

</div>

<?php require __DIR__ . '/../_partials/dialogs.php'; ?>

<?php
require __DIR__ . '/../_partials/footer_shared.php';
require_once \PayCal\Domain\Config\Environment::appHome() . 'html/footer.php';
