<?php declare(strict_types=1);

namespace PayCal\Domain;

$settingsDataConsentRowsById = [];
$settingsDataConsentByBusiness = [];
$settingsDataConsentRequiresSecureWrap = (bool) Config\SystemConfig::get('business_shared_encryption_enabled');
foreach (Database::smembers(Keys::businessConsentsByUser((string) $user->user_uuid)) as $consentIdRaw) {
  $consentId = trim((string) $consentIdRaw);
  if ($consentId === '') {
    continue;
  }

  $consent = Database::hgetall(Keys::businessConsent($consentId));
  if ($consent === [] || (string) ($consent['user_uuid'] ?? '') !== (string) $user->user_uuid) {
    continue;
  }

  $businessId = trim((string) ($consent['business_id'] ?? ''));
  if ($businessId === '') {
    continue;
  }

  $status = strtolower(trim((string) ($consent['status'] ?? '')));
  $settingsDataConsentByBusiness[$businessId][$status][] = $consent;
}

$settingsDataConsentHasReadyAccess = static function (string $businessId, string $userUUID, array $activeConsentIds) use ($settingsDataConsentRequiresSecureWrap): bool {
  if (!$settingsDataConsentRequiresSecureWrap) {
    return true;
  }

  $activeConsentLookup = array_fill_keys(array_map(static fn (mixed $value): string => (string) $value, $activeConsentIds), true);
  if ($activeConsentLookup === []) {
    return false;
  }

  $pattern = Keys::BUSINESS_DEK_WRAP . ':' . $businessId . ':*:*:' . $userUUID . ':*';
  foreach (Database::scanKeys($pattern) as $wrapKey) {
    $wrap = Database::hgetall((string) $wrapKey);
    if (
      $wrap !== []
      && (string) ($wrap['user_uuid'] ?? '') === $userUUID
      && (string) ($wrap['business_id'] ?? '') === $businessId
      && (string) ($wrap['status'] ?? '') === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE
      && isset($activeConsentLookup[(string) ($wrap['consent_id'] ?? '')])
    ) {
      return true;
    }
  }

  return false;
};

$settingsDataConsentRelationshipKeys = Database::scanKeys(Keys::BUSINESS_CONNECTION . ':*:' . (string) $user->user_uuid);
foreach ($settingsDataConsentRelationshipKeys as $relationshipKey) {
  $relationshipKey = (string) $relationshipKey;
  $prefix = Keys::BUSINESS_CONNECTION . ':';
  $suffix = ':' . (string) $user->user_uuid;
  if (!str_starts_with($relationshipKey, $prefix) || !str_ends_with($relationshipKey, $suffix)) {
    continue;
  }

  $businessId = substr($relationshipKey, strlen($prefix), -strlen($suffix));
  if ($businessId === '') {
    continue;
  }

  $relationship = Database::hgetall($relationshipKey);
  if ($relationship === []) {
    continue;
  }

  $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
  if ($business === []) {
    continue;
  }

  $businessType = strtolower(trim((string) ($business['business_type'] ?? 'shared')));
  if ($businessType === 'personal' || $businessType === 'self') {
    continue;
  }

  $consentsForBusiness = $settingsDataConsentByBusiness[$businessId] ?? [];
  $activeConsentIds = array_values(array_filter(array_map(
    static fn (array $consent): string => trim((string) ($consent['consent_id'] ?? '')),
    $consentsForBusiness[BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE] ?? []
  ), static fn (string $consentId): bool => $consentId !== ''));
  $hasActiveConsent = $activeConsentIds !== [];
  $hasRevokedConsent = ($consentsForBusiness[BusinessDiscoveryService::MEMBERSHIP_STATE_REVOKED] ?? []) !== [];
  $secureAccessReady = $settingsDataConsentHasReadyAccess($businessId, (string) $user->user_uuid, $activeConsentIds);

  $relationshipStatus = strtolower(trim((string) ($relationship['status'] ?? '')));
  $bucket = match ($relationshipStatus) {
    BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE => $hasActiveConsent ? ($secureAccessReady ? 'active' : 'setup') : ($hasRevokedConsent ? 'revoked' : 'setup'),
    BusinessDiscoveryService::MEMBERSHIP_STATE_PENDING,
    BusinessDiscoveryService::MEMBERSHIP_STATE_CONSENTED => 'pending',
    default => 'revoked',
  };
  $setupNeedsSecureRefresh = $relationshipStatus === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE && $hasActiveConsent && !$secureAccessReady;

  $settingsDataConsentRowsById[$businessId] = [
    'business_id' => $businessId,
    'name' => trim((string) ($business['name'] ?? $businessId)),
    'status' => $relationshipStatus,
    'bucket' => $bucket,
    'can_grant' => $relationshipStatus === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE && !$hasActiveConsent,
    'can_revoke' => $relationshipStatus === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE && $hasActiveConsent,
    'has_active_consent' => $hasActiveConsent,
    'secure_access_ready' => $secureAccessReady,
    'desc_key' => $setupNeedsSecureRefresh ? 'SETTINGS_DATA_CONSENT_SECURE_ACCESS_NOT_READY_DESC' : '',
    'action_key' => $setupNeedsSecureRefresh ? 'SETTINGS_DATA_CONSENT_REFRESH_ACCESS' : '',
    'control_note_key' => $setupNeedsSecureRefresh ? 'SETTINGS_DATA_CONSENT_REFRESH_CONTROL_NOTE' : '',
    'updated_at' => trim((string) ($relationship['updated_at'] ?? $relationship['accepted_at'] ?? $relationship['created_at'] ?? '')),
  ];
}

uasort($settingsDataConsentRowsById, static fn (array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name']));
$settingsDataConsentBuckets = [
  'active' => [],
  'pending' => [],
  'setup' => [],
  'revoked' => [],
];
foreach ($settingsDataConsentRowsById as $row) {
  $bucket = (string) ($row['bucket'] ?? 'revoked');
  $settingsDataConsentBuckets[$bucket][] = $row;
}

$renderConsentRows = static function (array $rows, string $descKey, string $pillKey, string $stateClass, string $actionKey, string $consentAction = ''): void {
  echo '<ul class="settings_data_consent_list">';
  foreach ($rows as $row) {
    $businessId = (string) ($row['business_id'] ?? '');
    $href = '/business/members/?business_id=' . rawurlencode($businessId);
    $businessName = (string) ($row['name'] ?? $businessId);
    $resolvedDescKey = (string) ($row['desc_key'] ?? '') !== '' ? (string) $row['desc_key'] : $descKey;
    $stateKey = match ($stateClass) {
      'is-active' => 'active',
      'is-waiting' => 'waiting',
      'is-setup' => 'setup',
      default => 'revoked',
    };

    echo '<li class="settings_data_consent_item ' . htmlspecialchars($stateClass, ENT_QUOTES, 'UTF-8') . '" data-business-consent-card data-business-consent-state="' . htmlspecialchars($stateKey, ENT_QUOTES, 'UTF-8') . '">';
    echo '<div class="settings_data_consent_body">';
    echo '<div class="settings_data_consent_card_header">';
    echo '<strong>' . htmlspecialchars($businessName, ENT_QUOTES, 'UTF-8') . '</strong>';
    echo '</div>';
    echo '<p data-business-consent-desc>' . htmlspecialchars(settings_index_i18n($resolvedDescKey), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</div>';

    $canUseConsentAction = $consentAction === 'grant'
      ? ((bool) ($row['can_grant'] ?? false) || ((bool) ($row['has_active_consent'] ?? false) && !(bool) ($row['secure_access_ready'] ?? false)))
      : ($consentAction === 'revoke' && (bool) ($row['can_revoke'] ?? false));

    echo '<div class="settings_data_consent_controls">';
    echo '<span class="settings_data_consent_pill ' . htmlspecialchars($stateClass, ENT_QUOTES, 'UTF-8') . '" data-business-consent-pill><span class="settings_data_consent_status_text">' . htmlspecialchars(settings_index_i18n($pillKey), ENT_QUOTES, 'UTF-8') . '</span></span>';
    if ($stateClass === 'is-active') {
      if ((bool) ($row['can_revoke'] ?? false)) {
        echo '<button type="button" class="btn btn_secondary settings_data_consent_action is-revoke" data-business-consent-action="revoke" data-business-consent-mode="revoke" data-business-id="' . htmlspecialchars($businessId, ENT_QUOTES, 'UTF-8') . '" data-business-name="' . htmlspecialchars($businessName, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(settings_index_i18n('SETTINGS_DATA_CONSENT_REVOKE'), ENT_QUOTES, 'UTF-8') . '</button>';
      }
    } elseif ($consentAction !== '' && $canUseConsentAction) {
      $resolvedActionKey = (string) ($row['action_key'] ?? '') !== '' ? (string) $row['action_key'] : $actionKey;
      $consentMode = $resolvedActionKey === 'SETTINGS_DATA_CONSENT_REFRESH_ACCESS' ? 'refresh' : $consentAction;
      $buttonClass = $consentAction === 'grant' ? 'btn btn_primary' : 'btn btn_secondary';
      echo '<button type="button" class="' . htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8') . ' settings_data_consent_action is-' . htmlspecialchars($consentAction, ENT_QUOTES, 'UTF-8') . '" data-business-consent-action="' . htmlspecialchars($consentAction, ENT_QUOTES, 'UTF-8') . '" data-business-consent-mode="' . htmlspecialchars($consentMode, ENT_QUOTES, 'UTF-8') . '" data-business-id="' . htmlspecialchars($businessId, ENT_QUOTES, 'UTF-8') . '" data-business-name="' . htmlspecialchars($businessName, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(settings_index_i18n($resolvedActionKey), ENT_QUOTES, 'UTF-8') . '</button>';
      if ($consentMode === 'refresh') {
        echo '<button type="button" class="btn btn_secondary settings_data_consent_action is-revoke" data-business-consent-action="revoke" data-business-consent-mode="revoke" data-business-id="' . htmlspecialchars($businessId, ENT_QUOTES, 'UTF-8') . '" data-business-name="' . htmlspecialchars($businessName, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(settings_index_i18n('SETTINGS_DATA_CONSENT_REVOKE'), ENT_QUOTES, 'UTF-8') . '</button>';
      }
    } else {
      $fallbackActionKey = $consentAction === 'grant' ? 'SETTINGS_DATA_CONSENT_VIEW_HISTORY' : $actionKey;
      $fallbackHref = $stateClass === 'is-waiting'
        ? '/connections/#businesses_current_panel'
        : $href;
      echo '<a class="btn btn_secondary" href="' . htmlspecialchars($fallbackHref, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(settings_index_i18n($fallbackActionKey), ENT_QUOTES, 'UTF-8') . '</a>';
    }
    echo '</div>';
    echo '</li>';
  }
  echo '</ul>';
};
$settingsDataConsentHasVisibleRows = count($settingsDataConsentBuckets['active']) > 0
  || count($settingsDataConsentBuckets['pending']) > 0
  || count($settingsDataConsentBuckets['setup']) > 0
  || count($settingsDataConsentBuckets['revoked']) > 0;
?>
<section class="panel settings_card_group settings_data_consent_panel" id="panel-data-consent" aria-labelledby="settings_data_consent_heading">
  <h2 id="settings_data_consent_heading" class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_TITLE'); ?></h2>
  <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_INTRO'); ?></p>
  <div id="settings_data_consent_status" class="status_message" role="status" aria-live="polite" aria-atomic="true"></div>

  <div class="settings_data_consent_counts" aria-label="<?php echo htmlspecialchars(settings_index_i18n('SETTINGS_DATA_CONSENT_OVERVIEW'), ENT_QUOTES, 'UTF-8'); ?>">
    <span class="settings_data_consent_count is-active" data-business-consent-count="active"><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_ACTIVE'); ?> <strong><?php echo count($settingsDataConsentBuckets['active']); ?></strong></span>
    <span class="settings_data_consent_count is-waiting" data-business-consent-count="waiting"<?php if (count($settingsDataConsentBuckets['pending']) === 0) { echo ' hidden'; } ?>><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_WAITING'); ?> <strong><?php echo count($settingsDataConsentBuckets['pending']); ?></strong></span>
    <span class="settings_data_consent_count is-setup" data-business-consent-count="setup"<?php if (count($settingsDataConsentBuckets['setup']) === 0) { echo ' hidden'; } ?>><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_NEEDS_SETUP'); ?> <strong><?php echo count($settingsDataConsentBuckets['setup']); ?></strong></span>
    <span class="settings_data_consent_count is-revoked" data-business-consent-count="revoked"<?php if (count($settingsDataConsentBuckets['revoked']) === 0) { echo ' hidden'; } ?>><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_REVOKED'); ?> <strong><?php echo count($settingsDataConsentBuckets['revoked']); ?></strong></span>
  </div>

  <div class="settings_data_consent_card_area">
    <div class="settings_data_consent_card_column">
<?php if (!$settingsDataConsentHasVisibleRows) { ?>
      <section class="settings_data_consent_empty_state" aria-live="polite">
        <h3><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_EMPTY'); ?></h3>
        <p><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_EMPTY_HELP'); ?></p>
      </section>
<?php } ?>

<?php if ($settingsDataConsentBuckets['active'] !== []) { ?>
      <section class="settings_data_consent_section" aria-labelledby="settings_data_consent_active_heading">
        <h3 id="settings_data_consent_active_heading" class="visually_hidden"><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_ACTIVE_TITLE'); ?></h3>
        <?php $renderConsentRows($settingsDataConsentBuckets['active'], 'SETTINGS_DATA_CONSENT_ACTIVE_DESC', 'SETTINGS_DATA_CONSENT_ACTIVE', 'is-active', 'SETTINGS_DATA_CONSENT_REVOKE', 'revoke'); ?>
      </section>
<?php } ?>

<?php if ($settingsDataConsentBuckets['pending'] !== []) { ?>
      <section class="settings_data_consent_section" aria-labelledby="settings_data_consent_pending_heading">
        <h3 id="settings_data_consent_pending_heading" class="visually_hidden"><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_PENDING_TITLE'); ?></h3>
        <?php $renderConsentRows($settingsDataConsentBuckets['pending'], 'SETTINGS_DATA_CONSENT_WAITING_DESC', 'SETTINGS_DATA_CONSENT_WAITING', 'is-waiting', 'SETTINGS_DATA_CONSENT_REVIEW_REQUEST'); ?>
      </section>
<?php } ?>

<?php if ($settingsDataConsentBuckets['setup'] !== []) { ?>
      <section class="settings_data_consent_section" aria-labelledby="settings_data_consent_setup_heading">
        <h3 id="settings_data_consent_setup_heading" class="visually_hidden"><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_SETUP_TITLE'); ?></h3>
        <?php $renderConsentRows($settingsDataConsentBuckets['setup'], 'SETTINGS_DATA_CONSENT_NEEDS_SETUP_DESC', 'SETTINGS_DATA_CONSENT_NEEDS_SETUP', 'is-setup', 'SETTINGS_DATA_CONSENT_PROVIDE', 'grant'); ?>
      </section>
<?php } ?>
    </div>
  </div>

<?php if ($settingsDataConsentBuckets['revoked'] !== []) { ?>
  <details class="settings_data_consent_past">
    <summary>
      <span><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_REVOKED_TITLE'); ?></span>
      <small><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_PAST_DISCLOSURE'); ?></small>
      <strong><?php echo count($settingsDataConsentBuckets['revoked']); ?></strong>
    </summary>
    <section class="settings_data_consent_section" aria-labelledby="settings_data_consent_revoked_heading">
      <h3 id="settings_data_consent_revoked_heading" class="visually_hidden"><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_REVOKED_TITLE'); ?></h3>
      <?php $renderConsentRows($settingsDataConsentBuckets['revoked'], 'SETTINGS_DATA_CONSENT_REVOKED_DESC', 'SETTINGS_DATA_CONSENT_REVOKED', 'is-revoked', 'SETTINGS_DATA_CONSENT_PROVIDE', 'grant'); ?>
    </section>
  </details>
<?php } ?>
</section>

<section class="panel" id="panel-data-portability">
  <form id="account_data_portability_form" method="POST" action="<?php echo Environment::appURL('api/v1/account/data/export/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_SECTION_DATA_PORTABILITY_EXPORT_IMPORT'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_SECTION_DATA_PORTABILITY'); ?></h2>
    <p class="help_text">Export creates a portable account package (user profile settings, sites, and work entries). Import runs in two stages: prepare validates and stages data, commit applies changes.</p>
    <p class="data_portability_warning" role="note"><strong>Warning:</strong> Export generates plaintext JSON data, including work details. Treat export files as sensitive and store or transfer securely.</p>

    <div id="data_portability_status" class="status_message" role="status" aria-live="polite" aria-atomic="true"></div>

    <div class="data_portability_grid">
      <section class="data_portability_column" aria-labelledby="data_export_title">
        <h3 id="data_export_title">Stage A: Export</h3>
        <p class="help_text">1) Click Export. 2) Review counts/checksum. 3) Copy or download the payload.</p>
        <fieldset class="settings_export_sections_fieldset">
          <legend><?php echo settings_index_i18n('SETTINGS_DATA_EXPORT_SECTIONS_TITLE'); ?></legend>
          <div class="work_entry_tags">
            <input type="checkbox" id="export_section_user" name="export_section_user" value="1" checked>
            <label for="export_section_user"><?php echo settings_index_i18n('SETTINGS_DATA_EXPORT_SECTION_USER'); ?></label>
            <input type="checkbox" id="export_section_sites" name="export_section_sites" value="1" checked>
            <label for="export_section_sites"><?php echo settings_index_i18n('SETTINGS_DATA_EXPORT_SECTION_SITES'); ?></label>
            <input type="checkbox" id="export_section_work" name="export_section_work" value="1" checked>
            <label for="export_section_work"><?php echo settings_index_i18n('SETTINGS_DATA_EXPORT_SECTION_WORK'); ?></label>
          </div>
        </fieldset>
        <div class="flex f_baseline w100">
          <label class="w25"><?php echo settings_index_i18n('SETTINGS_EXPORT_ENCRYPT_PREFERENCE_LABEL'); ?></label>
          <div class="w75 work_entry_tags">
            <input type="checkbox" name="export_encrypt_preference" value="1" id="export_encrypt_preference"<?php if ((string) ($user->export_encrypt_preference ?? '0') === '1') { echo ' checked'; } ?>>
            <label for="export_encrypt_preference"><?php echo settings_index_i18n('SETTINGS_EXPORT_ENCRYPT_PREFERENCE_LABEL'); ?></label>
          </div>
        </div>
        <div class="data_portability_actions_row">
          <button id="data_export_run_btn" type="button" class="btn btn_primary">Export Account Data</button>
          <button id="data_export_copy_btn" type="button" class="btn btn_secondary" disabled aria-disabled="true">Copy Payload</button>
          <button id="data_export_download_btn" type="button" class="btn btn_secondary" disabled aria-disabled="true">Download JSON</button>
        </div>
        <div class="data_portability_meta">
          <div><strong>Reference</strong> <span id="data_export_reference">-</span></div>
          <div><strong>Checksum (SHA-256)</strong> <span id="data_export_checksum">-</span></div>
          <div><strong>Counts</strong> <span id="data_export_counts">-</span></div>
        </div>
        <label for="data_export_payload" class="item_label">Export Payload JSON</label>
        <textarea id="data_export_payload" class="data_portability_textarea" rows="12" readonly aria-describedby="data_portability_status" placeholder="Export payload will appear here after running export."></textarea>
      </section>

      <section class="data_portability_column" aria-labelledby="data_import_title">
        <h3 id="data_import_title">Stage B: Import</h3>
        <p class="help_text">1) Paste payload. 2) Prepare Import validates and stages. 3) Commit Import applies data to your account.</p>
        <label for="data_import_payload_json" class="item_label">Import Payload JSON</label>
        <textarea id="data_import_payload_json" class="data_portability_textarea" rows="12" aria-describedby="data_portability_status" placeholder="Paste exported payload JSON here."></textarea>
        <div class="data_portability_actions_row">
          <button id="data_import_prepare_btn" type="button" class="btn btn_secondary">Prepare Import</button>
          <button id="data_import_commit_btn" type="button" class="btn btn_primary" disabled aria-disabled="true">Commit Import</button>
        </div>
        <div class="data_portability_meta">
          <div><strong>Import ID</strong> <span id="data_import_id">-</span></div>
          <div><strong>Prepared Checksum</strong> <span id="data_import_checksum">-</span></div>
          <div><strong>Prepared Counts</strong> <span id="data_import_counts">-</span></div>
          <div><strong>Session TTL</strong> <span id="data_import_expires">-</span></div>
          <div><strong>Commit Result</strong> <span id="data_import_result_counts">-</span></div>
        </div>
      </section>
    </div>

    <section class="data_portability_log_section" aria-labelledby="data_portability_log_title">
      <h3 id="data_portability_log_title"><?php echo settings_index_i18n('SETTINGS_SECTION_DATA_PORTABILITY_ACTION_LOG'); ?></h3>
      <ol id="data_portability_action_log" class="data_portability_action_log" aria-live="polite" aria-atomic="false"></ol>
    </section>

    <section class="data_portability_history_section" aria-labelledby="settings_export_history_title">
      <h3 id="settings_export_history_title"><?php echo settings_index_i18n('SETTINGS_DATA_EXPORT_HISTORY_TITLE'); ?></h3>
      <ul id="settings_export_history_list" class="settings_export_history_list" aria-live="polite"></ul>
    </section>
  </form>
</section>

<section class="panel settings_card_group settings_data_export_reality" id="panel-data-export-reality">
  <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_EXPORT_REALITY_TITLE'); ?></h2>
  <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DATA_CONSENT_EXPORT_REALITY_TEXT'); ?></p>
</section>
