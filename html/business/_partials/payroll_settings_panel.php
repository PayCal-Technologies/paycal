<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <section class="panel businesses_workspace_settings_panel businesses_payroll_panel" aria-labelledby="business_payroll_heading">
    <h2 id="business_payroll_heading" class="visually_hidden"><?php echo businesses_index_i18n('BUSINESS_PAYROLL_TITLE'); ?></h2>

    <div class="business_payroll_panels">
      <section class="business_payroll_section business_payroll_default_wage" title="<?php echo businesses_index_i18n('BUSINESS_PAYROLL_DEFAULT_WAGE_HELP'); ?>" data-hover-help="<?php echo businesses_index_i18n('BUSINESS_PAYROLL_DEFAULT_WAGE_HELP'); ?>">
        <div class="business_payroll_section_header">
          <h3><?php echo businesses_index_i18n('BUSINESSES_DEFAULT_WAGE'); ?></h3>
        </div>
        <p class="help_text"><?php echo businesses_index_i18n('BUSINESS_PAYROLL_DEFAULT_WAGE_HELP'); ?></p>
        <div class="businesses_field_grid">
          <label for="businesses_editor_default_wage"><?php echo businesses_index_i18n('BUSINESSES_DEFAULT_WAGE'); ?></label>
          <input id="businesses_editor_default_wage" type="text" maxlength="32" placeholder="<?php echo businesses_index_i18n('BUSINESSES_DEFAULT_WAGE_PLACEHOLDER'); ?>">
        </div>
      </section>

<?php require __DIR__ . '/editor_pay_period_panel.php'; ?>
    </div>

    <div class="businesses_actions_row">
      <button id="businesses_payroll_save" type="button" class="btn btn_primary"><?php echo businesses_index_i18n('BUSINESS_PAYROLL_SAVE'); ?></button>
    </div>
    <p id="businesses_payroll_status" class="help_text" role="status" aria-live="polite" aria-atomic="true"></p>
  </section>
