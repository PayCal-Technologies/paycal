<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <section class="panel businesses_connect_shell" id="panel-business-connect">
    <header class="businesses_connect_intro">
      <h2 class="businesses_connect_title"><?php echo businesses_index_i18n_html('CONNECTIONS_PAGE_TITLE'); ?></h2>
      <p class="businesses_connect_lede"><?php echo businesses_index_i18n_html('CONNECTIONS_PAGE_HELP'); ?></p>
    </header>

    <section class="connections_people_panel" id="connections_people_panel" aria-labelledby="connections_people_title">
      <header class="connections_section_header">
        <div>
          <h2 class="businesses_panel_title" id="connections_people_title"><?php echo businesses_index_i18n_html('CONNECTIONS_PEOPLE_TITLE'); ?></h2>
          <p class="help_text"><?php echo businesses_index_i18n_html('CONNECTIONS_PEOPLE_HELP'); ?></p>
        </div>
      </header>
      <form id="connections_person_form" class="connections_person_form" method="dialog">
        <label for="connections_person_email"><?php echo businesses_index_i18n_html('CONNECTIONS_PERSON_EMAIL_LABEL'); ?></label>
        <div class="connections_person_controls">
          <input id="connections_person_email" name="target_email" type="email" autocomplete="email" maxlength="200" placeholder="<?php echo businesses_index_i18n_html('CONNECTIONS_PERSON_EMAIL_PLACEHOLDER'); ?>">
          <button type="submit" class="btn btn_secondary"><?php echo businesses_index_i18n_html('CONNECTIONS_ADD_PERSON'); ?></button>
        </div>
      </form>
      <p id="connections_people_status" class="help_text" role="status" aria-live="polite" aria-atomic="true"></p>
      <div id="connections_people_list" class="connections_person_card_list">
        <p class="datagrid_empty"><?php echo businesses_index_i18n_html('CONNECTIONS_PEOPLE_EMPTY'); ?></p>
      </div>
    </section>

    <section class="connections_recovery_panel" aria-labelledby="connections_recovery_title">
      <h2 class="businesses_panel_title" id="connections_recovery_title"><?php echo businesses_index_i18n_html('CONNECTIONS_RECOVERY_TITLE'); ?></h2>
      <p class="help_text"><?php echo businesses_index_i18n_html('CONNECTIONS_RECOVERY_HELP'); ?></p>
    </section>

    <section class="businesses_current_panel hidden" id="businesses_current_panel">
      <div id="businesses_current_meta" class="businesses_current_meta"></div>
      <p id="businesses_current_status" class="businesses_current_status" role="status" aria-live="polite" aria-atomic="true"></p>
    </section>

    <section class="businesses_free_audit_panel hidden" id="businesses_free_audit_panel">
      <header class="businesses_free_audit_header">
        <div>
          <h2 class="businesses_panel_title"><?php echo businesses_index_i18n_html('BUSINESSES_FREE_AUDIT_ARIA'); ?></h2>
          <p class="businesses_free_audit_intro"><?php echo businesses_index_i18n_html('BUSINESSES_FREE_AUDIT_INTRO'); ?></p>
        </div>
      </header>
      <ul class="businesses_free_audit_scope_list" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_FREE_AUDIT_PANEL_HELP'); ?>">
        <li><?php echo businesses_index_i18n_html('BUSINESSES_FREE_AUDIT_ITEM_ACCESS'); ?></li>
        <li><?php echo businesses_index_i18n_html('BUSINESSES_FREE_AUDIT_ITEM_CONSENT'); ?></li>
        <li><?php echo businesses_index_i18n_html('BUSINESSES_FREE_AUDIT_ITEM_MEMBERSHIP'); ?></li>
      </ul>
      <div class="visually_hidden">
        <p id="businesses_free_audit_sr_instructions"><?php echo businesses_index_i18n_html('BUSINESSES_FREE_AUDIT_SR'); ?></p>
        <p id="businesses_free_audit_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
      </div>
      <div id="businesses-free-audit-grid-host" class="datagrid_container" role="region" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_FREE_AUDIT_ARIA'); ?>" aria-describedby="businesses_free_audit_sr_instructions businesses_free_audit_sr_status">
        <div class="datagrid_body"><div class="datagrid_empty"><?php echo businesses_index_i18n_html('BUSINESSES_FREE_AUDIT_EMPTY'); ?></div></div>
      </div>
    </section>

<?php require __DIR__ . '/../_archive/partials/browser_panel.php'; ?>

    <dialog id="connections_person_manage_dialog" data-dialog-invoker-bridge data-dialog-close-tts="<?php echo businesses_index_i18n_html('CONNECTIONS_MANAGE_PERSON_TITLE'); ?>" class="modal connections_person_manage_dialog" aria-labelledby="connections_person_manage_title">
      <form id="connections_person_manage_form" method="dialog" class="modal_content connections_person_manage_content">
        <header class="modal_header">
          <h2 id="connections_person_manage_title"><?php echo businesses_index_i18n_html('CONNECTIONS_MANAGE_PERSON_TITLE'); ?></h2>
          <button type="button" class="modal_close" data-dialog-close="connections_person_manage_dialog" commandfor="connections_person_manage_dialog" command="close" aria-label="<?php echo businesses_index_i18n_html('CLOSE'); ?>">&times;</button>
        </header>
        <div id="connections_person_manage_body" class="connections_person_manage_body"></div>
        <footer class="modal_actions">
          <button type="button" class="btn btn_secondary" id="connections_person_manage_cancel" data-dialog-close="connections_person_manage_dialog" commandfor="connections_person_manage_dialog" command="close"><?php echo businesses_index_i18n_html('CANCEL'); ?></button>
          <button type="submit" class="btn btn_primary"><?php echo businesses_index_i18n_html('SAVE'); ?></button>
        </footer>
      </form>
    </dialog>
  </section>
