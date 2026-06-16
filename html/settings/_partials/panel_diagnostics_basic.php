<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel settings_card_group settings_card_group--basic" id="panel-diagnostics-basic">
  <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_DIAGNOSTICS_BASIC_TITLE'); ?></h2>
  <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DIAGNOSTICS_BASIC_DESC'); ?></p>
  <ul class="settings_diagnostics_links">
    <li><a href="<?php echo Environment::appURL('help/troubleshooting/'); ?>"><?php echo settings_index_i18n('SETTINGS_DIAGNOSTICS_LINK_TROUBLESHOOTING'); ?></a></li>
    <li><a href="<?php echo Environment::appURL('transparency/diagnostics/'); ?>"><?php echo settings_index_i18n('SETTINGS_DIAGNOSTICS_LINK_TRANSPARENCY'); ?></a></li>
    <li><button type="button" id="settings_copy_support_info_btn" class="btn btn_secondary"><?php echo settings_index_i18n('SETTINGS_DIAGNOSTICS_COPY_SUPPORT_INFO'); ?></button></li>
    <li><button type="button" id="settings_export_debug_bundle_btn" class="btn btn_secondary"><?php echo settings_index_i18n('SETTINGS_DIAGNOSTICS_EXPORT_BUNDLE_BTN'); ?></button></li>
    <li><a href="<?php echo Environment::appURL('contact/'); ?>"><?php echo settings_index_i18n('SETTINGS_DIAGNOSTICS_LINK_ISSUE_REPORT'); ?></a></li>
  </ul>
  <div id="settings_copy_support_info_status" class="status_message" role="status" aria-live="polite"></div>
  <div id="settings_export_debug_bundle_status" class="status_message" role="status" aria-live="polite"></div>
</section>
