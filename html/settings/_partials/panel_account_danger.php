<?php declare(strict_types=1);

namespace PayCal\Domain;

?>

  <section class="panel settings_danger_zone" id="panel-danger-zone" aria-labelledby="panel-danger-zone-heading" title="<?php echo settings_index_i18n('PROFILE_DANGER_PANEL_HELP'); ?>" data-hover-help="<?php echo settings_index_i18n('PROFILE_DANGER_PANEL_HELP'); ?>">
    <header class="settings_danger_header">
      <h2 id="panel-danger-zone-heading" class="settings_card_title"><?php echo settings_index_i18n('PROFILE_DANGER_TITLE'); ?></h2>
      <p class="settings_danger_intro"><?php echo settings_index_i18n('PROFILE_DANGER_INTRO'); ?></p>
    </header>

    <div class="settings_danger_actions" aria-label="<?php echo settings_index_i18n('PROFILE_DANGER_ACTIONS_ARIA'); ?>">
      <article class="settings_danger_action" id="danger_delete_data_pill" aria-labelledby="danger-delete-data-heading">
        <div class="settings_danger_action_text">
          <h3 id="danger-delete-data-heading"><?php echo settings_index_i18n('PROFILE_DANGER_DELETE_DATA_TITLE'); ?></h3>
          <p><?php echo settings_index_i18n('PROFILE_DANGER_DELETE_DATA_TEXT'); ?></p>
        </div>
        <form class="settings_danger_confirm_form" id="danger_delete_data_form" method="POST" action="<?php echo Environment::appURL('api/v1/account/data/delete/'); ?>">
          <label class="settings_danger_phrase_label" for="danger_delete_data_phrase">
            <?php echo settings_index_i18n('PROFILE_DANGER_TYPE_PREFIX'); ?> <code><?php echo settings_index_i18n('PROFILE_DANGER_DELETE_DATA_PHRASE'); ?></code>
          </label>
          <input type="text" id="danger_delete_data_phrase" name="confirm_phrase" autocomplete="off" spellcheck="false" autocapitalize="characters" maxlength="32" pattern="DELETE ALL DATA" aria-label="<?php echo settings_index_i18n('PROFILE_DANGER_DELETE_DATA_ARIA'); ?>">
          <button type="submit" class="btn btn_delete" id="danger_delete_data_confirm" disabled><?php echo settings_index_i18n('PROFILE_CONFIRM_BUTTON'); ?></button>
        </form>
      </article>

      <article class="settings_danger_action" id="danger_delete_account_pill" aria-labelledby="danger-delete-account-heading">
        <div class="settings_danger_action_text">
          <h3 id="danger-delete-account-heading"><?php echo settings_index_i18n('PROFILE_DANGER_DELETE_ACCOUNT_TITLE'); ?></h3>
          <p><?php echo settings_index_i18n('PROFILE_DANGER_DELETE_ACCOUNT_TEXT'); ?></p>
        </div>
        <form class="settings_danger_confirm_form" id="danger_delete_account_form" method="POST" action="<?php echo Environment::appURL('api/v1/account/delete/'); ?>">
          <label class="settings_danger_phrase_label" for="danger_delete_account_phrase">
            <?php echo settings_index_i18n('PROFILE_DANGER_TYPE_PREFIX'); ?> <code><?php echo settings_index_i18n('PROFILE_DANGER_DELETE_ACCOUNT_PHRASE'); ?></code>
          </label>
          <input type="text" id="danger_delete_account_phrase" name="confirm_phrase" autocomplete="off" spellcheck="false" autocapitalize="characters" maxlength="32" pattern="DELETE MY ACCOUNT" aria-label="<?php echo settings_index_i18n('PROFILE_DANGER_DELETE_ACCOUNT_ARIA'); ?>">
          <button type="submit" class="btn btn_delete" id="danger_delete_account_confirm" disabled><?php echo settings_index_i18n('PROFILE_CONFIRM_BUTTON'); ?></button>
        </form>
      </article>

      <div id="danger_zone_status" class="settings_danger_status status_text" role="status" aria-live="polite"></div>
    </div>
  </section>
