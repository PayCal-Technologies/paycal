<?php declare(strict_types=1);

namespace PayCal\Domain;

?>

  <section class="panel" id="panel-danger-zone" aria-labelledby="panel-danger-zone-heading" title="<?php echo settings_index_i18n('PROFILE_DANGER_PANEL_HELP'); ?>" data-hover-help="<?php echo settings_index_i18n('PROFILE_DANGER_PANEL_HELP'); ?>">
    <div class="businesses_section_header">
      <div>
        <h2 id="panel-danger-zone-heading"><?php echo settings_index_i18n('PROFILE_DANGER_TITLE'); ?></h2>
        <p class="help_text danger_zone_intro"><?php echo settings_index_i18n('PROFILE_DANGER_INTRO'); ?></p>
      </div>
    </div>

    <div class="danger_zone_actions" aria-label="<?php echo settings_index_i18n('PROFILE_DANGER_ACTIONS_ARIA'); ?>">
      <div class="danger_zone_row">
        <div class="danger_zone_text">
          <p class="help_text"><?php echo settings_index_i18n('PROFILE_DANGER_DELETE_DATA_TEXT'); ?></p>
        </div>
        <div class="danger_zone_controls">
          <div class="danger_confirm_pill" id="danger_delete_data_pill">
            <span><?php echo settings_index_i18n('PROFILE_DANGER_TYPE_PREFIX'); ?> <code><?php echo settings_index_i18n('PROFILE_DANGER_DELETE_DATA_PHRASE'); ?></code></span>
            <input type="text" id="danger_delete_data_phrase" autocomplete="off" spellcheck="false" autocapitalize="characters" maxlength="32" aria-label="<?php echo settings_index_i18n('PROFILE_DANGER_DELETE_DATA_ARIA'); ?>">
            <button type="button" class="btn btn_delete" id="danger_delete_data_confirm" disabled><?php echo settings_index_i18n('PROFILE_CONFIRM_BUTTON'); ?></button>
          </div>
        </div>
      </div>

      <div class="danger_zone_row">
        <div class="danger_zone_text">
          <p class="help_text"><?php echo settings_index_i18n('PROFILE_DANGER_DELETE_ACCOUNT_TEXT'); ?></p>
        </div>
        <div class="danger_zone_controls">
          <div class="danger_confirm_pill" id="danger_delete_account_pill">
            <span><?php echo settings_index_i18n('PROFILE_DANGER_TYPE_PREFIX'); ?> <code><?php echo settings_index_i18n('PROFILE_DANGER_DELETE_ACCOUNT_PHRASE'); ?></code></span>
            <form id="danger_delete_account_form" method="POST" action="<?php echo Environment::appURL('api/v1/account/delete/'); ?>">
              <input type="text" id="danger_delete_account_phrase" name="confirm_phrase" autocomplete="off" spellcheck="false" autocapitalize="characters" maxlength="32" pattern="DELETE MY ACCOUNT" aria-label="<?php echo settings_index_i18n('PROFILE_DANGER_DELETE_ACCOUNT_ARIA'); ?>">
              <button type="submit" class="btn btn_delete" id="danger_delete_account_confirm" disabled><?php echo settings_index_i18n('PROFILE_CONFIRM_BUTTON'); ?></button>
            </form>
          </div>
        </div>
      </div>

      <div id="danger_zone_status" class="status_text" role="status" aria-live="polite"></div>
    </div>
  </section>
