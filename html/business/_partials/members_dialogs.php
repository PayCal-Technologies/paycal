<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <dialog
    id="businesses_member_revoke_dialog"
    class="dialog businesses_member_revoke_dialog"
    aria-modal="true"
    aria-labelledby="businesses_member_revoke_dialog_title"
    aria-describedby="businesses_member_revoke_dialog_message"
    data-dialog-invoker-bridge
    data-dialog-close-tts="<?php echo businesses_index_i18n_html('BUSINESSES_MEMBER_REVOKE_DIALOG_TITLE'); ?>"
  >
    <form id="businesses_member_revoke_form" method="dialog">
      <section class="modal_header">
        <h2 id="businesses_member_revoke_dialog_title" class="modal_title"><?php echo businesses_index_i18n_html('BUSINESSES_MEMBER_REVOKE_DIALOG_TITLE'); ?></h2>
        <button type="button" id="businesses_member_revoke_close" class="btn_close" data-dialog-close="businesses_member_revoke_dialog" commandfor="businesses_member_revoke_dialog" command="close" aria-label="<?php echo businesses_index_i18n_html('CLOSE'); ?>">&times;</button>
      </section>
      <section class="modal_content f_column businesses_member_revoke_dialog_content">
        <p id="businesses_member_revoke_dialog_message" class="businesses_member_revoke_dialog_message"></p>
      </section>
      <section class="modal_footer">
        <div class="flex f_center f_space_around">
          <button type="submit" id="businesses_member_revoke_confirm" class="btn btn_delete"><?php echo businesses_index_i18n_html('BUSINESSES_REVOKE'); ?></button>
          <button type="button" id="businesses_member_revoke_cancel" class="btn btn_secondary" data-dialog-close="businesses_member_revoke_dialog" commandfor="businesses_member_revoke_dialog" command="close"><?php echo businesses_index_i18n_html('CANCEL'); ?></button>
        </div>
      </section>
    </form>
  </dialog>

  <dialog
    id="businesses_member_reports_dialog"
    class="dialog dialog_fullscreen businesses_member_reports_dialog"
    aria-modal="true"
    aria-labelledby="businesses_member_reports_dialog_title"
    aria-describedby="businesses_member_reports_dialog_body"
    data-dialog-invoker-bridge
    data-dialog-close-tts="<?php echo businesses_index_i18n_html('BUSINESSES_MEMBER_REPORTS_DIALOG_TITLE'); ?>"
    data-dialog-close-on-backdrop="true"
  >
    <section class="modal_header">
      <h2 id="businesses_member_reports_dialog_title" class="modal_title"><?php echo businesses_index_i18n_html('BUSINESSES_MEMBER_REPORTS_DIALOG_TITLE'); ?></h2>
      <button type="button" id="businesses_member_reports_close" class="btn_close" data-dialog-close="businesses_member_reports_dialog" commandfor="businesses_member_reports_dialog" command="close" aria-label="<?php echo businesses_index_i18n_html('CLOSE'); ?>">&times;</button>
    </section>
    <section class="modal_content businesses_member_reports_dialog_content">
      <div id="businesses_member_reports_dialog_body" class="businesses_member_reports_dialog_body earnings_member_reports_mount" role="region" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_MEMBER_REPORTS_CONTENT_ARIA'); ?>" aria-live="polite" aria-busy="false"></div>
    </section>
    <section class="modal_footer">
      <button type="button" class="btn btn_secondary" data-dialog-close="businesses_member_reports_dialog" commandfor="businesses_member_reports_dialog" command="close"><?php echo businesses_index_i18n_html('CLOSE'); ?></button>
    </section>
  </dialog>
