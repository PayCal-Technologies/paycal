<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
    <section class="businesses_request_panel" title="<?php echo businesses_index_i18n_html('BUSINESSES_REQUEST_PANEL_HELP'); ?>" data-hover-help="<?php echo businesses_index_i18n_html('BUSINESSES_REQUEST_PANEL_HELP'); ?>">
      <div class="businesses_section_header">
        <h3><?php echo businesses_index_i18n_html('BUSINESSES_REQUEST_ACCESS_TITLE'); ?></h3>
      </div>
      <p class="help_text"><?php echo businesses_index_i18n_html('BUSINESSES_REQUEST_ACCESS_HELP'); ?></p>
      <form id="businesses_request_join_form" class="businesses_inline_form" method="dialog">
        <div class="businesses_request_access_controls">
          <input id="businesses_request_email" type="search" maxlength="200" autocomplete="off" list="businesses_access_lookup_request" placeholder="<?php echo businesses_index_i18n_html('BUSINESSES_REQUEST_EMAIL_PLACEHOLDER'); ?>" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_REQUEST_EMAIL_PLACEHOLDER'); ?>">
          <datalist id="businesses_access_lookup_request"></datalist>
          <div class="businesses_access_level_pillbox" role="group" aria-label="Access Level">
            <button id="businesses_request_access_readonly" type="button" class="pill pill_selected" data-access-level="readonly" aria-pressed="true">Read-Only</button>
            <button id="businesses_request_access_full" type="button" class="pill" data-access-level="full" aria-pressed="false">Full Access</button>
          </div>
        </div>
        <button id="businesses_request_join_button" type="submit" class="btn btn_primary"><?php echo businesses_index_i18n_html('BUSINESSES_REQUEST_JOIN_BTN'); ?></button>
      </form>
      <p id="businesses_discovery_panel_status" class="help_text" role="status" aria-live="polite" aria-atomic="true"></p>
    </section>
