<?php declare(strict_types=1);

namespace PayCal\Domain;

/** @var User $user */
$sitesForDefaults = [];
foreach (Sites::getSites($user->user_uuid, 'active') as $siteId => $siteData) {
  $sitesForDefaults[(string) $siteId] = (string) ($siteData['site_name'] ?? '');
}

?>
<section class="panel settings_card_group" id="panel-calendar-work-defaults">
  <form id="account_work_defaults_form" method="POST" action="<?php echo Environment::appURL('api/v1/account/info/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_ACCOUNT_WORK_DEFAULTS_ARIA'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_ACCOUNT_WORK_DEFAULTS_TITLE'); ?></h2>
    <p class="help_text"><?php echo settings_index_i18n('SETTINGS_ACCOUNT_WORK_DEFAULTS_DESC'); ?></p>

    <div class="flex f_baseline w100">
      <label for="default_site_id" class="w25"><?php echo settings_index_i18n('DEFAULT_SITE'); ?></label>
      <select id="default_site_id" name="default_site_id" class="w75" aria-label="<?php echo settings_index_i18n('DEFAULT_SITE'); ?>">
        <option value=""><?php echo settings_index_i18n('NONE'); ?></option>
        <?php foreach ($sitesForDefaults as $siteId => $siteName) { ?>
          <option value="<?php echo htmlspecialchars($siteId, ENT_QUOTES, 'UTF-8'); ?>"<?php if ((string) $user->default_site_id === (string) $siteId) { echo ' selected'; } ?>><?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></option>
        <?php } ?>
      </select>
    </div>

    <div class="flex f_baseline w100">
      <label for="default_hours" class="w25"><?php echo settings_index_i18n('HOURS'); ?></label>
      <input type="number" id="default_hours" name="default_hours" class="w75" min="0" max="24" step="0.25" value="<?php echo htmlspecialchars((string) $user->default_hours, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo settings_index_i18n('HOURS'); ?>">
    </div>

    <div class="flex f_baseline w100">
      <label for="default_living_out_allowance" class="w25"><?php echo settings_index_i18n('LIVING_OUT_ALLOWANCE'); ?></label>
      <input type="number" id="default_living_out_allowance" name="default_living_out_allowance" class="w75" min="0" step="0.01" value="<?php echo htmlspecialchars((string) $user->default_living_out_allowance, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo settings_index_i18n('LIVING_OUT_ALLOWANCE'); ?>">
    </div>

    <div class="flex f_baseline w100">
      <label for="default_travel_hours" class="w25"><?php echo settings_index_i18n('TRAVEL'); ?></label>
      <input type="number" id="default_travel_hours" name="default_travel_hours" class="w75" min="0" step="0.25" value="<?php echo htmlspecialchars((string) $user->default_travel_hours, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo settings_index_i18n('TRAVEL'); ?>">
    </div>
  </form>
</section>
