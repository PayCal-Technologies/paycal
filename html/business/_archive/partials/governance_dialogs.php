<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <dialog id="businesses_browser_profile_dialog" class="dialog businesses_browser_profile_dialog" aria-modal="true" aria-labelledby="businesses_browser_profile_title" aria-describedby="businesses_browser_profile_desc">
    <form method="dialog">
      <section class="modal_header">
        <h2 id="businesses_browser_profile_title" class="modal_title"><?php echo businesses_index_i18n('BUSINESSES_BROWSER_PROFILE_TITLE'); ?></h2>
        <button type="button" class="btn_close" data-dialog-close="businesses_browser_profile_dialog" aria-label="<?php echo businesses_index_i18n('CLOSE'); ?>">&times;</button>
      </section>
      <section class="modal_content f_column businesses_browser_profile_content">
        <p id="businesses_browser_profile_desc" class="help_text"><?php echo businesses_index_i18n('BUSINESSES_BROWSER_PROFILE_DESC'); ?></p>
        <div id="businesses_browser_profile_body" class="businesses_browser_profile_body"></div>
        <p id="businesses_browser_profile_status" class="businesses_browser_profile_status hidden" role="status" aria-live="polite"></p>
      </section>
      <section class="modal_footer">
        <button type="button" id="businesses_browser_profile_connect" class="btn btn_primary"><?php echo businesses_index_i18n('BUSINESSES_BROWSER_REQUEST_ACCESS'); ?></button>
        <button type="button" class="btn btn_secondary" data-dialog-close="businesses_browser_profile_dialog"><?php echo businesses_index_i18n('CLOSE'); ?></button>
      </section>
    </form>
  </dialog>

  <dialog id="businesses_current_details_dialog" class="dialog businesses_current_details_dialog" aria-modal="true" aria-labelledby="businesses_current_details_title" aria-describedby="businesses_current_details_aria">
    <form method="dialog">
      <section class="modal_header">
        <h2 id="businesses_current_details_title" class="modal_title"><?php echo businesses_index_i18n('BUSINESSES_CURRENT_DETAILS_TITLE'); ?></h2>
        <button type="button" class="btn_close" data-dialog-close="businesses_current_details_dialog" aria-label="<?php echo businesses_index_i18n('CLOSE'); ?>">&times;</button>
      </section>
      <section class="modal_content f_column businesses_current_details_content">
        <p id="businesses_current_details_aria" class="help_text"><?php echo businesses_index_i18n('BUSINESSES_CURRENT_DETAILS_ARIA'); ?></p>
        <div id="businesses_current_details_body" class="businesses_current_details_body"></div>
      </section>
      <section class="modal_footer">
        <button type="button" class="btn btn_secondary" data-dialog-close="businesses_current_details_dialog"><?php echo businesses_index_i18n('CLOSE'); ?></button>
      </section>
    </form>
  </dialog>
