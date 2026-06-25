<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel" id="panel-federated-signin">
  <div class="w100">
    <h2 class="heading-accent settings_card_title"><?php echo htmlspecialchars(settings_index_i18n('SETTINGS_FEDERATED_SECTION_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h2>
    <div id="security_federated_widget" class="security_status_widget" aria-live="polite">
      <div class="security_status_note"><?php echo htmlspecialchars(settings_index_i18n('SETTINGS_FEDERATED_SECTION_NOTE'), ENT_QUOTES, 'UTF-8'); ?></div>
      <div id="federated_provider_list" class="federated_provider_list"></div>
      <div id="federated_provider_status" class="status_message status_message_callout passkey_action_status" role="status" aria-live="polite" aria-atomic="true"></div>
    </div>
  </div>
</section>
