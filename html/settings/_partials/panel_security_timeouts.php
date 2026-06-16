<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel" id="panel-security">
  <form id="account_security_timeout_form" method="POST" action="<?php echo Environment::appURL('api/v1/account/security/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_TIMEOUT_FORM_ARIA'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent"><?php echo settings_index_i18n('SETTINGS_SECTION_SECURITY'); ?></h2>
    <p class="help_text"><?php echo settings_index_i18n('SETTINGS_SECURITY_SESSION_TIMING_HELP'); ?></p>

    <div class="security_level_card">
      <label for="security_level_slider" class="security_level_label"><?php echo settings_index_i18n('SETTINGS_SECURITY_LEVEL'); ?></label>
      <div class="security_slider_row">
        <span class="security_slider_edge"><?php echo settings_index_i18n('SETTINGS_SECURITY_LOW'); ?></span>
        <input id="security_level_slider" type="range" min="0" max="100" step="1" value="50" aria-label="<?php echo settings_index_i18n('SETTINGS_SECURITY_LEVEL_SLIDER_ARIA'); ?>" data-hover-help="<?php echo settings_index_i18n('SETTINGS_SECURITY_LEVEL_HOVER_HELP'); ?>">
        <span class="security_slider_edge"><?php echo settings_index_i18n('SETTINGS_SECURITY_HIGH'); ?></span>
      </div>
      <div id="security_level_value" class="security_level_value"><?php echo settings_index_i18n('SETTINGS_SECURITY_BALANCED'); ?></div>
      <p id="security_level_hint" class="help_text"><?php echo settings_index_i18n('SETTINGS_SECURITY_LEVEL_HINT'); ?></p>
    </div>

    <div class="security_timeouts_table_wrap">
      <table class="security_datagrid security_datagrid_table" aria-label="<?php echo settings_index_i18n('SETTINGS_SECURITY_DERIVED_TIMEOUTS_ARIA'); ?>">
        <colgroup>
          <col class="security_col_activity">
          <col class="security_col_timeout">
          <col class="security_col_session">
        </colgroup>
        <thead>
          <tr class="security_datagrid_row security_datagrid_header">
            <th scope="col"><?php echo settings_index_i18n('SETTINGS_SECURITY_TABLE_ACTION'); ?></th>
            <th scope="col"><?php echo settings_index_i18n('SETTINGS_SECURITY_TABLE_TTL'); ?></th>
            <th scope="col"><?php echo settings_index_i18n('SETTINGS_SECURITY_TABLE_LEFT'); ?></th>
          </tr>
        </thead>
        <tbody>
          <tr class="security_datagrid_row">
            <th scope="row" id="security_row_signout" data-hover-help="<?php echo settings_index_i18n('SETTINGS_SECURITY_ROW_AUTO_SIGNOUT_HOVER'); ?>"><?php echo settings_index_i18n('SETTINGS_SECURITY_ROW_AUTO_SIGNOUT'); ?></th>
            <td id="security_timeout_signout">60 minutes</td>
            <td id="security_remaining_signout">-</td>
          </tr>
          <tr class="security_datagrid_row">
            <th scope="row" id="security_row_account" data-hover-help="<?php echo settings_index_i18n('SETTINGS_SECURITY_ROW_ACCOUNT_EDIT_HOVER'); ?>"><?php echo settings_index_i18n('SETTINGS_SECURITY_ROW_ACCOUNT_EDIT'); ?></th>
            <td id="security_timeout_account">15 minutes</td>
            <td id="security_remaining_account">-</td>
          </tr>
          <tr class="security_datagrid_row">
            <th scope="row" id="security_row_calendar" data-hover-help="<?php echo settings_index_i18n('SETTINGS_SECURITY_ROW_CALENDAR_EDIT_HOVER'); ?>"><?php echo settings_index_i18n('SETTINGS_SECURITY_ROW_CALENDAR_EDIT'); ?></th>
            <td id="security_timeout_calendar">60 minutes</td>
            <td id="security_remaining_calendar">-</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="security_level_card">
      <label for="emergency_signout_window_ms" class="security_level_label"><?php echo settings_index_i18n('SETTINGS_SECURITY_EMERGENCY_SIGNOUT'); ?></label>
      <div class="security_slider_row security_slider_row_compact">
        <span class="security_slider_edge">0.2s</span>
        <input id="emergency_signout_window_ms" name="emergency_signout_window_ms" type="range" min="200" max="2000" step="200" value="<?php echo htmlspecialchars((string) ($user->emergency_signout_window_ms ?? '600'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_SECURITY_EMERGENCY_WINDOW_ARIA'); ?>" data-hover-help="<?php echo settings_index_i18n('SETTINGS_SECURITY_EMERGENCY_WINDOW_HOVER'); ?>">
        <span class="security_slider_edge">2.0s</span>
      </div>
      <p id="emergency_signout_hint" class="help_text">Press ESC x3 in <span id="emergency_signout_window_ms_value"><?php echo htmlspecialchars(Strings::formatLocalizedNumber(((int) ($user->emergency_signout_window_ms ?? '600')) / 1000, 1, 1), ENT_QUOTES, 'UTF-8'); ?></span>s to sign out to a safe site.</p>
    </div>

    <input type="hidden" id="session_timeout" name="session_timeout" value="<?php echo htmlspecialchars((string) ($user->session_timeout ?? '3600'), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" id="form_ttl_settings" name="form_ttl_settings" value="<?php echo htmlspecialchars((string) ($user->form_ttl_settings ?? '3600'), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" id="form_ttl_calendar" name="form_ttl_calendar" value="<?php echo htmlspecialchars((string) ($user->form_ttl_calendar ?? '3600'), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" id="form_ttl_general" name="form_ttl_general" value="<?php echo htmlspecialchars((string) ($user->form_ttl_general ?? '3600'), ENT_QUOTES, 'UTF-8'); ?>">
  </form>
</section>
