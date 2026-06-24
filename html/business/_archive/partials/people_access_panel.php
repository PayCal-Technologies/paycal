<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <section class="panel businesses_people_access_panel" aria-labelledby="businesses_people_access_title">
    <div class="businesses_section_header">
      <h2 id="businesses_people_access_title"><?php echo businesses_index_i18n_html('BUSINESSES_EDITOR_INLINE_TITLE'); ?></h2>
      <div class="businesses_header_actions">
        <button
          id="businesses_definitions_help_button"
          type="button"
          class="businesses_help_button"
          aria-haspopup="dialog"
          aria-controls="businesses_definitions_dialog"
          aria-expanded="false"
          aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_OPEN_DEFINITIONS_BTN'); ?>"
          title="<?php echo businesses_index_i18n_html('BUSINESSES_DEFINITIONS_TITLE'); ?>"
        >
          <span aria-hidden="true">?</span>
        </button>
      </div>
    </div>
    <div id="businesses_editor_inline_mount" class="panel businesses_inline_editor_panel businesses_settings_panel"></div>
  </section>
