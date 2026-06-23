<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel settings_card_group settings_card_group--advanced" id="panel-debugging" data-hover-help="<?php echo settings_index_i18n('SETTINGS_DEBUG_PANEL_HOVER_HELP'); ?>">
  <form id="account_debug_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/debug/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_DEBUGGING_FORM_ARIA'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_SECTION_DEBUGGING_OPTIONAL'); ?></h2>
    <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DEBUG_INTRO'); ?></p>
    <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DEBUG_PERF_NOTE'); ?></p>

    <div class="flex f_baseline w100">
      <div class="w100">
        <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DEBUG_CONSOLE_HELP'); ?></p>
        <div class="radio_group pill_group" data-hover-help="<?php echo settings_index_i18n('SETTINGS_DEBUG_CONSOLE_HOVER'); ?>">
          <input class="radio" type="radio" id="debug_console_enabled_off" name="debug_console_enabled" value="0" <?php echo ('1' === (string) ($user->debug_console_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_CONSOLE_ENABLED)) ? '' : 'checked'; ?>>
          <label for="debug_console_enabled_off"><?php echo settings_index_i18n('SETTINGS_DEBUG_OPTION_OFF_DEFAULT'); ?></label>
          <input class="radio" type="radio" id="debug_console_enabled_on" name="debug_console_enabled" value="1" <?php echo ('1' === (string) ($user->debug_console_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_CONSOLE_ENABLED)) ? 'checked' : ''; ?>>
          <label for="debug_console_enabled_on"><?php echo settings_index_i18n('SETTINGS_DEBUG_OPTION_ON_MORE_DETAILS'); ?></label>
        </div>
      </div>
    </div>

    <br>

    <div class="flex f_baseline w100">
      <div class="w100">
        <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DEBUG_FINE_GRAINED_HELP'); ?></p>
        <div class="radio_group pill_group" data-hover-help="<?php echo settings_index_i18n('SETTINGS_DEBUG_FINE_GRAINED_HOVER'); ?>">
          <input class="radio" type="radio" id="debug_fine_grained_enabled_off" name="debug_fine_grained_enabled" value="0" <?php echo ('1' === (string) ($user->debug_fine_grained_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_FINE_GRAINED_ENABLED)) ? '' : 'checked'; ?>>
          <label for="debug_fine_grained_enabled_off"><?php echo settings_index_i18n('SETTINGS_DEBUG_OPTION_OFF_DEFAULT'); ?></label>
          <input class="radio" type="radio" id="debug_fine_grained_enabled_on" name="debug_fine_grained_enabled" value="1" <?php echo ('1' === (string) ($user->debug_fine_grained_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_FINE_GRAINED_ENABLED)) ? 'checked' : ''; ?>>
          <label for="debug_fine_grained_enabled_on"><?php echo settings_index_i18n('SETTINGS_DEBUG_OPTION_ON_MORE_DETAILS'); ?></label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label for="debug_ttl_minutes" class="w25"><?php echo settings_index_i18n('SETTINGS_DEBUG_TTL_LABEL'); ?></label>
      <div class="w75">
        <select id="debug_ttl_minutes" name="debug_ttl_minutes" class="w100" aria-label="<?php echo settings_index_i18n('SETTINGS_DEBUG_TTL_LABEL'); ?>">
          <?php foreach (['0' => 'SETTINGS_DEBUG_TTL_OFF', '15' => 'SETTINGS_DEBUG_TTL_15', '30' => 'SETTINGS_DEBUG_TTL_30', '60' => 'SETTINGS_DEBUG_TTL_60'] as $minutes => $labelKey) { ?>
            <option value="<?php echo $minutes; ?>"<?php if ((string) ($user->debug_ttl_minutes ?? UserPreferenceDefaults::DEFAULT_DEBUG_TTL_MINUTES) === $minutes) { echo ' selected'; } ?>><?php echo settings_index_i18n($labelKey); ?></option>
          <?php } ?>
        </select>
        <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DEBUG_TTL_HELP'); ?></p>
      </div>
    </div>

    <br>

    <div class="flex f_baseline w100">
      <div class="w100">
        <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DEBUG_NETWORK_HELP'); ?></p>
        <div class="radio_group pill_group" data-hover-help="<?php echo settings_index_i18n('SETTINGS_DEBUG_NETWORK_HOVER'); ?>">
          <input class="radio" type="radio" id="debug_network_enabled_off" name="debug_network_enabled" value="0" <?php echo ('1' === (string) ($user->debug_network_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_NETWORK_ENABLED)) ? '' : 'checked'; ?>>
          <label for="debug_network_enabled_off"><?php echo settings_index_i18n('SETTINGS_DEBUG_OPTION_OFF_DEFAULT'); ?></label>
          <input class="radio" type="radio" id="debug_network_enabled_on" name="debug_network_enabled" value="1" <?php echo ('1' === (string) ($user->debug_network_enabled ?? UserPreferenceDefaults::DEFAULT_DEBUG_NETWORK_ENABLED)) ? 'checked' : ''; ?>>
          <label for="debug_network_enabled_on"><?php echo settings_index_i18n('SETTINGS_DEBUG_OPTION_ON_MORE_DETAILS'); ?></label>
        </div>
      </div>
    </div>

    <?php if (AdminSurface::userCanAccess()) { ?>
    <div class="flex f_baseline w100 settings_diagnostics_argus_link_row">
      <a class="btn btn_secondary" href="<?php echo Environment::appURL('admin/argus/'); ?>"><?php echo settings_index_i18n('SETTINGS_DIAGNOSTICS_ARGUS_LINK'); ?></a>
    </div>
    <?php } ?>
  </form>
</section>
