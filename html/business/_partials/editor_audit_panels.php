<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Compliance audit panels — mounted on Business Audit page.
 */
?>
          <section id="businesses_audit_control_test_panel" class="business_audit_section business_audit_control_test_section"<?php echo $showDevAdminPanels ? '' : ' hidden'; ?> title="<?php echo businesses_index_i18n('BUSINESSES_AUDIT_CONTROL_TEST_PANEL_HELP'); ?>" data-hover-help="<?php echo businesses_index_i18n('BUSINESSES_AUDIT_CONTROL_TEST_PANEL_HELP'); ?>">
            <div class="businesses_section_header">
              <h3><?php echo businesses_index_i18n('BUSINESSES_AUDIT_CONTROL_TEST_H3'); ?></h3>
            </div>
            <p class="help_text"><?php echo businesses_index_i18n('BUSINESSES_AUDIT_CONTROL_TEST_HELP'); ?></p>
            <div class="businesses_field_grid">
              <label for="businesses_audit_control_test_summary"><?php echo businesses_index_i18n('BUSINESSES_AUDIT_CONTROL_TEST_SUMMARY_LABEL'); ?></label>
              <input id="businesses_audit_control_test_summary" type="text" maxlength="240" value="<?php echo htmlspecialchars(businesses_index_i18n('BUSINESSES_AUDIT_CONTROL_TEST_SUMMARY_DEFAULT'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo businesses_index_i18n('BUSINESSES_AUDIT_CONTROL_TEST_SUMMARY_PLACEHOLDER'); ?>">
            </div>
            <div class="businesses_actions_row">
              <button id="businesses_audit_control_test_button" type="button" class="btn btn_delete"><?php echo businesses_index_i18n('BUSINESSES_AUDIT_CONTROL_TEST_GENERATE_BTN'); ?></button>
            </div>
            <p id="businesses_audit_control_test_status" class="help_text" role="status" aria-live="polite" aria-atomic="true"></p>
          </section>

          <section class="business_audit_section businesses_panel_audit_timeline" title="<?php echo businesses_index_i18n('BUSINESSES_AUDIT_TIMELINE_PANEL_HELP'); ?>" data-hover-help="<?php echo businesses_index_i18n('BUSINESSES_AUDIT_TIMELINE_PANEL_HELP'); ?>">
            <div class="businesses_section_header">
              <h3><?php echo businesses_index_i18n('BUSINESSES_AUDIT_TIMELINE'); ?></h3>
              <button id="businesses_audit_reload" type="button" class="btn btn_secondary"><?php echo businesses_index_i18n('REFRESH'); ?></button>
            </div>
            <p class="help_text"><?php echo businesses_index_i18n('BUSINESSES_AUDIT_TIMELINE_SETTINGS_HELP'); ?></p>
            <div class="visually_hidden">
              <p id="businesses_audit_sr_instructions"><?php echo businesses_index_i18n('BUSINESSES_AUDIT_SR'); ?></p>
              <p id="businesses_audit_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
            </div>
            <div id="businesses-audit-grid-host" class="datagrid_container businesses_audit_grid" role="region" aria-label="<?php echo businesses_index_i18n('BUSINESSES_AUDIT_ARIA'); ?>" aria-describedby="businesses_audit_sr_instructions businesses_audit_sr_status">
              <div class="datagrid_body"><div class="datagrid_empty"><?php echo businesses_index_i18n('BUSINESSES_NO_AUDIT'); ?></div></div>
            </div>
          </section>
