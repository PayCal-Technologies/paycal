<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel settings_card_group" id="panel-security-sensitive">
  <form id="account_security_sensitive_form" method="POST" action="<?php echo Environment::appURL('api/v1/account/info/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_SECURITY_SENSITIVE_ARIA'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_SECURITY_SENSITIVE_TITLE'); ?></h2>
    <p class="help_text"><?php echo settings_index_i18n('SETTINGS_SECURITY_SENSITIVE_DESC'); ?></p>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_REQUIRE_REAUTH_EXPORT_LABEL'); ?></label>
      <div class="w75 work_entry_tags">
        <input type="checkbox" name="require_reauth_export" value="1" id="require_reauth_export"<?php if ((string) ($user->require_reauth_export ?? '0') === '1') { echo ' checked'; } ?>>
        <label for="require_reauth_export"><?php echo settings_index_i18n('SETTINGS_REQUIRE_REAUTH_EXPORT_LABEL'); ?></label>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_REQUIRE_REAUTH_IMPORT_LABEL'); ?></label>
      <div class="w75 work_entry_tags">
        <input type="checkbox" name="require_reauth_import" value="1" id="require_reauth_import"<?php if ((string) ($user->require_reauth_import ?? '0') === '1') { echo ' checked'; } ?>>
        <label for="require_reauth_import"><?php echo settings_index_i18n('SETTINGS_REQUIRE_REAUTH_IMPORT_LABEL'); ?></label>
      </div>
    </div>
  </form>
</section>
