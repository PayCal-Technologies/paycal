<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel" id="panel-data-portability">
  <form id="account_data_portability_form" method="POST" action="<?php echo Environment::appURL('api/v1/account/data/export/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_SECTION_DATA_PORTABILITY_EXPORT_IMPORT'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent"><?php echo settings_index_i18n('SETTINGS_SECTION_DATA_PORTABILITY'); ?></h2>
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
          <div><strong>Reference:</strong> <span id="data_export_reference">-</span></div>
          <div><strong>Checksum (SHA-256):</strong> <span id="data_export_checksum">-</span></div>
          <div><strong>Counts:</strong> <span id="data_export_counts">-</span></div>
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
          <div><strong>Import ID:</strong> <span id="data_import_id">-</span></div>
          <div><strong>Prepared Checksum:</strong> <span id="data_import_checksum">-</span></div>
          <div><strong>Prepared Counts:</strong> <span id="data_import_counts">-</span></div>
          <div><strong>Session TTL:</strong> <span id="data_import_expires">-</span></div>
          <div><strong>Commit Result:</strong> <span id="data_import_result_counts">-</span></div>
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

<section class="panel settings_card_group settings_card_group--danger" id="panel-data-danger">
  <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_DATA_DANGER_TITLE'); ?></h2>
  <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DATA_DANGER_DESC'); ?></p>
  <div class="flex centered w100">
    <button type="button" id="settings_data_delete_account_btn" class="btn btn_delete" aria-describedby="settings_data_delete_help"><?php echo settings_index_i18n('DELETE_ACCOUNT'); ?></button>
  </div>
  <p id="settings_data_delete_help" class="help_text"><?php echo settings_index_i18n('SETTINGS_DELETE_ACCOUNT_WARNING_TEXT'); ?></p>
</section>
